<?php

namespace App\Modules\Contratacao\Application\Service;

use App\Models\Contratacao;
use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoRepositoryPort;
use App\Modules\Contratacao\Application\Port\Out\FornecedorCatalogoRepositoryPort;
use App\Modules\Contratacao\Application\Port\Out\FornecedorHistoricoRepositoryPort;
use App\Modules\Contratacao\Application\Port\Out\N8nSugestaoFornecedorPort;
use App\Modules\Contratacao\Application\Port\Out\SugestaoCacheRepositoryPort;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoNaoEncontradaException;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoTransicaoInvalidaException;
use App\Modules\Contratacao\Domain\Policies\ContratacaoElegivelParaVendorList;
use App\Modules\Contratacao\Domain\Policies\ContratacaoPertenceAoTenant;
use App\Modules\Contratacao\Domain\Policies\FornecedorCnpjUnicoNaContratacao;
use Illuminate\Support\Carbon;

final class SugestaoFornecedorService
{
  private const AVISO = 'Sugestões automáticas. Valide CNPJ e contatos antes de cadastrar.';

  public function __construct(
    private ContratacaoRepositoryPort $contratacaoRepository,
    private FornecedorHistoricoRepositoryPort $historicoRepository,
    private FornecedorCatalogoRepositoryPort $catalogoRepository,
    private SugestaoCacheRepositoryPort $cacheRepository,
    private N8nSugestaoFornecedorPort $n8nClient,
    private SugestaoFornecedorContextBuilder $contextBuilder,
    private SugestaoFornecedorRanker $ranker,
  ) {}

  /**
   * @param array<string, mixed> $opcoes
   * @return array<string, mixed>
   */
  public function gerar(UsuarioCliente $usuario, string $uuid, array $opcoes = []): array
  {
    $contratacao = $this->loadOrFail($uuid, $usuario->tenant_id);

    if (! ContratacaoElegivelParaVendorList::check($contratacao)) {
      throw new ContratacaoTransicaoInvalidaException('Contratação não está em análise de fornecedores.');
    }

    $limite = max(1, min(5, (int) ($opcoes['limite'] ?? 3)));
    $forcarRegeneracao = (bool) ($opcoes['forcar_regeneracao'] ?? false);

    $contexto = $this->contextBuilder->build($contratacao);
    $contextoHash = (string) $contexto['contexto_hash'];

    if (! $forcarRegeneracao) {
      $cache = $this->cacheRepository->buscarValido($contratacao, $contextoHash);
      if ($cache !== null) {
        return $cache;
      }
    }

    $historico = $this->historicoRepository->listarCandidatos($contratacao, $usuario->tenant_id);
    $catalogo = $this->catalogoRepository->listarAtivosPorTenant($usuario->tenant_id);
    $candidatos = array_merge($historico, $catalogo);

    $cnpjsExcluir = $contexto['cnpjs_cadastrados'];
    $sugestoes = $this->ranker->ranquear($candidatos, $contexto, $cnpjsExcluir, $limite);

    $slotsRestantes = $limite - count($sugestoes);
    if ($slotsRestantes > 0) {
      $cnpjsJaUsados = array_map(
        fn (array $s) => FornecedorCnpjUnicoNaContratacao::normalizarCnpj($s['cnpj']),
        $sugestoes,
      );
      $cnpjsExcluirN8n = array_values(array_unique(array_merge($cnpjsExcluir, $cnpjsJaUsados)));

      $iaCandidatos = $this->n8nClient->solicitarSugestoes(
        $usuario->tenant_id,
        $contratacao->uuid,
        [
          'titulo' => $contexto['titulo'],
          'categoria_servico' => $contexto['categoria_servico'],
          'local' => $contexto['local'],
          'empresa_endereco' => $contexto['empresa_endereco'] ?? null,
          'termo_referencia' => $contexto['termo_referencia'],
          'qqp_resumo' => $contexto['qqp_resumo'],
          'slots_restantes' => $slotsRestantes,
          'cnpjs_excluir' => $cnpjsExcluirN8n,
        ],
      );

      foreach ($iaCandidatos as $index => $ia) {
        $iaCandidatos[$index]['origem'] = 'ia_externa';
      }

      $iaSugestoes = $this->ranker->ranquear($iaCandidatos, $contexto, $cnpjsExcluirN8n, $slotsRestantes);
      $sugestoes = $this->mesclarSugestoes($sugestoes, $iaSugestoes, $limite);
    }

    $fonte = $this->determinarFonte($sugestoes);

    $payload = [
      'contratacao_uuid' => $contratacao->uuid,
      'gerado_em' => Carbon::now()->toIso8601String(),
      'fonte' => $fonte,
      'aviso' => self::AVISO,
      'contexto_resumido' => [
        'categoria_servico' => $contexto['categoria_servico'],
        'local' => $contexto['local'],
        'titulo' => $contexto['titulo'],
      ],
      'sugestoes' => $sugestoes,
      'meta' => [
        'total_encontrado' => count($candidatos),
        'retornados' => count($sugestoes),
        'cache_hit' => false,
      ],
    ];

    $this->cacheRepository->salvar($contratacao, $usuario->tenant_id, $contextoHash, $payload);

    return $payload;
  }

  private function loadOrFail(string $uuid, string $tenantId): Contratacao
  {
    $contratacao = $this->contratacaoRepository->findByUuidForTenant($uuid, $tenantId);

    if ($contratacao === null || ! ContratacaoPertenceAoTenant::check($contratacao, $tenantId)) {
      throw new ContratacaoNaoEncontradaException;
    }

    return $contratacao;
  }

  /**
   * @param list<array<string, mixed>> $base
   * @param list<array<string, mixed>> $extras
   * @return list<array<string, mixed>>
   */
  private function mesclarSugestoes(array $base, array $extras, int $limite): array
  {
    $merged = $base;
    $cnpjs = array_map(
      fn (array $s) => FornecedorCnpjUnicoNaContratacao::normalizarCnpj($s['cnpj']),
      $base,
    );

    $chaves = $cnpjs;
    foreach ($extras as $extra) {
      $cnpj = FornecedorCnpjUnicoNaContratacao::normalizarCnpj((string) ($extra['cnpj'] ?? ''));
      $nome = mb_strtolower(trim((string) ($extra['razao_social'] ?? '')));
      $chave = $cnpj !== '' ? $cnpj : ($nome !== '' ? 'nome:'.$nome : '');
      if ($chave === '' || in_array($chave, $chaves, true)) {
        continue;
      }
      if ($cnpj !== '' && in_array($cnpj, $cnpjs, true)) {
        continue;
      }
      $merged[] = $extra;
      $chaves[] = $chave;
      if ($cnpj !== '') {
        $cnpjs[] = $cnpj;
      }
      if (count($merged) >= $limite) {
        break;
      }
    }

    foreach ($merged as $i => $item) {
      $merged[$i]['rank'] = $i + 1;
    }

    return $merged;
  }

  /**
   * @param list<array<string, mixed>> $sugestoes
   */
  private function determinarFonte(array $sugestoes): string
  {
    if ($sugestoes === []) {
      return 'nenhuma';
    }

    $origens = array_unique(array_column($sugestoes, 'origem'));

    if (count($origens) > 1) {
      return 'hibrido';
    }

    return (string) $origens[0];
  }
}

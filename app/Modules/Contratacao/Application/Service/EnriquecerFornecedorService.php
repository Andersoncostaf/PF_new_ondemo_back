<?php

namespace App\Modules\Contratacao\Application\Service;

use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoRepositoryPort;
use App\Modules\Contratacao\Application\Port\Out\FornecedorCatalogoRepositoryPort;
use App\Modules\Contratacao\Application\Port\Out\FornecedorHistoricoRepositoryPort;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoNaoEncontradaException;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoTransicaoInvalidaException;
use App\Modules\Contratacao\Domain\Policies\ContratacaoElegivelParaVendorList;
use App\Modules\Contratacao\Domain\Policies\ContratacaoPertenceAoTenant;
use App\Modules\Contratacao\Domain\Policies\FornecedorCnpjUnicoNaContratacao;
use App\Modules\Contratacao\Infrastructure\External\BrasilApiCnpjClient;
use App\Modules\Contratacao\Infrastructure\External\WebEnriquecerFornecedorClient;

final class EnriquecerFornecedorService
{
    public function __construct(
        private ContratacaoRepositoryPort $contratacaoRepository,
        private FornecedorCatalogoRepositoryPort $catalogoRepository,
        private FornecedorHistoricoRepositoryPort $historicoRepository,
        private BrasilApiCnpjClient $brasilApi,
        private WebEnriquecerFornecedorClient $webClient,
    ) {}

    /**
     * Completa dados do fornecedor a partir de catálogo, histórico, BrasilAPI e busca web.
     *
     * @param array<string, mixed> $entrada
     * @return array<string, mixed>
     */
    public function enriquecer(UsuarioCliente $usuario, string $uuid, array $entrada): array
    {
        $contratacao = $this->contratacaoRepository->findByUuidForTenant($uuid, $usuario->tenant_id);

        if ($contratacao === null || ! ContratacaoPertenceAoTenant::check($contratacao, $usuario->tenant_id)) {
            throw new ContratacaoNaoEncontradaException;
        }

        if (! ContratacaoElegivelParaVendorList::check($contratacao)) {
            throw new ContratacaoTransicaoInvalidaException('Contratação não está em análise de fornecedores.');
        }

        $cnpj = FornecedorCnpjUnicoNaContratacao::normalizarCnpj((string) ($entrada['cnpj'] ?? ''));
        $razao = trim((string) ($entrada['razao_social'] ?? ''));
        $cidade = trim((string) ($entrada['cidade'] ?? '')) ?: null;
        $uf = trim((string) ($entrada['uf'] ?? '')) ?: null;

        if ($cnpj === '' && $razao === '') {
            return $this->respostaVazia();
        }

        $acc = [
            'cnpj' => $cnpj !== '' ? $cnpj : null,
            'razao_social' => $razao !== '' ? $razao : null,
            'telefone' => $this->nullableString($entrada['telefone'] ?? null),
            'email' => $this->nullableString($entrada['email'] ?? null),
            'vendedor' => $this->nullableString($entrada['vendedor'] ?? null),
            'cidade' => $cidade,
            'uf' => $uf,
            'site' => null,
            'instagram' => null,
            'linkedin' => null,
            'facebook' => null,
        ];
        $fontes = [];

        if ($cnpj !== '' && FornecedorCnpjUnicoNaContratacao::cnpjValido($cnpj)) {
            $catalogo = $this->catalogoRepository->findAtivoByCnpj($usuario->tenant_id, $cnpj);
            if ($catalogo !== null) {
                $acc = $this->mesclar($acc, $catalogo);
                $fontes[] = 'catalogo_tenant';
            }

            $historico = $this->historicoRepository->findByCnpj($contratacao, $usuario->tenant_id, $cnpj);
            if ($historico !== null) {
                $acc = $this->mesclar($acc, $historico);
                $fontes[] = 'historico_tenant';
            }

            $brasil = $this->brasilApi->consultar($cnpj);
            if ($brasil !== null) {
                $acc = $this->mesclar($acc, $brasil);
                $fontes[] = 'brasil_api';
            }
        }

        $faltandoContato = ($acc['telefone'] ?? null) === null
            || ($acc['email'] ?? null) === null
            || ($acc['cnpj'] ?? null) === null
            || ($acc['site'] ?? null) === null;

        if ($faltandoContato && ($razao !== '' || $cnpj !== '')) {
            $web = $this->webClient->enriquecer([
                'cnpj' => $acc['cnpj'] ?? $cnpj,
                'razao_social' => $acc['razao_social'] ?? $razao,
                'cidade' => $acc['cidade'] ?? $cidade,
                'uf' => $acc['uf'] ?? $uf,
            ]);

            if ($web !== []) {
                $acc = $this->mesclar($acc, $web);
                $fontes[] = 'web';
            }

            // Se a web achou CNPJ e ainda não consultamos BrasilAPI, completa razão social oficial
            $cnpjWeb = FornecedorCnpjUnicoNaContratacao::normalizarCnpj((string) ($acc['cnpj'] ?? ''));
            if (
                $cnpjWeb !== ''
                && FornecedorCnpjUnicoNaContratacao::cnpjValido($cnpjWeb)
                && ! in_array('brasil_api', $fontes, true)
            ) {
                $brasil = $this->brasilApi->consultar($cnpjWeb);
                if ($brasil !== null) {
                    $acc = $this->mesclar($acc, $brasil);
                    $fontes[] = 'brasil_api';
                }
            }
        }

        $campos = array_values(array_filter([
            ($acc['cnpj'] ?? null) ? 'cnpj' : null,
            ($acc['razao_social'] ?? null) ? 'razao_social' : null,
            ($acc['telefone'] ?? null) ? 'telefone' : null,
            ($acc['email'] ?? null) ? 'email' : null,
            ($acc['vendedor'] ?? null) ? 'vendedor' : null,
            ($acc['site'] ?? null) ? 'site' : null,
            ($acc['instagram'] ?? null) ? 'instagram' : null,
            ($acc['linkedin'] ?? null) ? 'linkedin' : null,
            ($acc['facebook'] ?? null) ? 'facebook' : null,
        ]));

        $encontrado = $campos !== [] && (
            ($acc['cnpj'] ?? null) !== null
            || ($acc['telefone'] ?? null) !== null
            || ($acc['email'] ?? null) !== null
            || ($acc['site'] ?? null) !== null
            || ($acc['instagram'] ?? null) !== null
            || count($fontes) > 0
        );

        return [
            'encontrado' => $encontrado,
            'fonte' => $this->determinarFonte($fontes),
            'cnpj' => $acc['cnpj'] ?? null,
            'razao_social' => $acc['razao_social'] ?? null,
            'telefone' => $acc['telefone'] ?? null,
            'email' => $acc['email'] ?? null,
            'vendedor' => $acc['vendedor'] ?? null,
            'cidade' => $acc['cidade'] ?? null,
            'uf' => $acc['uf'] ?? null,
            'site' => $acc['site'] ?? null,
            'instagram' => $acc['instagram'] ?? null,
            'linkedin' => $acc['linkedin'] ?? null,
            'facebook' => $acc['facebook'] ?? null,
            'campos_preenchidos' => $campos,
            'aviso' => $encontrado
                ? 'Dados localizados automaticamente. Revise antes de cadastrar.'
                : 'Não encontramos dados públicos adicionais. Preencha manualmente.',
        ];
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function mesclar(array $base, array $extra): array
    {
        foreach ([
            'cnpj', 'razao_social', 'telefone', 'email', 'vendedor',
            'cidade', 'uf', 'site', 'instagram', 'linkedin', 'facebook',
        ] as $campo) {
            $atual = $base[$campo] ?? null;
            $novo = $extra[$campo] ?? null;

            if (($atual === null || $atual === '') && $novo !== null && $novo !== '') {
                if ($campo === 'cnpj') {
                    $digits = FornecedorCnpjUnicoNaContratacao::normalizarCnpj((string) $novo);
                    $base[$campo] = $digits !== '' ? $digits : $atual;
                } elseif ($campo === 'telefone') {
                    $digits = preg_replace('/\D+/', '', (string) $novo) ?? '';
                    $base[$campo] = $digits !== '' ? $digits : $atual;
                } elseif ($campo === 'email') {
                    $base[$campo] = strtolower(trim((string) $novo));
                } else {
                    $base[$campo] = is_string($novo) ? trim($novo) : $novo;
                }
            }
        }

        // Razão social oficial da BrasilAPI tem prioridade se a atual parecer hostname/fantasia curta
        if (
            isset($extra['fonte'])
            && $extra['fonte'] === 'brasil_api'
            && ! empty($extra['razao_social'])
            && ! empty($base['razao_social'])
        ) {
            $atual = (string) $base['razao_social'];
            $oficial = trim((string) $extra['razao_social']);
            if ($oficial !== '' && (mb_strlen($atual) < 8 || ! str_contains($atual, ' '))) {
                $base['razao_social'] = $oficial;
            }
        }

        return $base;
    }

    /**
     * @param list<string> $fontes
     */
    private function determinarFonte(array $fontes): string
    {
        $fontes = array_values(array_unique($fontes));
        if ($fontes === []) {
            return 'nenhuma';
        }
        if (count($fontes) === 1) {
            return $fontes[0];
        }

        return 'hibrido';
    }

    /**
     * @return array<string, mixed>
     */
    private function respostaVazia(): array
    {
        return [
            'encontrado' => false,
            'fonte' => 'nenhuma',
            'cnpj' => null,
            'razao_social' => null,
            'telefone' => null,
            'email' => null,
            'vendedor' => null,
            'cidade' => null,
            'uf' => null,
            'site' => null,
            'instagram' => null,
            'linkedin' => null,
            'facebook' => null,
            'campos_preenchidos' => [],
            'aviso' => 'Informe CNPJ ou razão social para buscar dados.',
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }
}

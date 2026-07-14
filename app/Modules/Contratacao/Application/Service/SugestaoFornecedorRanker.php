<?php

namespace App\Modules\Contratacao\Application\Service;

use App\Modules\Contratacao\Domain\Policies\FornecedorCnpjUnicoNaContratacao;

final class SugestaoFornecedorRanker
{
  private const PESO_ORIGEM = [
    'historico_tenant' => 100,
    'catalogo_tenant' => 80,
    'ia_externa' => 60,
  ];

  /**
   * @param list<array<string, mixed>> $candidatos
   * @param array<string, mixed> $contexto
   * @param list<string> $cnpjsExcluir
   * @return list<array<string, mixed>>
   */
  public function ranquear(array $candidatos, array $contexto, array $cnpjsExcluir, int $limite): array
  {
    $excluir = array_flip($cnpjsExcluir);
    $melhorPorChave = [];

    foreach ($candidatos as $candidato) {
      $cnpjNorm = FornecedorCnpjUnicoNaContratacao::normalizarCnpj((string) ($candidato['cnpj'] ?? ''));
      $razao = trim((string) ($candidato['razao_social'] ?? ''));
      if ($cnpjNorm === '' && $razao === '') {
        continue;
      }
      if ($cnpjNorm !== '' && isset($excluir[$cnpjNorm])) {
        continue;
      }

      $chave = $cnpjNorm !== ''
        ? $cnpjNorm
        : 'nome:'.mb_strtolower($razao);

      $score = $this->calcularScore($candidato, $contexto);
      $item = $this->serializarSugestao($candidato, $score, $cnpjsExcluir);

      if (! isset($melhorPorChave[$chave]) || $melhorPorChave[$chave]['score'] < $score) {
        $melhorPorChave[$chave] = $item;
      }
    }

    $ranqueados = array_values($melhorPorChave);
    usort($ranqueados, fn (array $a, array $b) => $b['score'] <=> $a['score']);

    $resultado = [];
    foreach (array_slice($ranqueados, 0, $limite) as $index => $item) {
      $item['rank'] = $index + 1;
      $resultado[] = $item;
    }

    return $resultado;
  }

  /**
   * @param array<string, mixed> $candidato
   * @param array<string, mixed> $contexto
   */
  private function calcularScore(array $candidato, array $contexto): float
  {
    $origem = (string) ($candidato['origem'] ?? 'catalogo_tenant');
    $score = (float) (self::PESO_ORIGEM[$origem] ?? 50);

    $categoriaContexto = mb_strtolower(trim((string) ($contexto['categoria_servico'] ?? '')));
    $categoriaCandidato = mb_strtolower(trim((string) ($candidato['categoria_servico'] ?? '')));
    if ($categoriaContexto !== '' && $categoriaCandidato !== '' && $categoriaContexto === $categoriaCandidato) {
      $score += 30;
    }

    $localTokens = $contexto['local_tokens'] ?? [];
    $candidatoLocal = mb_strtolower(trim((string) ($candidato['local'] ?? '')));
    $cidade = mb_strtolower(trim((string) ($candidato['cidade'] ?? '')));
    $uf = mb_strtolower(trim((string) ($candidato['uf'] ?? '')));

    foreach ($localTokens as $token) {
      if ($token !== '' && (
        str_contains($candidatoLocal, $token) ||
        str_contains($cidade, $token) ||
        ($uf !== '' && $token === $uf)
      )) {
        $score += 20;
        break;
      }
    }

    $termoTokens = $contexto['termo_tokens'] ?? [];
    $textoCandidato = mb_strtolower(
      (string) ($candidato['razao_social'] ?? '') . ' ' .
      (string) ($candidato['categoria_servico'] ?? '') . ' ' .
      (string) ($candidato['motivo'] ?? ''),
    );
    $matchesTermo = 0;
    foreach ($termoTokens as $token) {
      if ($token !== '' && str_contains($textoCandidato, $token)) {
        $matchesTermo++;
      }
    }
    if ($matchesTermo > 0) {
      $score += min(15, $matchesTermo * 5);
    }

    $participacoes = (int) ($candidato['participacoes'] ?? 0);
    if ($participacoes > 0) {
      $score += min(15, $participacoes * 5);
    }

    return round(min($score, 200), 2);
  }

  /**
   * @param array<string, mixed> $candidato
   * @param list<string> $cnpjsCadastrados
   * @return array<string, mixed>
   */
  private function serializarSugestao(array $candidato, float $score, array $cnpjsCadastrados): array
  {
    $cnpjNorm = FornecedorCnpjUnicoNaContratacao::normalizarCnpj((string) ($candidato['cnpj'] ?? ''));
    $razao = (string) ($candidato['razao_social'] ?? '');
    $jaCadastrado = $cnpjNorm !== '' && in_array($cnpjNorm, $cnpjsCadastrados, true);
    $idSeed = $cnpjNorm !== '' ? $cnpjNorm : mb_strtolower(trim($razao));

    $motivo = (string) ($candidato['motivo'] ?? $this->motivoPadrao($candidato));

    return [
      'id' => 'sug-' . substr(md5($idSeed . ($candidato['origem'] ?? '')), 0, 12),
      'rank' => 0,
      'score' => $score,
      'origem' => $candidato['origem'] ?? 'catalogo_tenant',
      'cnpj' => $candidato['cnpj'] ?? '',
      'razao_social' => $razao,
      'telefone' => $candidato['telefone'] ?? null,
      'email' => $candidato['email'] ?? null,
      'cidade' => $candidato['cidade'] ?? null,
      'uf' => $candidato['uf'] ?? null,
      'site' => $candidato['site'] ?? null,
      'instagram' => $candidato['instagram'] ?? null,
      'linkedin' => $candidato['linkedin'] ?? null,
      'facebook' => $candidato['facebook'] ?? null,
      'motivo' => $motivo,
      'ja_cadastrado' => $jaCadastrado,
    ];
  }

  /**
   * @param array<string, mixed> $candidato
   */
  private function motivoPadrao(array $candidato): string
  {
    return match ($candidato['origem'] ?? '') {
      'historico_tenant' => sprintf(
        'Fornecedor já utilizado em %d contratação(ões) anteriores neste cliente.',
        (int) ($candidato['participacoes'] ?? 1),
      ),
      'catalogo_tenant' => 'Fornecedor do catálogo preferencial do cliente.',
      'ia_externa' => 'Sugestão complementar via integração externa.',
      default => 'Sugestão baseada no perfil desta contratação.',
    };
  }
}

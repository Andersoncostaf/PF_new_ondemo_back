<?php

namespace App\Modules\Contratacao\Application\Service;

use App\Models\Contratacao;
use App\Modules\Contratacao\Domain\Policies\FornecedorCnpjUnicoNaContratacao;

final class SugestaoFornecedorContextBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(Contratacao $contratacao): array
    {
        $contratacao->loadMissing(['qqpItens', 'fornecedores']);

        $qqpResumo = $contratacao->qqpItens
            ->pluck('descricao')
            ->filter()
            ->values()
            ->all();

        $cnpjsCadastrados = $contratacao->fornecedores
            ->map(fn ($f) => FornecedorCnpjUnicoNaContratacao::normalizarCnpj($f->cnpj))
            ->filter()
            ->values()
            ->all();

        $localEfetivo = $this->localEfetivo($contratacao);
        $localTokens = $this->tokenizarLocal($localEfetivo);

        return [
            'contratacao_uuid' => $contratacao->uuid,
            'titulo' => $contratacao->titulo,
            'categoria_servico' => $contratacao->categoria_servico,
            'local' => $localEfetivo !== '' ? $localEfetivo : $contratacao->local,
            'empresa_endereco' => $contratacao->empresa_endereco,
            'local_tokens' => $localTokens,
            'termo_referencia' => $contratacao->termo_referencia,
            'termo_tokens' => $this->tokenizarTexto((string) ($contratacao->termo_referencia ?? '')),
            'qqp_resumo' => $qqpResumo,
            'cnpjs_cadastrados' => $cnpjsCadastrados,
            'contexto_hash' => $this->gerarHash($contratacao, $qqpResumo, $cnpjsCadastrados),
        ];
    }

    /**
     * Prioriza o campo "local" da contratação; se vazio, usa o endereço da empresa.
     */
    public function localEfetivo(Contratacao $contratacao): string
    {
        $local = trim((string) ($contratacao->local ?? ''));
        if ($local !== '') {
            return $local;
        }

        return trim((string) ($contratacao->empresa_endereco ?? ''));
    }

    /**
     * @param list<string> $qqpResumo
     * @param list<string> $cnpjsCadastrados
     */
    public function gerarHash(Contratacao $contratacao, array $qqpResumo, array $cnpjsCadastrados): string
    {
        $payload = json_encode([
            'categoria_servico' => $contratacao->categoria_servico,
            'local' => $this->localEfetivo($contratacao),
            'empresa_endereco' => $contratacao->empresa_endereco,
            'termo_referencia' => $contratacao->termo_referencia,
            'qqp' => $qqpResumo,
            'cnpjs' => $cnpjsCadastrados,
            'provider' => 'v2-web-llm',
        ], JSON_THROW_ON_ERROR);

        return hash('sha256', $payload);
    }

    /**
     * @return list<string>
     */
    public function tokenizarLocal(string $local): array
    {
        $tokens = [];
        $normalizado = mb_strtolower(trim($local));

        if ($normalizado === '') {
            return [];
        }

        if (preg_match('/\b([A-Z]{2})\b/i', $local, $ufMatch)) {
            $tokens[] = mb_strtolower($ufMatch[1]);
        }

        $partes = preg_split('/\s*[-,\/]\s*/', $normalizado) ?: [];
        foreach ($partes as $parte) {
            $parte = trim($parte);
            if (strlen($parte) >= 3) {
                $tokens[] = $parte;
            }
        }

        // Tokens por palavra para endereços como "Trav Vilela"
        $palavras = preg_split('/\W+/u', $normalizado, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($palavras as $palavra) {
            if (mb_strlen($palavra) >= 3) {
                $tokens[] = $palavra;
            }
        }

        return array_values(array_unique($tokens));
    }

    /**
     * @return list<string>
     */
    public function tokenizarTexto(string $texto): array
    {
        $normalizado = mb_strtolower($texto);
        $palavras = preg_split('/\W+/u', $normalizado, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter(
            $palavras,
            fn (string $p) => mb_strlen($p) >= 4,
        )));
    }
}

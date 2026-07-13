<?php

namespace App\Modules\Contratacao\Infrastructure\N8n;

use App\Modules\Contratacao\Application\Port\Out\N8nSugestaoFornecedorPort;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente OpenAI-compatível para sugerir fornecedores quando n8n não está configurado.
 */
final class LlmSugestaoFornecedorClient implements N8nSugestaoFornecedorPort
{
    public function solicitarSugestoes(
        string $tenantId,
        string $contratacaoUuid,
        array $brief,
    ): array {
        $apiKey = (string) config('contratacao.sugestao.llm.api_key', '');
        if ($apiKey === '') {
            return [];
        }

        $baseUrl = rtrim((string) config('contratacao.sugestao.llm.base_url', 'https://api.openai.com/v1'), '/');
        $model = (string) config('contratacao.sugestao.llm.model', 'gpt-4o-mini');
        $timeout = (int) config('contratacao.sugestao.llm.timeout_seconds', 45);
        $slots = max(1, min(5, (int) ($brief['slots_restantes'] ?? 3)));

        $prompt = $this->montarPrompt($brief, $slots);

        try {
            $response = Http::timeout($timeout)
                ->withToken($apiKey)
                ->acceptJson()
                ->post("{$baseUrl}/chat/completions", [
                    'model' => $model,
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Você é um assistente de sourcing B2B no Brasil. Retorne APENAS JSON válido.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('llm sugestao fornecedor falhou', [
                    'status' => $response->status(),
                    'contratacao_uuid' => $contratacaoUuid,
                ]);

                return [];
            }

            $content = data_get($response->json(), 'choices.0.message.content');
            if (! is_string($content) || trim($content) === '') {
                return [];
            }

            return $this->parseSugestoes($content, $slots);
        } catch (\Throwable $e) {
            Log::warning('llm sugestao fornecedor exception', [
                'message' => $e->getMessage(),
                'contratacao_uuid' => $contratacaoUuid,
            ]);

            return [];
        }
    }

    /**
     * @param array<string, mixed> $brief
     */
    private function montarPrompt(array $brief, int $slots): string
    {
        $briefJson = json_encode($brief, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return <<<PROMPT
Com base no brief abaixo, sugira até {$slots} empresas brasileiras reais que prestem o serviço descrito,
preferencialmente próximas ao endereço/local indicado (use internet knowledge e empresas conhecidas da região).

Regras:
- Retorne JSON no formato: {"sugestoes":[{"cnpj":"","razao_social":"","telefone":"","email":"","cidade":"","uf":"","motivo":""}]}
- razao_social e motivo são obrigatórios (motivo em português).
- Não invente CNPJs: se não tiver certeza, deixe cnpj vazio.
- Não repetir CNPJs em brief.cnpjs_excluir.
- Prefira fornecedores próximos ao local/endereço do brief.

Brief:
{$briefJson}
PROMPT;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseSugestoes(string $content, int $slots): array
    {
        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            if (preg_match('/\{.*\}/s', $content, $m)) {
                $decoded = json_decode($m[0], true);
            }
        }

        if (! is_array($decoded)) {
            return [];
        }

        $items = $decoded['sugestoes'] ?? $decoded;
        if (! is_array($items)) {
            return [];
        }

        $resultado = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $nome = trim((string) ($item['razao_social'] ?? ''));
            if ($nome === '') {
                continue;
            }
            $resultado[] = [
                'cnpj' => (string) ($item['cnpj'] ?? ''),
                'razao_social' => $nome,
                'telefone' => $item['telefone'] ?? null,
                'email' => $item['email'] ?? null,
                'cidade' => $item['cidade'] ?? null,
                'uf' => $item['uf'] ?? null,
                'motivo' => (string) ($item['motivo'] ?? 'Sugestão via IA com base no serviço e local da contratação.'),
            ];
            if (count($resultado) >= $slots) {
                break;
            }
        }

        return $resultado;
    }
}

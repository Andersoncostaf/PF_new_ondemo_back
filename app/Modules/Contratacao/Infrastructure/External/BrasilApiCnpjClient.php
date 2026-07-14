<?php

namespace App\Modules\Contratacao\Infrastructure\External;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Consulta pública de CNPJ via BrasilAPI (Receita Federal).
 */
final class BrasilApiCnpjClient
{
    /**
     * @return array<string, mixed>|null
     */
    public function consultar(string $cnpj): ?array
    {
        if (! config('contratacao.enrichment.brasil_api.enabled', true)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $cnpj) ?? '';
        if (strlen($digits) !== 14) {
            return null;
        }

        $timeout = (int) config('contratacao.enrichment.brasil_api.timeout_seconds', 12);
        $baseUrl = rtrim((string) config(
            'contratacao.enrichment.brasil_api.base_url',
            'https://brasilapi.com.br/api/cnpj/v1',
        ), '/');

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->get("{$baseUrl}/{$digits}");

            if ($response->status() === 404) {
                return null;
            }

            if (! $response->successful()) {
                Log::warning('brasilapi cnpj falhou', [
                    'status' => $response->status(),
                    'cnpj' => $digits,
                ]);

                return null;
            }

            /** @var array<string, mixed> $data */
            $data = $response->json() ?? [];

            return $this->mapear($data, $digits);
        } catch (\Throwable $e) {
            Log::warning('brasilapi cnpj exception', [
                'message' => $e->getMessage(),
                'cnpj' => $digits,
            ]);

            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function mapear(array $data, string $cnpj): array
    {
        $telefone = $this->montarTelefone(
            (string) ($data['ddd_telefone_1'] ?? ''),
            (string) ($data['ddd_telefone_2'] ?? ''),
        );

        $email = trim((string) ($data['email'] ?? ''));
        $vendedor = $this->primeiroSocio($data['qsa'] ?? []);

        return [
            'cnpj' => $cnpj,
            'razao_social' => trim((string) ($data['razao_social'] ?? $data['nome_fantasia'] ?? '')),
            'telefone' => $telefone,
            'email' => $email !== '' ? strtolower($email) : null,
            'vendedor' => $vendedor,
            'cidade' => trim((string) ($data['municipio'] ?? '')) ?: null,
            'uf' => trim((string) ($data['uf'] ?? '')) ?: null,
            'fonte' => 'brasil_api',
        ];
    }

    private function montarTelefone(string $primeiro, string $segundo): ?string
    {
        foreach ([$primeiro, $segundo] as $raw) {
            $digits = preg_replace('/\D+/', '', $raw) ?? '';
            if (strlen($digits) >= 10 && strlen($digits) <= 11) {
                return $digits;
            }
            // BrasilAPI às vezes devolve "9132123456" sem formatação, ou DDD+número juntos
            if (strlen($digits) === 10 || strlen($digits) === 11) {
                return $digits;
            }
        }

        // Alguns payloads vêm como "91 3212-3456"
        $combo = preg_replace('/\D+/', '', $primeiro.$segundo) ?? '';
        if (strlen($combo) === 10 || strlen($combo) === 11) {
            return $combo;
        }

        return null;
    }

    /**
     * @param mixed $qsa
     */
    private function primeiroSocio(mixed $qsa): ?string
    {
        if (! is_array($qsa) || $qsa === []) {
            return null;
        }

        $primeiro = $qsa[0] ?? null;
        if (! is_array($primeiro)) {
            return null;
        }

        $nome = trim((string) ($primeiro['nome_socio'] ?? $primeiro['nome'] ?? ''));

        return $nome !== '' ? mb_substr($nome, 0, 255) : null;
    }
}

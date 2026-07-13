<?php

namespace App\Modules\Contratacao\Infrastructure\N8n;

use App\Modules\Contratacao\Application\Port\Out\N8nSugestaoFornecedorPort;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class N8nSugestaoFornecedorClient implements N8nSugestaoFornecedorPort
{
  public function solicitarSugestoes(
    string $tenantId,
    string $contratacaoUuid,
    array $brief,
  ): array {
    if (! config('contratacao.sugestao.n8n.enabled', false)) {
      return [];
    }

    $url = (string) config('contratacao.sugestao.n8n.webhook_url', '');
    if ($url === '') {
      return [];
    }

    $payload = [
      'event' => 'contratacao.sugestao_fornecedores',
      'tenant_id' => $tenantId,
      'contratacao_uuid' => $contratacaoUuid,
      'brief' => $brief,
    ];

    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $secret = (string) config('contratacao.sugestao.n8n.webhook_secret', '');
    $signature = $secret !== '' ? hash_hmac('sha256', $body, $secret) : '';

    $timeout = (int) config('contratacao.sugestao.n8n.timeout_seconds', 15);

    try {
      $response = Http::timeout($timeout)
        ->withHeaders(array_filter([
          'Content-Type' => 'application/json',
          'X-Portal-Signature' => $signature !== '' ? $signature : null,
        ]))
        ->withBody($body, 'application/json')
        ->post($url);

      if (! $response->successful()) {
        Log::warning('n8n sugestao fornecedor falhou', [
          'status' => $response->status(),
          'contratacao_uuid' => $contratacaoUuid,
        ]);

        return [];
      }

      $data = $response->json();
      if (! is_array($data)) {
        return [];
      }

      $sugestoes = $data['sugestoes'] ?? [];

      return is_array($sugestoes) ? array_values($sugestoes) : [];
    } catch (\Throwable $e) {
      Log::warning('n8n sugestao fornecedor exception', [
        'message' => $e->getMessage(),
        'contratacao_uuid' => $contratacaoUuid,
      ]);

      return [];
    }
  }
}

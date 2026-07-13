<?php

namespace App\Modules\Contratacao\Infrastructure\N8n;

use App\Modules\Contratacao\Application\Port\Out\N8nSugestaoFornecedorPort;

final class NullN8nSugestaoFornecedorClient implements N8nSugestaoFornecedorPort
{
  public function solicitarSugestoes(
    string $tenantId,
    string $contratacaoUuid,
    array $brief,
  ): array {
    return [];
  }
}

<?php

namespace App\Modules\Contratacao\Application\Port\Out;

interface N8nSugestaoFornecedorPort
{
    /**
     * @param array<string, mixed> $brief
     * @return list<array<string, mixed>>
     */
    public function solicitarSugestoes(
        string $tenantId,
        string $contratacaoUuid,
        array $brief,
    ): array;
}

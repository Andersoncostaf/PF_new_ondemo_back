<?php

namespace App\Modules\Contratacao\Application\Port\Out;

interface FornecedorCatalogoRepositoryPort
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listarAtivosPorTenant(string $tenantId): array;

    /**
     * @return array<string, mixed>|null
     */
    public function findAtivoByCnpj(string $tenantId, string $cnpj): ?array;
}

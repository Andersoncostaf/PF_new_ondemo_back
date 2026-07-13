<?php

namespace App\Modules\Contratacao\Application\Port\Out;

use App\Models\Contratacao;

interface FornecedorHistoricoRepositoryPort
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listarCandidatos(Contratacao $contratacao, string $tenantId): array;

    /**
     * @return array<string, mixed>|null
     */
    public function findByCnpj(Contratacao $contratacao, string $tenantId, string $cnpj): ?array;
}

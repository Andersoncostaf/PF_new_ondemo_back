<?php

namespace App\Modules\Identidade\Application\Port\Out;

use App\Models\Tenant;

interface TenantRepositoryPort
{
    public function existsByCnpj(string $cnpj): bool;

    public function existsBySlug(string $slug): bool;

    public function findBySlug(string $slug): ?Tenant;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Tenant;
}

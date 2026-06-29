<?php

namespace App\Modules\Identidade\Infrastructure\Persistence;

use App\Models\Tenant;
use App\Modules\Identidade\Application\Port\Out\TenantRepositoryPort;

final class EloquentTenantRepository implements TenantRepositoryPort
{
    public function existsByCnpj(string $cnpj): bool
    {
        return Tenant::query()->where('cnpj', $cnpj)->exists();
    }

    public function existsBySlug(string $slug): bool
    {
        return Tenant::query()->where('slug', $slug)->exists();
    }

    public function findBySlug(string $slug): ?Tenant
    {
        return Tenant::query()->where('slug', $slug)->first();
    }

    public function create(array $attributes): Tenant
    {
        return Tenant::query()->create($attributes);
    }
}

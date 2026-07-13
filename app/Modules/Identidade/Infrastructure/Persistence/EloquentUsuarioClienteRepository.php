<?php

namespace App\Modules\Identidade\Infrastructure\Persistence;

use App\Models\UsuarioCliente;
use App\Modules\Identidade\Application\Port\Out\UsuarioClienteRepositoryPort;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class EloquentUsuarioClienteRepository implements UsuarioClienteRepositoryPort
{
    public function existsByTenantAndEmail(string $tenantId, string $email): bool
    {
        return UsuarioCliente::query()
            ->where('tenant_id', $tenantId)
            ->where('email', Str::lower($email))
            ->exists();
    }

    public function findByTenantAndEmail(string $tenantId, string $email): ?UsuarioCliente
    {
        return UsuarioCliente::query()
            ->where('tenant_id', $tenantId)
            ->where('email', Str::lower($email))
            ->first();
    }

    public function findById(string $id): ?UsuarioCliente
    {
        return UsuarioCliente::query()
            ->with('tenant')
            ->find($id);
    }

    public function findByUuidForTenant(string $uuid, string $tenantId): ?UsuarioCliente
    {
        return UsuarioCliente::query()
            ->where('id', $uuid)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function listByTenant(string $tenantId): Collection
    {
        return UsuarioCliente::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('nome')
            ->get();
    }

    public function create(array $attributes): UsuarioCliente
    {
        if (isset($attributes['email'])) {
            $attributes['email'] = Str::lower($attributes['email']);
        }

        return UsuarioCliente::query()->create($attributes);
    }

    public function update(UsuarioCliente $usuario, array $attributes): UsuarioCliente
    {
        if (isset($attributes['email'])) {
            $attributes['email'] = Str::lower($attributes['email']);
        }

        $usuario->fill($attributes);
        $usuario->save();

        return $usuario->fresh();
    }
}

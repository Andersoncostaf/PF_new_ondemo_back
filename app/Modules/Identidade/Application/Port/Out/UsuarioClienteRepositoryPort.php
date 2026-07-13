<?php

namespace App\Modules\Identidade\Application\Port\Out;

use App\Models\UsuarioCliente;
use Illuminate\Support\Collection;

interface UsuarioClienteRepositoryPort
{
    public function existsByTenantAndEmail(string $tenantId, string $email): bool;

    public function findByTenantAndEmail(string $tenantId, string $email): ?UsuarioCliente;

    public function findById(string $id): ?UsuarioCliente;

    public function findByUuidForTenant(string $uuid, string $tenantId): ?UsuarioCliente;

    /**
     * @return Collection<int, UsuarioCliente>
     */
    public function listByTenant(string $tenantId): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): UsuarioCliente;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(UsuarioCliente $usuario, array $attributes): UsuarioCliente;
}

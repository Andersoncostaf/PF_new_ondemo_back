<?php

namespace App\Modules\Identidade\Application\Port\Out;

use App\Models\UsuarioCliente;

interface UsuarioClienteRepositoryPort
{
    public function existsByTenantAndEmail(string $tenantId, string $email): bool;

    public function findByTenantAndEmail(string $tenantId, string $email): ?UsuarioCliente;

    public function findById(string $id): ?UsuarioCliente;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): UsuarioCliente;
}

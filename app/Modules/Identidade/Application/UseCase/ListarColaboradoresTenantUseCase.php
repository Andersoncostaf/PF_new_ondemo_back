<?php

namespace App\Modules\Identidade\Application\UseCase;

use App\Models\UsuarioCliente;
use App\Modules\Identidade\Application\Port\Out\UsuarioClienteRepositoryPort;

final class ListarColaboradoresTenantUseCase
{
    public function __construct(
        private UsuarioClienteRepositoryPort $repository,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function executar(UsuarioCliente $admin): array
    {
        return $this->repository->listByTenant($admin->tenant_id)
            ->map(fn (UsuarioCliente $u) => [
                'id' => $u->id,
                'nome' => $u->nome,
                'email' => $u->email,
                'cargo' => $u->cargo,
                'perfil' => $u->perfil,
                'status' => $u->status,
                'created_at' => $u->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}

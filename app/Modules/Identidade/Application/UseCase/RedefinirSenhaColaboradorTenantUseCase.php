<?php

namespace App\Modules\Identidade\Application\UseCase;

use App\Models\UsuarioCliente;
use App\Modules\Identidade\Application\Port\Out\UsuarioClienteRepositoryPort;
use App\Modules\Identidade\Domain\Exceptions\ColaboradorNaoEncontradoException;

final class RedefinirSenhaColaboradorTenantUseCase
{
    public function __construct(
        private UsuarioClienteRepositoryPort $repository,
    ) {}

    public function executar(UsuarioCliente $admin, string $colaboradorUuid, string $password): void
    {
        $colaborador = $this->repository->findByUuidForTenant($colaboradorUuid, $admin->tenant_id);

        if ($colaborador === null) {
            throw new ColaboradorNaoEncontradoException;
        }

        $this->repository->update($colaborador, ['password' => $password]);
    }
}

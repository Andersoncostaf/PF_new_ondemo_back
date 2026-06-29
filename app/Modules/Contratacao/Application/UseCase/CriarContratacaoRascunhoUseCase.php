<?php

namespace App\Modules\Contratacao\Application\UseCase;

use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Application\DTO\ContratacaoInput;
use App\Modules\Contratacao\Application\DTO\ContratacaoOutput;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoRepositoryPort;

final class CriarContratacaoRascunhoUseCase
{
    public function __construct(
        private ContratacaoRepositoryPort $repository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function executar(UsuarioCliente $usuario, ContratacaoInput $input): array
    {
        $contratacao = $this->repository->createRascunho(
            $usuario->tenant_id,
            $usuario->id,
            $input,
        );

        return ContratacaoOutput::fromModel($contratacao);
    }
}

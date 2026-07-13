<?php

namespace App\Modules\Contratacao\Application\UseCase;

use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoRepositoryPort;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoNaoEditavelException;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoNaoEncontradaException;
use App\Modules\Contratacao\Domain\Policies\ContratacaoEditavelEmRascunho;
use App\Modules\Contratacao\Domain\Policies\ContratacaoPertenceAoTenant;

final class ExcluirContratacaoRascunhoUseCase
{
    public function __construct(
        private ContratacaoRepositoryPort $repository,
    ) {}

    public function executar(UsuarioCliente $usuario, string $uuid): void
    {
        $contratacao = $this->repository->findByUuidForTenant($uuid, $usuario->tenant_id);

        if ($contratacao === null || ! ContratacaoPertenceAoTenant::check($contratacao, $usuario->tenant_id)) {
            throw new ContratacaoNaoEncontradaException;
        }

        if (! ContratacaoEditavelEmRascunho::check($contratacao)) {
            throw new ContratacaoNaoEditavelException;
        }

        $this->repository->deleteRascunho($contratacao);
    }
}

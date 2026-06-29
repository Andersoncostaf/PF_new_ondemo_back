<?php

namespace App\Modules\Contratacao\Application\UseCase;

use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Application\DTO\ContratacaoOutput;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoRepositoryPort;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoIncompletaException;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoNaoEditavelException;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoNaoEncontradaException;
use App\Modules\Contratacao\Domain\Policies\ContratacaoCompletaParaSubmissao;
use App\Modules\Contratacao\Domain\Policies\ContratacaoEditavelEmRascunho;
use App\Modules\Contratacao\Domain\Policies\ContratacaoPertenceAoTenant;

final class SubmeterContratacaoUseCase
{
    public function __construct(
        private ContratacaoRepositoryPort $repository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function executar(UsuarioCliente $usuario, string $uuid): array
    {
        $contratacao = $this->repository->findByUuidForTenant($uuid, $usuario->tenant_id);

        if ($contratacao === null || ! ContratacaoPertenceAoTenant::check($contratacao, $usuario->tenant_id)) {
            throw new ContratacaoNaoEncontradaException;
        }

        if (! ContratacaoEditavelEmRascunho::check($contratacao)) {
            throw new ContratacaoNaoEditavelException;
        }

        if (! ContratacaoCompletaParaSubmissao::check($contratacao)) {
            $missing = ContratacaoCompletaParaSubmissao::missingFields($contratacao);
            throw new ContratacaoIncompletaException(
                'Campos obrigatórios pendentes: '.implode(', ', $missing).'.'
            );
        }

        $contratacao = $this->repository->submeter($contratacao);

        return ContratacaoOutput::fromModel($contratacao);
    }
}

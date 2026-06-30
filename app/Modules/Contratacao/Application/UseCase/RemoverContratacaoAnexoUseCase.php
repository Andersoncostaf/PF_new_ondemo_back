<?php

namespace App\Modules\Contratacao\Application\UseCase;

use App\Models\ContratacaoAnexo;
use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoRepositoryPort;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoNaoEditavelException;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoNaoEncontradaException;
use App\Modules\Contratacao\Domain\Policies\ContratacaoEditavelEmRascunho;
use App\Modules\Contratacao\Domain\Policies\ContratacaoPertenceAoTenant;
use App\Modules\Contratacao\Infrastructure\Storage\ContratacaoAnexoStorage;

final class RemoverContratacaoAnexoUseCase
{
    public function __construct(
        private ContratacaoRepositoryPort $repository,
        private ContratacaoAnexoStorage $storage,
    ) {}

    public function executar(UsuarioCliente $usuario, string $uuid, string $anexoId): void
    {
        $contratacao = $this->repository->findByUuidForTenant($uuid, $usuario->tenant_id);

        if ($contratacao === null || ! ContratacaoPertenceAoTenant::check($contratacao, $usuario->tenant_id)) {
            throw new ContratacaoNaoEncontradaException;
        }

        if (! ContratacaoEditavelEmRascunho::check($contratacao)) {
            throw new ContratacaoNaoEditavelException;
        }

        $anexo = ContratacaoAnexo::query()
            ->where('id', $anexoId)
            ->where('contratacao_id', $contratacao->id)
            ->first();

        if ($anexo === null) {
            throw new ContratacaoNaoEncontradaException;
        }

        $this->storage->delete($anexo->storage_path);
        $anexo->delete();
    }
}

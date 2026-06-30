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
use Illuminate\Http\UploadedFile;

final class AdicionarContratacaoAnexoUseCase
{
    public function __construct(
        private ContratacaoRepositoryPort $repository,
        private ContratacaoAnexoStorage $storage,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function executar(
        UsuarioCliente $usuario,
        string $uuid,
        ?string $descricao,
        UploadedFile $arquivo,
    ): array {
        $contratacao = $this->repository->findByUuidForTenant($uuid, $usuario->tenant_id);

        if ($contratacao === null || ! ContratacaoPertenceAoTenant::check($contratacao, $usuario->tenant_id)) {
            throw new ContratacaoNaoEncontradaException;
        }

        if (! ContratacaoEditavelEmRascunho::check($contratacao)) {
            throw new ContratacaoNaoEditavelException;
        }

        $storagePath = $this->storage->store($usuario->tenant_id, $contratacao->uuid, $arquivo);

        $anexo = ContratacaoAnexo::query()->create([
            'contratacao_id' => $contratacao->id,
            'descricao' => $descricao ?? '',
            'nome_arquivo' => $arquivo->getClientOriginalName(),
            'storage_path' => $storagePath,
            'mime_type' => $arquivo->getClientMimeType(),
            'tamanho_bytes' => $arquivo->getSize() ?: 0,
        ]);

        return [
            'id' => $anexo->id,
            'descricao' => $anexo->descricao,
            'nome_arquivo' => $anexo->nome_arquivo,
            'mime_type' => $anexo->mime_type,
            'tamanho_bytes' => $anexo->tamanho_bytes,
            'created_at' => $anexo->created_at?->toIso8601String(),
        ];
    }
}

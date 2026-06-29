<?php

namespace App\Modules\Contratacao\Application\UseCase;

use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Application\DTO\ContratacaoOutput;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoRepositoryPort;

final class ListarContratacoesTenantUseCase
{
    public function __construct(
        private ContratacaoRepositoryPort $repository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function executar(UsuarioCliente $usuario, int $page = 1): array
    {
        $paginator = $this->repository->listByTenant($usuario->tenant_id, 15, $page);

        return [
            'data' => collect($paginator->items())
                ->map(fn ($item) => ContratacaoOutput::listItem($item))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}

<?php

namespace App\Modules\Contratacao\Application\Service;

use App\Models\Contratacao;
use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Application\DTO\ContratacaoListFilter;
use App\Modules\Contratacao\Application\DTO\ContratacaoOutput;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoRepositoryPort;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoNaoEncontradaException;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoTransicaoInvalidaException;
use App\Modules\Contratacao\Domain\Policies\ContratacaoElegivelParaAssumirVendorList;
use App\Modules\Contratacao\Domain\Policies\ContratacaoPertenceAoTenant;

final class ContratacaoComprasService
{
    public function __construct(
        private ContratacaoRepositoryPort $repository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function listarFila(UsuarioCliente $usuario, ContratacaoListFilter $filter): array
    {
        $paginator = $this->repository->listFilaCompras($usuario->tenant_id, $filter);

        return [
            'data' => collect($paginator->items())->map(fn (Contratacao $c) => ContratacaoOutput::listItem($c))->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function assumirVendorList(UsuarioCliente $usuario, string $uuid): array
    {
        $contratacao = $this->loadOrFail($uuid, $usuario->tenant_id);

        if (! ContratacaoElegivelParaAssumirVendorList::check($contratacao)) {
            throw new ContratacaoTransicaoInvalidaException('Contratação não está aprovada para processamento de Compras.');
        }

        $contratacao = $this->repository->assumirVendorList($contratacao, $usuario->id);

        return ContratacaoOutput::fromModel($contratacao);
    }

    /**
     * @return array<string, mixed>
     */
    public function obterDetalhe(UsuarioCliente $usuario, string $uuid): array
    {
        $contratacao = $this->loadOrFail($uuid, $usuario->tenant_id);

        return ContratacaoOutput::fromModel($contratacao);
    }

    private function loadOrFail(string $uuid, string $tenantId): Contratacao
    {
        $contratacao = $this->repository->findByUuidForTenant($uuid, $tenantId);

        if ($contratacao === null || ! ContratacaoPertenceAoTenant::check($contratacao, $tenantId)) {
            throw new ContratacaoNaoEncontradaException;
        }

        return $contratacao;
    }
}

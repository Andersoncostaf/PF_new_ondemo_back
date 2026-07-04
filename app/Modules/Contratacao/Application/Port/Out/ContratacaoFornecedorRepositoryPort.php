<?php

namespace App\Modules\Contratacao\Application\Port\Out;

use App\Models\Contratacao;
use App\Models\ContratacaoFornecedor;
use Illuminate\Support\Collection;

interface ContratacaoFornecedorRepositoryPort
{
    /**
     * @return Collection<int, ContratacaoFornecedor>
     */
    public function listByContratacao(Contratacao $contratacao): Collection;

    public function findByUuidForContratacao(Contratacao $contratacao, string $fornecedorUuid): ?ContratacaoFornecedor;

    public function countByContratacao(Contratacao $contratacao): int;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(Contratacao $contratacao, string $tenantId, array $attributes): ContratacaoFornecedor;

    public function delete(ContratacaoFornecedor $fornecedor): void;
}

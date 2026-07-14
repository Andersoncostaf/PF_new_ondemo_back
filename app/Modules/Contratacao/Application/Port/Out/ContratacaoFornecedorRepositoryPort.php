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

    public function marcarAceiteParticipacao(ContratacaoFornecedor $fornecedor): ContratacaoFornecedor;

    /**
     * @param array<string, mixed> $dados
     */
    public function updateProposta(ContratacaoFornecedor $fornecedor, array $dados): ContratacaoFornecedor;

    public function definirVencedor(Contratacao $contratacao, ContratacaoFornecedor $vencedor): ContratacaoFornecedor;

    public function updateAberturaStatus(
        ContratacaoFornecedor $fornecedor,
        string $status,
        ?\DateTimeInterface $solicitadaEm = null,
        ?\DateTimeInterface $enviadaEm = null,
        ?\DateTimeInterface $confirmadaEm = null,
    ): ContratacaoFornecedor;

    public function delete(ContratacaoFornecedor $fornecedor): void;
}

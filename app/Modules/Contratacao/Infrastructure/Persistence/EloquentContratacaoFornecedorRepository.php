<?php

namespace App\Modules\Contratacao\Infrastructure\Persistence;

use App\Models\Contratacao;
use App\Models\ContratacaoFornecedor;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoFornecedorRepositoryPort;
use App\Modules\Contratacao\Domain\Policies\FornecedorCnpjUnicoNaContratacao;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class EloquentContratacaoFornecedorRepository implements ContratacaoFornecedorRepositoryPort
{
    public function listByContratacao(Contratacao $contratacao): Collection
    {
        return $contratacao->fornecedores()->orderBy('created_at')->get();
    }

    public function findByUuidForContratacao(Contratacao $contratacao, string $fornecedorUuid): ?ContratacaoFornecedor
    {
        return $contratacao->fornecedores()
            ->where(function ($q) use ($fornecedorUuid) {
                $q->where('uuid', $fornecedorUuid)->orWhere('id', $fornecedorUuid);
            })
            ->first();
    }

    public function countByContratacao(Contratacao $contratacao): int
    {
        return $contratacao->fornecedores()->count();
    }

    public function create(Contratacao $contratacao, string $tenantId, array $attributes): ContratacaoFornecedor
    {
        $cnpj = FornecedorCnpjUnicoNaContratacao::normalizarCnpj((string) ($attributes['cnpj'] ?? ''));

        return ContratacaoFornecedor::query()->create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'contratacao_id' => $contratacao->id,
            'cnpj' => $cnpj,
            'razao_social' => $attributes['razao_social'],
            'telefone' => $attributes['telefone'] ?? null,
            'email' => $attributes['email'] ?? null,
            'vendedor' => $attributes['vendedor'] ?? null,
            'aceite' => false,
            'status_participacao' => 'convidado',
        ]);
    }

    public function delete(ContratacaoFornecedor $fornecedor): void
    {
        $fornecedor->delete();
    }
}

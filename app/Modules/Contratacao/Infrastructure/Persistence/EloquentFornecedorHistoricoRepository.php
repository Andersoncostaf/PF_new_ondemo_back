<?php

namespace App\Modules\Contratacao\Infrastructure\Persistence;

use App\Models\Contratacao;
use App\Models\ContratacaoFornecedor;
use App\Modules\Contratacao\Application\Port\Out\FornecedorHistoricoRepositoryPort;
use App\Modules\Contratacao\Domain\Policies\FornecedorCnpjUnicoNaContratacao;

final class EloquentFornecedorHistoricoRepository implements FornecedorHistoricoRepositoryPort
{
    public function listarCandidatos(Contratacao $contratacao, string $tenantId): array
    {
        $fornecedores = ContratacaoFornecedor::query()
            ->where('tenant_id', $tenantId)
            ->where('contratacao_id', '!=', $contratacao->id)
            ->with(['contratacao:id,categoria_servico,local'])
            ->get();

        /** @var array<string, array<string, mixed>> $agrupados */
        $agrupados = [];

        foreach ($fornecedores as $fornecedor) {
            $cnpj = FornecedorCnpjUnicoNaContratacao::normalizarCnpj($fornecedor->cnpj);
            if ($cnpj === '') {
                continue;
            }

            if (! isset($agrupados[$cnpj])) {
                $agrupados[$cnpj] = [
                    'cnpj' => $fornecedor->cnpj,
                    'razao_social' => $fornecedor->razao_social,
                    'telefone' => $fornecedor->telefone,
                    'email' => $fornecedor->email,
                    'cidade' => null,
                    'uf' => null,
                    'categoria_servico' => $fornecedor->contratacao?->categoria_servico,
                    'local' => $fornecedor->contratacao?->local,
                    'participacoes' => 0,
                    'origem' => 'historico_tenant',
                ];
            }

            $agrupados[$cnpj]['participacoes']++;
        }

        return array_values($agrupados);
    }

    public function findByCnpj(Contratacao $contratacao, string $tenantId, string $cnpj): ?array
    {
        $cnpjNorm = FornecedorCnpjUnicoNaContratacao::normalizarCnpj($cnpj);
        if ($cnpjNorm === '') {
            return null;
        }

        $fornecedor = ContratacaoFornecedor::query()
            ->where('tenant_id', $tenantId)
            ->where('contratacao_id', '!=', $contratacao->id)
            ->where('cnpj', $cnpjNorm)
            ->orderByDesc('created_at')
            ->first();

        if ($fornecedor === null) {
            return null;
        }

        return [
            'cnpj' => $fornecedor->cnpj,
            'razao_social' => $fornecedor->razao_social,
            'telefone' => $fornecedor->telefone,
            'email' => $fornecedor->email,
            'cidade' => null,
            'uf' => null,
            'origem' => 'historico_tenant',
        ];
    }
}

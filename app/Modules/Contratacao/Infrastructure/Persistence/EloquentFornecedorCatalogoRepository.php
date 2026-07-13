<?php

namespace App\Modules\Contratacao\Infrastructure\Persistence;

use App\Models\FornecedorCatalogo;
use App\Modules\Contratacao\Application\Port\Out\FornecedorCatalogoRepositoryPort;

final class EloquentFornecedorCatalogoRepository implements FornecedorCatalogoRepositoryPort
{
    public function listarAtivosPorTenant(string $tenantId): array
    {
        return FornecedorCatalogo::query()
            ->where('tenant_id', $tenantId)
            ->where('ativo', true)
            ->orderBy('razao_social')
            ->get()
            ->map(fn (FornecedorCatalogo $item) => [
                'cnpj' => $item->cnpj,
                'razao_social' => $item->razao_social,
                'telefone' => $item->telefone,
                'email' => $item->email,
                'cidade' => $item->cidade,
                'uf' => $item->uf,
                'categoria_servico' => $item->categoria_servico,
                'local' => $item->cidade && $item->uf ? "{$item->cidade} - {$item->uf}" : $item->cidade,
                'participacoes' => 0,
                'origem' => 'catalogo_tenant',
            ])
            ->values()
            ->all();
    }

    public function findAtivoByCnpj(string $tenantId, string $cnpj): ?array
    {
        $item = FornecedorCatalogo::query()
            ->where('tenant_id', $tenantId)
            ->where('ativo', true)
            ->where('cnpj', $cnpj)
            ->first();

        if ($item === null) {
            $item = FornecedorCatalogo::query()
                ->where('tenant_id', $tenantId)
                ->where('ativo', true)
                ->get()
                ->first(
                    fn (FornecedorCatalogo $catalogo) => preg_replace('/\D+/', '', $catalogo->cnpj) === $cnpj,
                );
        }

        if ($item === null) {
            return null;
        }

        return [
            'cnpj' => $item->cnpj,
            'razao_social' => $item->razao_social,
            'telefone' => $item->telefone,
            'email' => $item->email,
            'cidade' => $item->cidade,
            'uf' => $item->uf,
            'origem' => 'catalogo_tenant',
        ];
    }
}

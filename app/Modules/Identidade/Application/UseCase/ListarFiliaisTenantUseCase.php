<?php

namespace App\Modules\Identidade\Application\UseCase;

use App\Models\TenantFilial;
use App\Models\UsuarioCliente;

final class ListarFiliaisTenantUseCase
{
    /**
     * @return list<array<string, mixed>>
     */
    public function executar(UsuarioCliente $usuario): array
    {
        return TenantFilial::query()
            ->where('tenant_id', $usuario->tenant_id)
            ->orderBy('codigo')
            ->get()
            ->map(fn (TenantFilial $filial) => [
                'id' => $filial->id,
                'codigo' => $filial->codigo,
                'razao_social' => $filial->razao_social,
                'cnpj' => $filial->cnpj,
                'endereco' => $filial->endereco,
            ])
            ->values()
            ->all();
    }
}

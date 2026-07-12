<?php

namespace App\Modules\Identidade\Application\UseCase;

use App\Models\UsuarioCliente;

final class ObterPerfilUsuarioClienteUseCase
{
    /**
     * @return array<string, mixed>
     */
    public function executar(UsuarioCliente $usuario): array
    {
        $tenant = $usuario->tenant;

        return [
            'usuario' => [
                'id' => $usuario->id,
                'nome' => $usuario->nome,
                'email' => $usuario->email,
                'perfil' => $usuario->perfil,
                'cargo' => $usuario->cargo,
                'status' => $usuario->status,
                'preferencias' => $usuario->preferencias,
            ],
            'tenant' => [
                'id' => $tenant->id,
                'slug' => $tenant->slug,
                'razao_social' => $tenant->razao_social,
                'nome_fantasia' => $tenant->nome_fantasia,
                'status' => $tenant->status,
                'subscription_status' => $tenant->subscription_status,
                'trial_starts_at' => $tenant->trial_starts_at?->toIso8601String(),
                'trial_ends_at' => $tenant->trial_ends_at?->toIso8601String(),
            ],
        ];
    }
}

<?php

namespace App\Modules\Identidade\Application\DTO;

use App\Models\Tenant;
use App\Models\UsuarioCliente;
use App\Modules\Identidade\Domain\Services\TenantPortalUrlBuilder;

final class AuthResult
{
    public function __construct(
        public readonly string $token,
        public readonly int $expiresIn,
        public readonly UsuarioCliente $usuario,
        public readonly Tenant $tenant,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'token_type' => 'Bearer',
            'expires_in' => $this->expiresIn,
            'usuario' => [
                'id' => $this->usuario->id,
                'nome' => $this->usuario->nome,
                'email' => $this->usuario->email,
                'perfil' => $this->usuario->perfil,
                'cargo' => $this->usuario->cargo,
            ],
            'tenant' => [
                'id' => $this->tenant->id,
                'slug' => $this->tenant->slug,
                'razao_social' => $this->tenant->razao_social,
                'status' => $this->tenant->status,
                'subscription_status' => $this->tenant->subscription_status,
                'trial_ends_at' => $this->tenant->trial_ends_at?->toIso8601String(),
            ],
            'portal_url' => TenantPortalUrlBuilder::build($this->tenant->slug),
        ];
    }
}

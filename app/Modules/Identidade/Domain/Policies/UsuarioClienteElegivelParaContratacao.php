<?php

namespace App\Modules\Identidade\Domain\Policies;

use App\Models\UsuarioCliente;

final class UsuarioClienteElegivelParaContratacao
{
    public static function check(UsuarioCliente $usuario): bool
    {
        if (! UsuarioClienteAtivo::check($usuario)) {
            return false;
        }

        if (! in_array($usuario->perfil, ['area', 'admin_tenant'], true)) {
            return false;
        }

        $tenant = $usuario->tenant;

        if (! TenantAtivo::check($tenant)) {
            return false;
        }

        return TenantElegivelParaUsarProduto::check($tenant);
    }
}

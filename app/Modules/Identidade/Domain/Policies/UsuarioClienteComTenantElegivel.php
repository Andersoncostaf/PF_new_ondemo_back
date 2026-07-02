<?php

namespace App\Modules\Identidade\Domain\Policies;

use App\Models\UsuarioCliente;

final class UsuarioClienteComTenantElegivel
{
    public static function check(UsuarioCliente $usuario): bool
    {
        if (! UsuarioClienteAtivo::check($usuario)) {
            return false;
        }

        $tenant = $usuario->tenant;

        if (! TenantAtivo::check($tenant)) {
            return false;
        }

        return TenantElegivelParaUsarProduto::check($tenant);
    }
}

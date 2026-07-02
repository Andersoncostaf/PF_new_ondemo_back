<?php

namespace App\Modules\Identidade\Domain\Policies;

use App\Models\UsuarioCliente;

final class UsuarioClienteElegivelParaResponderApontamento
{
    public static function check(UsuarioCliente $usuario): bool
    {
        if (! UsuarioClienteComTenantElegivel::check($usuario)) {
            return false;
        }

        return in_array($usuario->perfil, ['area', 'admin_tenant'], true);
    }
}

<?php

namespace App\Modules\Identidade\Domain\Policies;

use App\Models\UsuarioCliente;

final class UsuarioClienteEAdminTenant
{
    public static function check(UsuarioCliente $usuario): bool
    {
        return $usuario->perfil === 'admin_tenant'
            && UsuarioClienteComTenantElegivel::check($usuario);
    }
}

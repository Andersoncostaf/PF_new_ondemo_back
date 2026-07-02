<?php

namespace App\Modules\Identidade\Domain\Policies;

use App\Models\UsuarioCliente;

final class UsuarioClienteElegivelParaContratacaoAprovacao
{
    public static function check(UsuarioCliente $usuario): bool
    {
        if (! UsuarioClienteComTenantElegivel::check($usuario)) {
            return false;
        }

        return in_array($usuario->perfil, ['compras', 'gestor', 'admin_tenant'], true);
    }
}

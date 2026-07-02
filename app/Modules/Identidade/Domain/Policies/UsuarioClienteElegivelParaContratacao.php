<?php

namespace App\Modules\Identidade\Domain\Policies;

use App\Models\UsuarioCliente;

/** @deprecated Use UsuarioClienteElegivelParaContratacaoWizard */
final class UsuarioClienteElegivelParaContratacao
{
    public static function check(UsuarioCliente $usuario): bool
    {
        return UsuarioClienteElegivelParaContratacaoWizard::check($usuario);
    }
}

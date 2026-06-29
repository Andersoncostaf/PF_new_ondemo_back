<?php

namespace App\Modules\Identidade\Domain\Policies;

use App\Models\UsuarioCliente;

final class UsuarioClienteAtivo
{
    public static function check(UsuarioCliente $usuario): bool
    {
        return $usuario->status === 'ativo';
    }
}

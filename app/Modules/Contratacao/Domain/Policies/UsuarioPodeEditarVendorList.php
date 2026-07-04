<?php

namespace App\Modules\Contratacao\Domain\Policies;

use App\Models\Contratacao;
use App\Models\UsuarioCliente;

final class UsuarioPodeEditarVendorList
{
    public static function check(UsuarioCliente $usuario, Contratacao $contratacao): bool
    {
        if ($usuario->perfil === 'compras') {
            return true;
        }

        return $contratacao->analista_usuario_id === $usuario->id;
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\UsuarioCliente;
use App\Modules\Identidade\Domain\Policies\UsuarioClienteElegivelParaContratacaoCompras;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureContratacaoComprasAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var UsuarioCliente|null $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        if ($usuario === null || ! UsuarioClienteElegivelParaContratacaoCompras::check($usuario)) {
            return response()->json([
                'message' => 'Acesso ao módulo de Compras não permitido para este perfil.',
                'code' => 'CONTRATACAO_COMPRAS_ACESSO_NEGADO',
            ], 403);
        }

        return $next($request);
    }
}

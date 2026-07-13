<?php

namespace App\Http\Middleware;

use App\Models\UsuarioCliente;
use App\Modules\Identidade\Domain\Policies\UsuarioClienteEAdminTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var UsuarioCliente|null $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        if ($usuario === null || ! UsuarioClienteEAdminTenant::check($usuario)) {
            return response()->json([
                'message' => 'Acesso restrito ao administrador do tenant.',
                'code' => 'ADMIN_TENANT_ACESSO_NEGADO',
            ], 403);
        }

        return $next($request);
    }
}

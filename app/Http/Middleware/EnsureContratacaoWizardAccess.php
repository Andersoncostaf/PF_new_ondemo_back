<?php

namespace App\Http\Middleware;

use App\Models\UsuarioCliente;
use App\Modules\Identidade\Domain\Policies\UsuarioClienteElegivelParaContratacaoWizard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureContratacaoWizardAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var UsuarioCliente|null $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        if ($usuario === null || ! UsuarioClienteElegivelParaContratacaoWizard::check($usuario)) {
            return response()->json([
                'message' => 'Acesso ao wizard de Contratação não permitido para este perfil.',
                'code' => 'CONTRATACAO_WIZARD_ACESSO_NEGADO',
            ], 403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\UsuarioCliente;
use App\Modules\Identidade\Domain\Policies\UsuarioClienteElegivelParaContratacaoAprovacao;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureContratacaoAprovacaoAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var UsuarioCliente|null $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        if ($usuario === null || ! UsuarioClienteElegivelParaContratacaoAprovacao::check($usuario)) {
            return response()->json([
                'message' => 'Acesso à aprovação de Contratação não permitido para este perfil.',
                'code' => 'CONTRATACAO_APROVACAO_ACESSO_NEGADO',
            ], 403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Modules\Identidade\Application\Port\Out\JwtTokenPort;
use App\Modules\Identidade\Application\Port\Out\UsuarioClienteRepositoryPort;
use App\Modules\Identidade\Domain\Exceptions\TokenInvalidoException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthenticate
{
    public function __construct(
        private JwtTokenPort $jwtToken,
        private UsuarioClienteRepositoryPort $usuarioRepository,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractBearerToken($request);

        if ($token === null) {
            throw new TokenInvalidoException('Token não informado.');
        }

        $claims = $this->jwtToken->decode($token);

        $usuario = $this->usuarioRepository->findById($claims['sub']);

        if ($usuario === null || $usuario->tenant_id !== $claims['tenant_id']) {
            throw new TokenInvalidoException;
        }

        $request->attributes->set('jwt_claims', $claims);
        $request->attributes->set('usuario_cliente', $usuario);

        if ($request->attributes->get('tenant') === null && $usuario->tenant !== null) {
            $request->attributes->set('tenant', $usuario->tenant);
        }

        return $next($request);
    }

    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (! is_string($header) || ! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($header, 7));

        return $token !== '' ? $token : null;
    }
}

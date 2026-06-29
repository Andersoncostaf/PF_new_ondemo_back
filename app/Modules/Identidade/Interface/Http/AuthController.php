<?php

namespace App\Modules\Identidade\Interface\Http;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Modules\Identidade\Application\DTO\LoginInput;
use App\Modules\Identidade\Application\Port\Out\JwtTokenPort;
use App\Modules\Identidade\Application\UseCase\AutenticarUsuarioClienteUseCase;
use App\Modules\Identidade\Interface\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private AutenticarUsuarioClienteUseCase $loginUseCase,
        private JwtTokenPort $jwtToken,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');

        $result = $this->loginUseCase->executar(
            $tenant,
            new LoginInput(
                email: $request->string('email')->toString(),
                password: $request->string('password')->toString(),
            ),
        );

        return response()->json($result->toArray());
    }

    public function logout(Request $request): JsonResponse
    {
        $header = $request->header('Authorization');

        if (is_string($header) && str_starts_with($header, 'Bearer ')) {
            $token = trim(substr($header, 7));

            if ($token !== '') {
                try {
                    $this->jwtToken->invalidate($token);
                } catch (\Throwable) {
                    // Logout idempotente — token inválido/expirado ainda retorna sucesso.
                }
            }
        }

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }
}

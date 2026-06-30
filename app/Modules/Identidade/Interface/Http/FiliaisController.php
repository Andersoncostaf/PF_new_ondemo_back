<?php

namespace App\Modules\Identidade\Interface\Http;

use App\Http\Controllers\Controller;
use App\Models\UsuarioCliente;
use App\Modules\Identidade\Application\UseCase\ListarFiliaisTenantUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FiliaisController extends Controller
{
    public function __construct(
        private ListarFiliaisTenantUseCase $listarUseCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        return response()->json([
            'data' => $this->listarUseCase->executar($usuario),
        ]);
    }
}

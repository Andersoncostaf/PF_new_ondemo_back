<?php

namespace App\Modules\Identidade\Interface\Http;

use App\Http\Controllers\Controller;
use App\Models\UsuarioCliente;
use App\Modules\Identidade\Application\UseCase\AtualizarPreferenciasUsuarioClienteUseCase;
use App\Modules\Identidade\Application\UseCase\ObterModulosUsuarioClienteUseCase;
use App\Modules\Identidade\Application\UseCase\ObterPerfilUsuarioClienteUseCase;
use App\Modules\Identidade\Interface\Http\Requests\AtualizarPreferenciasRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __construct(
        private ObterPerfilUsuarioClienteUseCase $perfilUseCase,
        private ObterModulosUsuarioClienteUseCase $modulosUseCase,
        private AtualizarPreferenciasUsuarioClienteUseCase $preferenciasUseCase,
    ) {}

    public function show(Request $request): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        return response()->json($this->perfilUseCase->executar($usuario));
    }

    public function updatePreferencias(AtualizarPreferenciasRequest $request): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        return response()->json(
            $this->preferenciasUseCase->executar($usuario, $request->validated()),
        );
    }

    public function modulos(Request $request): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        $modulos = array_map(
            fn ($modulo) => $modulo->toArray(),
            $this->modulosUseCase->executar($usuario),
        );

        return response()->json(['modulos' => $modulos]);
    }
}

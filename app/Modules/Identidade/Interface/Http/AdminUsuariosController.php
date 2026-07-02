<?php

namespace App\Modules\Identidade\Interface\Http;

use App\Http\Controllers\Controller;
use App\Models\UsuarioCliente;
use App\Modules\Identidade\Application\UseCase\AtualizarColaboradorTenantUseCase;
use App\Modules\Identidade\Application\UseCase\ConvidarColaboradorTenantUseCase;
use App\Modules\Identidade\Application\UseCase\ListarColaboradoresTenantUseCase;
use App\Modules\Identidade\Application\UseCase\RedefinirSenhaColaboradorTenantUseCase;
use App\Modules\Identidade\Domain\Exceptions\IdentidadeDomainException;
use App\Modules\Identidade\Interface\Http\Requests\AtualizarColaboradorRequest;
use App\Modules\Identidade\Interface\Http\Requests\ConvidarColaboradorRequest;
use App\Modules\Identidade\Interface\Http\Requests\RedefinirSenhaColaboradorRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUsuariosController extends Controller
{
    public function __construct(
        private ListarColaboradoresTenantUseCase $listarUseCase,
        private ConvidarColaboradorTenantUseCase $convidarUseCase,
        private AtualizarColaboradorTenantUseCase $atualizarUseCase,
        private RedefinirSenhaColaboradorTenantUseCase $redefinirSenhaUseCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var UsuarioCliente $admin */
        $admin = $request->attributes->get('usuario_cliente');

        return response()->json([
            'data' => $this->listarUseCase->executar($admin),
        ]);
    }

    public function store(ConvidarColaboradorRequest $request): JsonResponse
    {
        /** @var UsuarioCliente $admin */
        $admin = $request->attributes->get('usuario_cliente');

        try {
            $data = $this->convidarUseCase->executar(
                $admin,
                $request->validated('nome'),
                $request->validated('email'),
                $request->validated('password'),
                $request->validated('perfil'),
                $request->validated('cargo'),
            );

            return response()->json($data, 201);
        } catch (IdentidadeDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function update(AtualizarColaboradorRequest $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $admin */
        $admin = $request->attributes->get('usuario_cliente');

        try {
            $data = $this->atualizarUseCase->executar($admin, $uuid, $request->validated());

            return response()->json($data);
        } catch (IdentidadeDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function redefinirSenha(RedefinirSenhaColaboradorRequest $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $admin */
        $admin = $request->attributes->get('usuario_cliente');

        try {
            $this->redefinirSenhaUseCase->executar($admin, $uuid, $request->validated('password'));

            return response()->json(['message' => 'Senha redefinida com sucesso.']);
        } catch (IdentidadeDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }
}

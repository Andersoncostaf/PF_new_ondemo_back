<?php

namespace App\Modules\Contratacao\Interface\Http;

use App\Http\Controllers\Controller;
use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Application\DTO\ContratacaoInput;
use App\Modules\Contratacao\Application\UseCase\AtualizarContratacaoRascunhoUseCase;
use App\Modules\Contratacao\Application\UseCase\CriarContratacaoRascunhoUseCase;
use App\Modules\Contratacao\Application\UseCase\ListarContratacoesTenantUseCase;
use App\Modules\Contratacao\Application\UseCase\ObterContratacaoUseCase;
use App\Modules\Contratacao\Application\UseCase\SubmeterContratacaoUseCase;
use App\Modules\Contratacao\Interface\Http\Requests\StoreContratacaoRequest;
use App\Modules\Contratacao\Interface\Http\Requests\UpdateContratacaoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContratacaoController extends Controller
{
    public function __construct(
        private ListarContratacoesTenantUseCase $listarUseCase,
        private CriarContratacaoRascunhoUseCase $criarUseCase,
        private ObterContratacaoUseCase $obterUseCase,
        private AtualizarContratacaoRascunhoUseCase $atualizarUseCase,
        private SubmeterContratacaoUseCase $submeterUseCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        $page = max(1, (int) $request->query('page', 1));

        return response()->json($this->listarUseCase->executar($usuario, $page));
    }

    public function store(StoreContratacaoRequest $request): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        $data = $this->criarUseCase->executar(
            $usuario,
            ContratacaoInput::fromArray($request->validated()),
        );

        return response()->json($data, 201);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        return response()->json($this->obterUseCase->executar($usuario, $uuid));
    }

    public function update(UpdateContratacaoRequest $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        return response()->json(
            $this->atualizarUseCase->executar(
                $usuario,
                $uuid,
                ContratacaoInput::fromArray($request->validated()),
            )
        );
    }

    public function submeter(Request $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        return response()->json($this->submeterUseCase->executar($usuario, $uuid));
    }
}

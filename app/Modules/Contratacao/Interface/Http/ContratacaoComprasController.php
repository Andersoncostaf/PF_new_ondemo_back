<?php

namespace App\Modules\Contratacao\Interface\Http;

use App\Http\Controllers\Controller;
use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Application\DTO\ContratacaoListFilter;
use App\Modules\Contratacao\Application\Service\ContratacaoComprasService;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoDomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContratacaoComprasController extends Controller
{
    public function __construct(
        private ContratacaoComprasService $comprasService,
    ) {}

    public function fila(Request $request): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->comprasService->listarFila($usuario, ContratacaoListFilter::fromRequest($request))
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function assumirVendorList(Request $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json($this->comprasService->assumirVendorList($usuario, $uuid));
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json($this->comprasService->obterDetalhe($usuario, $uuid));
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }
}

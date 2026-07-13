<?php

namespace App\Modules\Contratacao\Interface\Http;

use App\Http\Controllers\Controller;
use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Application\Service\ContratacaoVendorListService;
use App\Modules\Contratacao\Application\Service\SugestaoFornecedorService;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoDomainException;
use App\Modules\Contratacao\Interface\Http\Requests\GerarSugestoesFornecedorRequest;
use App\Modules\Contratacao\Interface\Http\Requests\StoreContratacaoFornecedorRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContratacaoVendorListController extends Controller
{
    public function __construct(
        private ContratacaoVendorListService $vendorListService,
        private SugestaoFornecedorService $sugestaoFornecedorService,
    ) {}

    public function show(Request $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json($this->vendorListService->obterDetalhe($usuario, $uuid));
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function listarFornecedores(Request $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json([
                'data' => $this->vendorListService->listarFornecedores($usuario, $uuid),
            ]);
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function cadastrarFornecedor(StoreContratacaoFornecedorRequest $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->vendorListService->cadastrarFornecedor($usuario, $uuid, $request->validated()),
                201,
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function buscarFornecedorPorCnpj(Request $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->vendorListService->buscarFornecedorPorCnpj(
                    $usuario,
                    $uuid,
                    (string) $request->query('cnpj', ''),
                ),
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function gerarSugestoesFornecedores(GerarSugestoesFornecedorRequest $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->sugestaoFornecedorService->gerar($usuario, $uuid, $request->validated()),
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function registrarAceite(Request $request, string $uuid, string $fornecedorUuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->vendorListService->registrarAceiteParticipacao($usuario, $uuid, $fornecedorUuid),
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function removerFornecedor(Request $request, string $uuid, string $fornecedorUuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            $this->vendorListService->removerFornecedor($usuario, $uuid, $fornecedorUuid);

            return response()->json(['message' => 'Fornecedor excluído da contratação.']);
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }
}

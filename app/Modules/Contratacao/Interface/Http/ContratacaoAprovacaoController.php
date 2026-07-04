<?php

namespace App\Modules\Contratacao\Interface\Http;

use App\Http\Controllers\Controller;
use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Application\DTO\ContratacaoListFilter;
use App\Modules\Contratacao\Application\Service\ContratacaoAprovacaoService;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoDomainException;
use App\Modules\Contratacao\Interface\Http\Requests\SalvarApontamentoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContratacaoAprovacaoController extends Controller
{
    public function __construct(
        private ContratacaoAprovacaoService $aprovacaoService,
    ) {}

    public function pendentes(Request $request): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->aprovacaoService->listarPendentes($usuario, ContratacaoListFilter::fromRequest($request))
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function assumir(Request $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json($this->aprovacaoService->assumirAnalise($usuario, $uuid));
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json($this->aprovacaoService->obterDetalhe($usuario, $uuid));
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function listarApontamentos(Request $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json([
                'data' => $this->aprovacaoService->listarApontamentos(
                    $usuario,
                    $uuid,
                    $request->query('etapa'),
                ),
            ]);
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function salvarApontamento(SalvarApontamentoRequest $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->aprovacaoService->salvarApontamento(
                    $usuario,
                    $uuid,
                    $request->validated('etapa'),
                    $request->validated('descricao'),
                    $request->file('arquivo'),
                ),
                201,
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function excluirApontamento(Request $request, string $uuid, string $apontamentoId): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            $this->aprovacaoService->excluirApontamento($usuario, $uuid, $apontamentoId);

            return response()->json(['message' => 'Apontamento excluído.']);
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function baixarAnexoApontamento(Request $request, string $uuid, string $apontamentoId): Response
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            $dados = $this->aprovacaoService->baixarAnexoApontamento($usuario, $uuid, $apontamentoId);

            return response($dados['binario'], 200, [
                'Content-Type' => $dados['mime'] ?? 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="'.$dados['nome'].'"',
            ]);
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function retornarAjustes(Request $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json($this->aprovacaoService->retornarParaAjustes($usuario, $uuid));
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function aprovarAnalise(Request $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json($this->aprovacaoService->aprovarAnalise($usuario, $uuid));
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }
}

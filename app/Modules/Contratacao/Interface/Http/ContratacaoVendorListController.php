<?php

namespace App\Modules\Contratacao\Interface\Http;

use App\Http\Controllers\Controller;
use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Application\Service\AberturaContratoService;
use App\Modules\Contratacao\Application\Service\AvaliacaoTecnicaService;
use App\Modules\Contratacao\Application\Service\ContratacaoCotacaoService;
use App\Modules\Contratacao\Application\Service\ContratacaoVendorListService;
use App\Modules\Contratacao\Application\Service\EnriquecerFornecedorService;
use App\Modules\Contratacao\Application\Service\FornecedorUsuarioService;
use App\Modules\Contratacao\Application\Service\PropostaApontamentoService;
use App\Modules\Contratacao\Application\Service\SugestaoFornecedorService;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoDomainException;
use App\Modules\Contratacao\Interface\Http\Requests\AnalisarAberturaItemRequest;
use App\Modules\Contratacao\Interface\Http\Requests\DefinirFornecedorVencedorRequest;
use App\Modules\Contratacao\Interface\Http\Requests\EnriquecerFornecedorRequest;
use App\Modules\Contratacao\Interface\Http\Requests\GerarSugestoesFornecedorRequest;
use App\Modules\Contratacao\Interface\Http\Requests\ResponderPropostaApontamentoRequest;
use App\Modules\Contratacao\Interface\Http\Requests\SalvarAvaliacaoTecnicaRequest;
use App\Modules\Contratacao\Interface\Http\Requests\SalvarPropostaApontamentoRequest;
use App\Modules\Contratacao\Interface\Http\Requests\SalvarPropostaFornecedorRequest;
use App\Modules\Contratacao\Interface\Http\Requests\StoreContratacaoFornecedorRequest;
use App\Modules\Contratacao\Interface\Http\Requests\StoreFornecedorUsuarioRequest;
use App\Modules\Contratacao\Interface\Http\Requests\UpdateFornecedorUsuarioRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContratacaoVendorListController extends Controller
{
    public function __construct(
        private ContratacaoVendorListService $vendorListService,
        private SugestaoFornecedorService $sugestaoFornecedorService,
        private EnriquecerFornecedorService $enriquecerFornecedorService,
        private ContratacaoCotacaoService $cotacaoService,
        private AvaliacaoTecnicaService $avaliacaoTecnicaService,
        private AberturaContratoService $aberturaContratoService,
        private FornecedorUsuarioService $fornecedorUsuarioService,
        private PropostaApontamentoService $propostaApontamentoService,
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

    public function enriquecerFornecedor(EnriquecerFornecedorRequest $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->enriquecerFornecedorService->enriquecer($usuario, $uuid, $request->validated()),
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

    public function salvarProposta(
        SalvarPropostaFornecedorRequest $request,
        string $uuid,
        string $fornecedorUuid,
    ): JsonResponse {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->cotacaoService->salvarProposta($usuario, $uuid, $fornecedorUuid, $request->validated()),
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function definirVencedor(DefinirFornecedorVencedorRequest $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->cotacaoService->definirVencedor(
                    $usuario,
                    $uuid,
                    (string) $request->validated('fornecedor_uuid'),
                ),
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function aprovarVendorList(Request $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json($this->cotacaoService->aprovarVendorList($usuario, $uuid));
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function obterAvaliacaoTecnica(Request $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json($this->avaliacaoTecnicaService->obter($usuario, $uuid));
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function salvarAvaliacaoTecnica(SalvarAvaliacaoTecnicaRequest $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->avaliacaoTecnicaService->salvarNotas($usuario, $uuid, $request->validated()),
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function concluirAvaliacaoTecnica(Request $request, string $uuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json($this->avaliacaoTecnicaService->concluir($usuario, $uuid));
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function obterAberturaContrato(Request $request, string $uuid, string $fornecedorUuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->aberturaContratoService->obter($usuario, $uuid, $fornecedorUuid),
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function solicitarAberturaContrato(Request $request, string $uuid, string $fornecedorUuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->aberturaContratoService->solicitar($usuario, $uuid, $fornecedorUuid),
                201,
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function analisarItemAbertura(
        AnalisarAberturaItemRequest $request,
        string $uuid,
        string $fornecedorUuid,
        string $itemUuid,
    ): JsonResponse {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->aberturaContratoService->analisarItem(
                    $usuario,
                    $uuid,
                    $fornecedorUuid,
                    $itemUuid,
                    $request->validated(),
                ),
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function confirmarAberturaContrato(Request $request, string $uuid, string $fornecedorUuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->aberturaContratoService->confirmar($usuario, $uuid, $fornecedorUuid),
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function abrirApontamentoAbertura(
        SalvarPropostaApontamentoRequest $request,
        string $uuid,
        string $fornecedorUuid,
        string $itemUuid,
    ): JsonResponse {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->aberturaContratoService->abrirApontamento(
                    $usuario,
                    $uuid,
                    $fornecedorUuid,
                    $itemUuid,
                    (string) $request->validated('descricao'),
                ),
                201,
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function responderApontamentoAbertura(
        ResponderPropostaApontamentoRequest $request,
        string $uuid,
        string $fornecedorUuid,
        string $apontamentoUuid,
    ): JsonResponse {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->aberturaContratoService->responderApontamento(
                    $usuario,
                    $uuid,
                    $fornecedorUuid,
                    $apontamentoUuid,
                    (string) $request->validated('resposta'),
                ),
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function encerrarApontamentoAbertura(
        Request $request,
        string $uuid,
        string $fornecedorUuid,
        string $apontamentoUuid,
    ): JsonResponse {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->aberturaContratoService->encerrarApontamento($usuario, $uuid, $fornecedorUuid, $apontamentoUuid),
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function listarUsuariosFornecedor(Request $request, string $uuid, string $fornecedorUuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json([
                'data' => $this->fornecedorUsuarioService->listar($usuario, $uuid, $fornecedorUuid),
            ]);
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function cadastrarUsuarioFornecedor(
        StoreFornecedorUsuarioRequest $request,
        string $uuid,
        string $fornecedorUuid,
    ): JsonResponse {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->fornecedorUsuarioService->cadastrar($usuario, $uuid, $fornecedorUuid, $request->validated()),
                201,
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function atualizarUsuarioFornecedor(
        UpdateFornecedorUsuarioRequest $request,
        string $uuid,
        string $fornecedorUuid,
        string $usuarioUuid,
    ): JsonResponse {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->fornecedorUsuarioService->atualizarPerfil(
                    $usuario,
                    $uuid,
                    $fornecedorUuid,
                    $usuarioUuid,
                    $request->validated(),
                ),
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function inativarUsuarioFornecedor(
        Request $request,
        string $uuid,
        string $fornecedorUuid,
        string $usuarioUuid,
    ): JsonResponse {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->fornecedorUsuarioService->inativar($usuario, $uuid, $fornecedorUuid, $usuarioUuid),
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function listarApontamentosProposta(Request $request, string $uuid, string $fornecedorUuid): JsonResponse
    {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json([
                'data' => $this->propostaApontamentoService->listar($usuario, $uuid, $fornecedorUuid),
            ]);
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function criarApontamentoProposta(
        SalvarPropostaApontamentoRequest $request,
        string $uuid,
        string $fornecedorUuid,
    ): JsonResponse {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->propostaApontamentoService->abrir(
                    $usuario,
                    $uuid,
                    $fornecedorUuid,
                    (string) $request->validated('descricao'),
                ),
                201,
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function responderApontamentoProposta(
        ResponderPropostaApontamentoRequest $request,
        string $uuid,
        string $fornecedorUuid,
        string $apontamentoUuid,
    ): JsonResponse {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->propostaApontamentoService->responder(
                    $usuario,
                    $uuid,
                    $fornecedorUuid,
                    $apontamentoUuid,
                    (string) $request->validated('resposta'),
                ),
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }

    public function encerrarApontamentoProposta(
        Request $request,
        string $uuid,
        string $fornecedorUuid,
        string $apontamentoUuid,
    ): JsonResponse {
        /** @var UsuarioCliente $usuario */
        $usuario = $request->attributes->get('usuario_cliente');

        try {
            return response()->json(
                $this->propostaApontamentoService->encerrar($usuario, $uuid, $fornecedorUuid, $apontamentoUuid),
            );
        } catch (ContratacaoDomainException $e) {
            return response()->json($e->payload(), $e->statusCode());
        }
    }
}

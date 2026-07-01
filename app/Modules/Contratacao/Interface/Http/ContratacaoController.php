<?php



namespace App\Modules\Contratacao\Interface\Http;



use App\Http\Controllers\Controller;

use App\Models\UsuarioCliente;

use App\Modules\Contratacao\Application\DTO\ContratacaoInput;
use App\Modules\Contratacao\Application\DTO\ContratacaoListFilter;

use App\Modules\Contratacao\Application\UseCase\AdicionarContratacaoAnexoUseCase;

use App\Modules\Contratacao\Application\UseCase\AtualizarContratacaoRascunhoUseCase;

use App\Modules\Contratacao\Application\UseCase\CriarContratacaoRascunhoUseCase;

use App\Modules\Contratacao\Application\UseCase\ListarContratacoesTenantUseCase;

use App\Modules\Contratacao\Application\UseCase\ObterContratacaoUseCase;

use App\Modules\Contratacao\Application\UseCase\RemoverContratacaoAnexoUseCase;

use App\Modules\Contratacao\Application\UseCase\SubmeterContratacaoUseCase;

use App\Modules\Contratacao\Interface\Http\Requests\StoreContratacaoAnexoRequest;

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

        private AdicionarContratacaoAnexoUseCase $adicionarAnexoUseCase,

        private RemoverContratacaoAnexoUseCase $removerAnexoUseCase,

    ) {}



    public function index(Request $request): JsonResponse

    {

        /** @var UsuarioCliente $usuario */

        $usuario = $request->attributes->get('usuario_cliente');



        $filter = ContratacaoListFilter::fromRequest($request);



        return response()->json($this->listarUseCase->executar($usuario, $filter));

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



    public function storeAnexo(StoreContratacaoAnexoRequest $request, string $uuid): JsonResponse

    {

        /** @var UsuarioCliente $usuario */

        $usuario = $request->attributes->get('usuario_cliente');



        $data = $this->adicionarAnexoUseCase->executar(

            $usuario,

            $uuid,

            $request->validated('descricao'),

            $request->file('arquivo'),

        );



        return response()->json($data, 201);

    }



    public function destroyAnexo(Request $request, string $uuid, string $anexoId): JsonResponse

    {

        /** @var UsuarioCliente $usuario */

        $usuario = $request->attributes->get('usuario_cliente');



        $this->removerAnexoUseCase->executar($usuario, $uuid, $anexoId);



        return response()->json(null, 204);

    }

}


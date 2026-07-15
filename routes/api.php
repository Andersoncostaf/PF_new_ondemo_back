<?php

use App\Http\Controllers\HealthController;
use App\Modules\Contratacao\Interface\Http\ContratacaoAprovacaoController;
use App\Modules\Contratacao\Interface\Http\ContratacaoComprasController;
use App\Modules\Contratacao\Interface\Http\ContratacaoController;
use App\Modules\Contratacao\Interface\Http\ContratacaoVendorListController;
use App\Modules\Identidade\Interface\Http\AdminUsuariosController;
use App\Modules\Identidade\Interface\Http\AuthController;
use App\Modules\Identidade\Interface\Http\CadastroController;
use App\Modules\Identidade\Interface\Http\FiliaisController;
use App\Modules\Identidade\Interface\Http\MeController;
use App\Modules\Identidade\Interface\Http\SlugDisponivelController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::prefix('v1')->group(function () {
    Route::prefix('public')->group(function () {
        Route::post('/cadastro', [CadastroController::class, 'store']);
        Route::get('/slug-disponivel', [SlugDisponivelController::class, 'show']);
    });

    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('tenant.host');

        Route::post('/logout', [AuthController::class, 'logout'])
            ->middleware('jwt.auth');
    });

    Route::middleware('jwt.auth')->group(function () {
        Route::get('/me', [MeController::class, 'show']);
        Route::patch('/me/preferencias', [MeController::class, 'updatePreferencias']);
        Route::get('/me/modulos', [MeController::class, 'modulos']);
        Route::get('/filiais', [FiliaisController::class, 'index']);

        Route::middleware('identidade.admin_tenant')->prefix('admin/usuarios')->group(function () {
            Route::get('/', [AdminUsuariosController::class, 'index']);
            Route::post('/', [AdminUsuariosController::class, 'store']);
            Route::patch('/{uuid}', [AdminUsuariosController::class, 'update']);
            Route::post('/{uuid}/redefinir-senha', [AdminUsuariosController::class, 'redefinirSenha']);
        });

        Route::middleware('contratacao.wizard')->prefix('contratacao')->group(function () {
            Route::get('/', [ContratacaoController::class, 'index']);
            Route::post('/', [ContratacaoController::class, 'store']);
            Route::get('/{uuid}', [ContratacaoController::class, 'show']);
            Route::patch('/{uuid}', [ContratacaoController::class, 'update']);
            Route::delete('/{uuid}', [ContratacaoController::class, 'destroy']);
            Route::post('/{uuid}/submeter', [ContratacaoController::class, 'submeter']);
            Route::post('/{uuid}/anexos', [ContratacaoController::class, 'storeAnexo']);
            Route::delete('/{uuid}/anexos/{anexoId}', [ContratacaoController::class, 'destroyAnexo']);
            Route::get('/{uuid}/apontamentos', [ContratacaoController::class, 'listarApontamentos']);
            Route::post('/{uuid}/apontamentos/{apontamentoId}/responder', [ContratacaoController::class, 'responderApontamento']);
            Route::post('/{uuid}/reenviar', [ContratacaoController::class, 'reenviar']);
        });

        Route::middleware('contratacao.aprovacao')->prefix('contratacao/aprovacao')->group(function () {
            Route::get('/pendentes', [ContratacaoAprovacaoController::class, 'pendentes']);
            Route::post('/{uuid}/assumir', [ContratacaoAprovacaoController::class, 'assumir']);
            Route::get('/{uuid}', [ContratacaoAprovacaoController::class, 'show']);
            Route::get('/{uuid}/apontamentos', [ContratacaoAprovacaoController::class, 'listarApontamentos']);
            Route::post('/{uuid}/apontamentos', [ContratacaoAprovacaoController::class, 'salvarApontamento']);
            Route::delete('/{uuid}/apontamentos/{apontamentoId}', [ContratacaoAprovacaoController::class, 'excluirApontamento']);
            Route::get('/{uuid}/apontamentos/{apontamentoId}/anexo', [ContratacaoAprovacaoController::class, 'baixarAnexoApontamento']);
            Route::post('/{uuid}/retornar-ajustes', [ContratacaoAprovacaoController::class, 'retornarAjustes']);
            Route::post('/{uuid}/aprovar-analise', [ContratacaoAprovacaoController::class, 'aprovarAnalise']);
        });

        Route::middleware('contratacao.compras')->prefix('contratacao/compras')->group(function () {
            Route::get('/fila', [ContratacaoComprasController::class, 'fila']);
            Route::post('/{uuid}/assumir-vendor-list', [ContratacaoComprasController::class, 'assumirVendorList']);
            Route::get('/{uuid}', [ContratacaoComprasController::class, 'show']);
        });

        Route::middleware('contratacao.compras')->prefix('contratacao/compras/vendor-list')->group(function () {
            Route::get('/{uuid}', [ContratacaoVendorListController::class, 'show']);
            Route::get('/{uuid}/fornecedores', [ContratacaoVendorListController::class, 'listarFornecedores']);
            Route::get('/{uuid}/fornecedores/buscar', [ContratacaoVendorListController::class, 'buscarFornecedorPorCnpj']);
            Route::post('/{uuid}/fornecedores/enriquecer', [ContratacaoVendorListController::class, 'enriquecerFornecedor']);
            Route::post('/{uuid}/fornecedores', [ContratacaoVendorListController::class, 'cadastrarFornecedor']);
            Route::post('/{uuid}/fornecedores/{fornecedorUuid}/aceite', [ContratacaoVendorListController::class, 'registrarAceite']);
            Route::post('/{uuid}/sugestoes-fornecedores', [ContratacaoVendorListController::class, 'gerarSugestoesFornecedores']);
            Route::delete('/{uuid}/fornecedores/{fornecedorUuid}', [ContratacaoVendorListController::class, 'removerFornecedor']);

            Route::put('/{uuid}/fornecedores/{fornecedorUuid}/proposta', [ContratacaoVendorListController::class, 'salvarProposta']);
            Route::put('/{uuid}/fornecedor-vencedor', [ContratacaoVendorListController::class, 'definirVencedor']);
            Route::post('/{uuid}/aprovar-vendor-list', [ContratacaoVendorListController::class, 'aprovarVendorList']);

            Route::get('/{uuid}/avaliacao-tecnica', [ContratacaoVendorListController::class, 'obterAvaliacaoTecnica']);
            Route::put('/{uuid}/avaliacao-tecnica', [ContratacaoVendorListController::class, 'salvarAvaliacaoTecnica']);
            Route::post('/{uuid}/avaliacao-tecnica/concluir', [ContratacaoVendorListController::class, 'concluirAvaliacaoTecnica']);

            Route::get('/{uuid}/fornecedores/{fornecedorUuid}/abertura-contrato', [ContratacaoVendorListController::class, 'obterAberturaContrato']);
            Route::post('/{uuid}/fornecedores/{fornecedorUuid}/abertura-contrato/solicitar', [ContratacaoVendorListController::class, 'solicitarAberturaContrato']);
            Route::post('/{uuid}/fornecedores/{fornecedorUuid}/abertura-contrato/itens/{itemUuid}/analisar', [ContratacaoVendorListController::class, 'analisarItemAbertura']);
            Route::post('/{uuid}/fornecedores/{fornecedorUuid}/abertura-contrato/confirmar', [ContratacaoVendorListController::class, 'confirmarAberturaContrato']);
            Route::post('/{uuid}/fornecedores/{fornecedorUuid}/abertura-contrato/itens/{itemUuid}/apontamentos', [ContratacaoVendorListController::class, 'abrirApontamentoAbertura']);
            Route::post('/{uuid}/fornecedores/{fornecedorUuid}/abertura-contrato/apontamentos/{apontamentoUuid}/responder', [ContratacaoVendorListController::class, 'responderApontamentoAbertura']);
            Route::post('/{uuid}/fornecedores/{fornecedorUuid}/abertura-contrato/apontamentos/{apontamentoUuid}/encerrar', [ContratacaoVendorListController::class, 'encerrarApontamentoAbertura']);

            Route::get('/{uuid}/fornecedores/{fornecedorUuid}/visita-tecnica', [ContratacaoVendorListController::class, 'obterVisitaTecnica']);
            Route::put('/{uuid}/fornecedores/{fornecedorUuid}/visita-tecnica/agendar', [ContratacaoVendorListController::class, 'agendarVisitaTecnica']);
            Route::post('/{uuid}/fornecedores/{fornecedorUuid}/visita-tecnica/concluir', [ContratacaoVendorListController::class, 'concluirVisitaTecnica']);
            Route::post('/{uuid}/fornecedores/{fornecedorUuid}/visita-tecnica/dispensar', [ContratacaoVendorListController::class, 'dispensarVisitaTecnica']);

            Route::get('/{uuid}/fornecedores/{fornecedorUuid}/usuarios', [ContratacaoVendorListController::class, 'listarUsuariosFornecedor']);
            Route::post('/{uuid}/fornecedores/{fornecedorUuid}/usuarios', [ContratacaoVendorListController::class, 'cadastrarUsuarioFornecedor']);
            Route::patch('/{uuid}/fornecedores/{fornecedorUuid}/usuarios/{usuarioUuid}', [ContratacaoVendorListController::class, 'atualizarUsuarioFornecedor']);
            Route::post('/{uuid}/fornecedores/{fornecedorUuid}/usuarios/{usuarioUuid}/inativar', [ContratacaoVendorListController::class, 'inativarUsuarioFornecedor']);

            Route::get('/{uuid}/fornecedores/{fornecedorUuid}/proposta/apontamentos', [ContratacaoVendorListController::class, 'listarApontamentosProposta']);
            Route::post('/{uuid}/fornecedores/{fornecedorUuid}/proposta/apontamentos', [ContratacaoVendorListController::class, 'criarApontamentoProposta']);
            Route::post('/{uuid}/fornecedores/{fornecedorUuid}/proposta/apontamentos/{apontamentoUuid}/responder', [ContratacaoVendorListController::class, 'responderApontamentoProposta']);
            Route::post('/{uuid}/fornecedores/{fornecedorUuid}/proposta/apontamentos/{apontamentoUuid}/encerrar', [ContratacaoVendorListController::class, 'encerrarApontamentoProposta']);
        });
    });
});

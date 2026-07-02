<?php

use App\Http\Controllers\HealthController;
use App\Modules\Contratacao\Interface\Http\ContratacaoAprovacaoController;
use App\Modules\Contratacao\Interface\Http\ContratacaoController;
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
    });
});

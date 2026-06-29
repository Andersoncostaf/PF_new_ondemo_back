<?php

use App\Http\Controllers\HealthController;
use App\Modules\Identidade\Interface\Http\AuthController;
use App\Modules\Identidade\Interface\Http\CadastroController;
use App\Modules\Identidade\Interface\Http\MeController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::prefix('v1')->group(function () {
    Route::prefix('public')->group(function () {
        Route::post('/cadastro', [CadastroController::class, 'store']);
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
    });
});

<?php

namespace App\Providers\Modules;

use App\Modules\Contratacao\Application\Port\Out\ContratacaoRepositoryPort;
use App\Modules\Contratacao\Infrastructure\Persistence\EloquentContratacaoRepository;
use Illuminate\Support\ServiceProvider;

class ContratacaoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ContratacaoRepositoryPort::class, EloquentContratacaoRepository::class);
    }
}

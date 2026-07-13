<?php

namespace App\Providers\Modules;

use App\Modules\Contratacao\Application\Port\Out\ContratacaoFornecedorRepositoryPort;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoRepositoryPort;
use App\Modules\Contratacao\Application\Port\Out\FornecedorCatalogoRepositoryPort;
use App\Modules\Contratacao\Application\Port\Out\FornecedorHistoricoRepositoryPort;
use App\Modules\Contratacao\Application\Port\Out\N8nSugestaoFornecedorPort;
use App\Modules\Contratacao\Application\Port\Out\SugestaoCacheRepositoryPort;
use App\Modules\Contratacao\Infrastructure\N8n\CompositeSugestaoFornecedorClient;
use App\Modules\Contratacao\Infrastructure\N8n\LlmSugestaoFornecedorClient;
use App\Modules\Contratacao\Infrastructure\N8n\N8nSugestaoFornecedorClient;
use App\Modules\Contratacao\Infrastructure\N8n\WebSearchSugestaoFornecedorClient;
use App\Modules\Contratacao\Infrastructure\Persistence\EloquentContratacaoFornecedorRepository;
use App\Modules\Contratacao\Infrastructure\Persistence\EloquentContratacaoRepository;
use App\Modules\Contratacao\Infrastructure\Persistence\EloquentFornecedorCatalogoRepository;
use App\Modules\Contratacao\Infrastructure\Persistence\EloquentFornecedorHistoricoRepository;
use App\Modules\Contratacao\Infrastructure\Persistence\EloquentSugestaoCacheRepository;
use Illuminate\Support\ServiceProvider;

class ContratacaoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ContratacaoRepositoryPort::class, EloquentContratacaoRepository::class);
        $this->app->bind(ContratacaoFornecedorRepositoryPort::class, EloquentContratacaoFornecedorRepository::class);
        $this->app->bind(FornecedorHistoricoRepositoryPort::class, EloquentFornecedorHistoricoRepository::class);
        $this->app->bind(FornecedorCatalogoRepositoryPort::class, EloquentFornecedorCatalogoRepository::class);
        $this->app->bind(SugestaoCacheRepositoryPort::class, EloquentSugestaoCacheRepository::class);
        $this->app->bind(N8nSugestaoFornecedorPort::class, function ($app) {
            return new CompositeSugestaoFornecedorClient(
                $app->make(N8nSugestaoFornecedorClient::class),
                $app->make(LlmSugestaoFornecedorClient::class),
                $app->make(WebSearchSugestaoFornecedorClient::class),
            );
        });
        $this->app->singleton(\App\Modules\Contratacao\Infrastructure\Storage\ContratacaoAnexoStorage::class);
    }
}

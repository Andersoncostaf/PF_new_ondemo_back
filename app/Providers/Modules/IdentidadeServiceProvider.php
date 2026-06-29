<?php

namespace App\Providers\Modules;

use App\Modules\Identidade\Application\Port\Out\JwtTokenPort;
use App\Modules\Identidade\Application\Port\Out\TenantRepositoryPort;
use App\Modules\Identidade\Application\Port\Out\UsuarioClienteRepositoryPort;
use App\Modules\Identidade\Infrastructure\Auth\HmacJwtTokenService;
use App\Modules\Identidade\Infrastructure\Persistence\EloquentTenantRepository;
use App\Modules\Identidade\Infrastructure\Persistence\EloquentUsuarioClienteRepository;
use Illuminate\Support\ServiceProvider;

class IdentidadeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TenantRepositoryPort::class, EloquentTenantRepository::class);
        $this->app->bind(UsuarioClienteRepositoryPort::class, EloquentUsuarioClienteRepository::class);
        $this->app->bind(JwtTokenPort::class, HmacJwtTokenService::class);
    }
}

<?php

namespace App\Http\Middleware;

use App\Modules\Identidade\Infrastructure\Tenant\TenantHostResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromHost
{
    public function __construct(
        private TenantHostResolver $tenantHostResolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->tenantHostResolver->resolveFromRequest($request);
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}

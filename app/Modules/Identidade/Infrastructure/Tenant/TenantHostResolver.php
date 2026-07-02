<?php

namespace App\Modules\Identidade\Infrastructure\Tenant;

use App\Models\Tenant;
use App\Modules\Identidade\Application\Port\Out\TenantRepositoryPort;
use App\Modules\Identidade\Domain\Exceptions\TenantNaoEncontradoException;
use App\Modules\Identidade\Domain\Policies\ReservedSlugPolicy;
use Illuminate\Http\Request;

final class TenantHostResolver
{
    public function __construct(
        private TenantRepositoryPort $tenantRepository,
    ) {}

    public function resolveFromRequest(Request $request): Tenant
    {
        $slug = $this->extractSlug($request);

        if ($slug === null) {
            throw new TenantNaoEncontradoException;
        }

        if (ReservedSlugPolicy::isReserved($slug)) {
            throw new TenantNaoEncontradoException(
                'Este endereço é para criar conta. Acesse o portal da sua empresa.'
            );
        }

        $tenant = $this->tenantRepository->findBySlug($slug);

        if ($tenant === null) {
            throw new TenantNaoEncontradoException;
        }

        return $tenant;
    }

    private function extractSlug(Request $request): ?string
    {
        $candidates = array_filter([
            $request->header('X-Tenant-Slug'),
            $this->slugFromHost($request->getHost()),
            $this->slugFromHost((string) $request->header('X-Forwarded-Host')),
            $this->slugFromOrigin((string) $request->header('Origin')),
            $this->slugFromOrigin((string) $request->header('Referer')),
        ]);

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    private function slugFromHost(?string $host): ?string
    {
        if ($host === null || $host === '') {
            return null;
        }

        $host = strtolower(explode(':', $host)[0]);

        if (preg_match('/^portalfornecedor\.([a-z0-9-]+)\.(local|com\.br)$/', $host, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function slugFromOrigin(?string $origin): ?string
    {
        if ($origin === null || $origin === '') {
            return null;
        }

        $host = parse_url($origin, PHP_URL_HOST);

        return is_string($host) ? $this->slugFromHost($host) : null;
    }
}

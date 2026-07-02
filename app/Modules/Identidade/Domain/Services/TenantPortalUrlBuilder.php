<?php

namespace App\Modules\Identidade\Domain\Services;

final class TenantPortalUrlBuilder
{
    public static function build(string $slug): string
    {
        $template = (string) config(
            'identidade.frontend_tenant_url_template',
            'http://portalfornecedor.{slug}.local:4200'
        );

        return str_replace('{slug}', $slug, $template);
    }
}

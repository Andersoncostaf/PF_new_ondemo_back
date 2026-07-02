<?php

return [

    'jwt' => [
        'secret' => env('JWT_SECRET', env('APP_KEY')),
        'ttl' => (int) env('JWT_TTL', 3600),
    ],

    'tenant_host_pattern' => env(
        'TENANT_HOST_PATTERN',
        'portalfornecedor.{slug}.local'
    ),

    'frontend_tenant_url_template' => env(
        'FRONTEND_TENANT_URL_TEMPLATE',
        'http://portalfornecedor.{slug}.local:4200'
    ),

];

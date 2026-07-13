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

    'welcome_mail' => [
        'enabled' => filter_var(env('IDENTIDADE_WELCOME_MAIL_ENABLED', true), FILTER_VALIDATE_BOOL),
        'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@portalfornecedor.com.br'),
        'from_name' => env('MAIL_FROM_NAME', 'Portal Fornecedor On Demand'),
        'reply_to' => env('MAIL_REPLY_TO', 'suporte@portalfornecedor.com.br'),
    ],

];

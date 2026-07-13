<?php

return [
    'sugestao' => [
        'cache_ttl_hours' => (int) env('CONTRATACAO_SUGESTAO_CACHE_TTL_HOURS', 24),
        'n8n' => [
            'enabled' => (bool) env('CONTRATACAO_SUGESTAO_N8N_ENABLED', false),
            'webhook_url' => env('CONTRATACAO_SUGESTAO_N8N_WEBHOOK_URL', ''),
            'webhook_secret' => env('N8N_WEBHOOK_SECRET', ''),
            'timeout_seconds' => (int) env('CONTRATACAO_SUGESTAO_N8N_TIMEOUT_SECONDS', 15),
        ],
        'llm' => [
            'api_key' => env('CONTRATACAO_SUGESTAO_LLM_API_KEY', env('OPENAI_API_KEY', '')),
            'base_url' => env('CONTRATACAO_SUGESTAO_LLM_BASE_URL', 'https://api.openai.com/v1'),
            'model' => env('CONTRATACAO_SUGESTAO_LLM_MODEL', 'gpt-4o-mini'),
            'timeout_seconds' => (int) env('CONTRATACAO_SUGESTAO_LLM_TIMEOUT_SECONDS', 45),
        ],
        'web_search' => [
            'enabled' => (bool) env('CONTRATACAO_SUGESTAO_WEB_SEARCH_ENABLED', true),
            'timeout_seconds' => (int) env('CONTRATACAO_SUGESTAO_WEB_SEARCH_TIMEOUT_SECONDS', 20),
        ],
    ],
];

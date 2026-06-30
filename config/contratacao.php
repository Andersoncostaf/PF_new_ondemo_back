<?php

return [
    'anexos_disk' => env('CONTRATACAO_ANEXOS_DISK', 'local'),
    'anexos_max_kb' => (int) env('CONTRATACAO_ANEXOS_MAX_KB', 10240),
];

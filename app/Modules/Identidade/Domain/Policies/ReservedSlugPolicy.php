<?php

namespace App\Modules\Identidade\Domain\Policies;

final class ReservedSlugPolicy
{
    /** @var list<string> */
    private const RESERVED = [
        'cadastro',
        'www',
        'api',
        'app',
        'admin',
        'fornecedor',
        'portaldofornecedor',
        'homolog',
        'staging',
        'mail',
        'static',
        'login',
        'auth',
        'suporte',
        'billing',
        'assinatura',
    ];

    public static function isReserved(string $slug): bool
    {
        return in_array(strtolower(trim($slug)), self::RESERVED, true);
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return self::RESERVED;
    }
}

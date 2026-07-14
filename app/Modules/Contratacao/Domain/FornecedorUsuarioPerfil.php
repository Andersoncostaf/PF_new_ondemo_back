<?php

namespace App\Modules\Contratacao\Domain;

final class FornecedorUsuarioPerfil
{
    public const PADRAO = 'PADRAO';

    public const ADMIN = 'ADMIN';

    public const LIMITE_USUARIOS_ATIVOS = 10;

    public static function normalizar(?string $perfil): string
    {
        $p = strtoupper(trim((string) $perfil));

        return $p === self::ADMIN ? self::ADMIN : self::PADRAO;
    }

    public static function isAdmin(?string $perfil): bool
    {
        return self::normalizar($perfil) === self::ADMIN;
    }

    /** @return list<string> */
    public static function todos(): array
    {
        return [self::PADRAO, self::ADMIN];
    }
}

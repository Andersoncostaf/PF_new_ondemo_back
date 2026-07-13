<?php

namespace App\Modules\Identidade\Domain\Policies;

final class PerfilOperacionalColaborador
{
    /** @var list<string> */
    public const PERFIS = ['area', 'compras', 'gestor', 'fiscal', 'auditoria'];

    public static function isValid(string $perfil): bool
    {
        return in_array($perfil, self::PERFIS, true);
    }
}

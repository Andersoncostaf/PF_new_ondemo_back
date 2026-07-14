<?php

namespace App\Modules\Contratacao\Domain;

final class AberturaItemStatusAnalise
{
    public const PENDENTE = 'pendente';

    public const SIM = 'sim';

    public const NAO = 'nao';

    public const NA = 'na';

    /** @return list<string> */
    public static function todos(): array
    {
        return [self::PENDENTE, self::SIM, self::NAO, self::NA];
    }

    /** @return list<string> */
    public static function analise(): array
    {
        return [self::SIM, self::NAO, self::NA];
    }

    public static function isValid(?string $status): bool
    {
        return in_array(strtolower(trim((string) $status)), self::todos(), true);
    }

    public static function normalizar(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        return self::isValid($status) ? $status : self::PENDENTE;
    }

    public static function ehConforme(?string $status): bool
    {
        return in_array(self::normalizar($status), [self::SIM, self::NA], true);
    }
}

<?php

namespace App\Modules\Contratacao\Domain;

final class AvaliacaoTecnicaStatus
{
    public const RASCUNHO = 'rascunho';

    public const AGUARDANDO_AREA = 'aguardando_area';

    public const CONCLUIDA = 'concluida';

    /** @return list<string> */
    public static function todos(): array
    {
        return [
            self::RASCUNHO,
            self::AGUARDANDO_AREA,
            self::CONCLUIDA,
        ];
    }

    public static function normalizar(?string $valor): string
    {
        $v = strtolower(trim((string) $valor));

        return match ($v) {
            self::AGUARDANDO_AREA, self::CONCLUIDA => $v,
            default => self::RASCUNHO,
        };
    }

    public static function permiteAprovarVendorList(?string $status): bool
    {
        return self::normalizar($status) === self::CONCLUIDA;
    }

    public static function comprasPodeEditar(?string $status): bool
    {
        return in_array(self::normalizar($status), [
            self::RASCUNHO,
            self::AGUARDANDO_AREA,
        ], true);
    }
}

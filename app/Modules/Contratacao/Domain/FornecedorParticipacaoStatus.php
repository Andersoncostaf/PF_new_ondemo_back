<?php

namespace App\Modules\Contratacao\Domain;

final class FornecedorParticipacaoStatus
{
    public const CONVIDADO = 'convidado';

    public const ACEITO = 'aceito';

    public const EM_COTACAO = 'em_cotacao';

    public const VENCEDOR = 'vencedor';

    public const DESQUALIFICADA = 'desqualificada';

    /** @return list<string> */
    public static function todos(): array
    {
        return [
            self::CONVIDADO,
            self::ACEITO,
            self::EM_COTACAO,
            self::VENCEDOR,
            self::DESQUALIFICADA,
        ];
    }

    public static function isValid(?string $status): bool
    {
        return in_array(strtolower(trim((string) $status)), self::todos(), true);
    }

    public static function normalizar(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        return self::isValid($status) ? $status : self::CONVIDADO;
    }
}

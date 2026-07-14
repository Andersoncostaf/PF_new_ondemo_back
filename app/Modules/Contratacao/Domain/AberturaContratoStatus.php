<?php

namespace App\Modules\Contratacao\Domain;

final class AberturaContratoStatus
{
    public const NAO_INICIADA = 'nao_iniciada';

    public const AGUARDANDO_ENVIO = 'aguardando_envio';

    public const ENVIADO_PELO_FORNECEDOR = 'enviado_pelo_fornecedor';

    public const EM_AJUSTE = 'em_ajuste';

    public const ACEITO = 'aceito';

    /** @return list<string> */
    public static function todos(): array
    {
        return [
            self::NAO_INICIADA,
            self::AGUARDANDO_ENVIO,
            self::ENVIADO_PELO_FORNECEDOR,
            self::EM_AJUSTE,
            self::ACEITO,
        ];
    }

    public static function isValid(?string $status): bool
    {
        return in_array(strtolower(trim((string) $status)), self::todos(), true);
    }

    public static function normalizar(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        return self::isValid($status) ? $status : self::NAO_INICIADA;
    }

    public static function permiteComprasSolicitar(?string $status): bool
    {
        return in_array(self::normalizar($status), [
            self::NAO_INICIADA,
            self::AGUARDANDO_ENVIO,
        ], true);
    }

    public static function permiteAnaliseCompras(?string $status): bool
    {
        return in_array(self::normalizar($status), [
            self::AGUARDANDO_ENVIO,
            self::ENVIADO_PELO_FORNECEDOR,
            self::EM_AJUSTE,
        ], true);
    }

    public static function estaAceito(?string $status): bool
    {
        return self::normalizar($status) === self::ACEITO;
    }
}

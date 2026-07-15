<?php

namespace App\Modules\Contratacao\Domain;

final class VisitaTecnicaStatus
{
    public const NAO_INICIADA = 'nao_iniciada';

    public const INTERESSE_CONFIRMADO = 'interesse_confirmado';

    public const AGUARDANDO_ACEITE_FORNECEDOR = 'aguardando_aceite_fornecedor';

    public const AGENDAMENTO_FEITO = 'agendamento_feito';

    /** @return list<string> */
    public static function todos(): array
    {
        return [
            self::NAO_INICIADA,
            self::INTERESSE_CONFIRMADO,
            self::AGUARDANDO_ACEITE_FORNECEDOR,
            self::AGENDAMENTO_FEITO,
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

    /** Compras (sem portal do fornecedor) pode agendar enquanto a visita não estiver resolvida. */
    public static function permiteAgendarPorCompras(?string $status, ?string $resolucao): bool
    {
        if (VisitaTecnicaResolucao::estaResolvida($resolucao)) {
            return false;
        }

        return in_array(self::normalizar($status), [
            self::NAO_INICIADA,
            self::INTERESSE_CONFIRMADO,
            self::AGUARDANDO_ACEITE_FORNECEDOR,
            self::AGENDAMENTO_FEITO,
        ], true);
    }
}

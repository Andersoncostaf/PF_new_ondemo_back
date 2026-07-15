<?php

namespace App\Modules\Contratacao\Domain;

final class VisitaTecnicaResolucao
{
    public const PENDENTE = 'pendente';

    public const AGUARDANDO_APROVACAO_DISPENSA = 'aguardando_aprovacao_dispensa';

    public const DISPENSADA = 'dispensada';

    public const CONCLUIDA = 'concluida';

    /** @return list<string> */
    public static function todos(): array
    {
        return [
            self::PENDENTE,
            self::AGUARDANDO_APROVACAO_DISPENSA,
            self::DISPENSADA,
            self::CONCLUIDA,
        ];
    }

    public static function isValid(?string $resolucao): bool
    {
        return in_array(strtolower(trim((string) $resolucao)), self::todos(), true);
    }

    public static function normalizar(?string $resolucao): string
    {
        $resolucao = strtolower(trim((string) $resolucao));

        return self::isValid($resolucao) ? $resolucao : self::PENDENTE;
    }

    public static function estaResolvida(?string $resolucao): bool
    {
        $r = self::normalizar($resolucao);

        return $r === self::DISPENSADA || $r === self::CONCLUIDA;
    }

    public static function permiteEnviarProposta(?string $resolucao): bool
    {
        return self::estaResolvida($resolucao);
    }

    public static function permiteRegistrarPorCompras(?string $resolucao): bool
    {
        return ! self::estaResolvida($resolucao);
    }
}

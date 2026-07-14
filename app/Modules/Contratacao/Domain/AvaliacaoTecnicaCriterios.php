<?php

namespace App\Modules\Contratacao\Domain;

final class AvaliacaoTecnicaCriterios
{
    public const INDICE_MINIMO_PERCENTUAL = 70.0;

    /** @return list<array{codigo: string, label: string, peso_percentual: float}> */
    public static function todos(): array
    {
        return [
            ['codigo' => 'aderencia', 'label' => 'Aderência ao escopo', 'peso_percentual' => 30.0],
            ['codigo' => 'metodo', 'label' => 'Método de execução', 'peso_percentual' => 20.0],
            ['codigo' => 'sso', 'label' => 'SSO / segurança', 'peso_percentual' => 35.0],
            ['codigo' => 'atestado', 'label' => 'Atestado / referências', 'peso_percentual' => 15.0],
        ];
    }

    public static function pesoTotal(): float
    {
        return (float) array_sum(array_column(self::todos(), 'peso_percentual'));
    }

    /** @return list<string> */
    public static function codigosValidos(): array
    {
        return array_column(self::todos(), 'codigo');
    }
}

<?php

namespace App\Modules\Contratacao\Domain\Policies;

use App\Models\Contratacao;
use App\Modules\Contratacao\Domain\TermoReferenciaCampos;

final class ContratacaoCompletaParaSubmissao
{
    public static function check(Contratacao $contratacao): bool
    {
        if (blank($contratacao->titulo) || blank($contratacao->categoria_servico)) {
            return false;
        }

        if (! self::termoReferenciaCompleto($contratacao)) {
            return false;
        }

        return $contratacao->qqpItens()->count() >= 1;
    }

    /**
     * @return list<string>
     */
    public static function missingFields(Contratacao $contratacao): array
    {
        $missing = [];

        if (blank($contratacao->titulo)) {
            $missing[] = 'titulo';
        }

        if (blank($contratacao->categoria_servico)) {
            $missing[] = 'categoria_servico';
        }

        if (! self::termoReferenciaCompleto($contratacao)) {
            $missing[] = 'termo_referencia_campos';
        }

        if ($contratacao->qqpItens()->count() < 1) {
            $missing[] = 'qqp_itens';
        }

        return $missing;
    }

    private static function termoReferenciaCompleto(Contratacao $contratacao): bool
    {
        if (TermoReferenciaCampos::isComplete($contratacao->termo_referencia_campos)) {
            return true;
        }

        return ! blank($contratacao->termo_referencia);
    }
}

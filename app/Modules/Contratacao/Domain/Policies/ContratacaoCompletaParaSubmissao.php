<?php

namespace App\Modules\Contratacao\Domain\Policies;

use App\Models\Contratacao;

final class ContratacaoCompletaParaSubmissao
{
    public static function check(Contratacao $contratacao): bool
    {
        if (blank($contratacao->titulo) || blank($contratacao->categoria_servico)) {
            return false;
        }

        if (blank($contratacao->termo_referencia)) {
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

        if (blank($contratacao->termo_referencia)) {
            $missing[] = 'termo_referencia';
        }

        if ($contratacao->qqpItens()->count() < 1) {
            $missing[] = 'qqp_itens';
        }

        return $missing;
    }
}

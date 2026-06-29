<?php

namespace App\Modules\Contratacao\Domain\Policies;

use App\Models\Contratacao;

final class ContratacaoEditavelEmRascunho
{
    public static function check(Contratacao $contratacao): bool
    {
        return $contratacao->status === 'rascunho';
    }
}

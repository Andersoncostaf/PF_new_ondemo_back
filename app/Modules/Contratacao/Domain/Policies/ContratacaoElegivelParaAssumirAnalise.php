<?php

namespace App\Modules\Contratacao\Domain\Policies;

use App\Models\Contratacao;
use App\Modules\Contratacao\Domain\ContratacaoStatus;

final class ContratacaoElegivelParaAssumirAnalise
{
    public static function check(Contratacao $contratacao): bool
    {
        return $contratacao->status === ContratacaoStatus::AGUARDANDO_ANALISE_COMPRAS;
    }
}

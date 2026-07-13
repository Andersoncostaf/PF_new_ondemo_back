<?php

namespace App\Modules\Contratacao\Domain\Policies;

use App\Models\Contratacao;
use App\Modules\Contratacao\Domain\ContratacaoStatus;

final class ContratacaoElegivelParaAssumirVendorList
{
    public static function check(Contratacao $contratacao): bool
    {
        return $contratacao->status === ContratacaoStatus::APROVADO_COMPRAS;
    }
}

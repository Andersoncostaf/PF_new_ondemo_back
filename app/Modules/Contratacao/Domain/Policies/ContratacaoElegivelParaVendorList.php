<?php

namespace App\Modules\Contratacao\Domain\Policies;

use App\Models\Contratacao;
use App\Modules\Contratacao\Domain\ContratacaoStatus;

final class ContratacaoElegivelParaVendorList
{
    public static function check(Contratacao $contratacao): bool
    {
        return $contratacao->status === ContratacaoStatus::EM_VENDOR_LIST;
    }
}

<?php

namespace App\Modules\Contratacao\Domain\Policies;

use App\Models\Contratacao;
use App\Modules\Contratacao\Domain\ContratacaoStatus;

final class ContratacaoElegivelParaVendorList
{
    /** Edição de cotação (propostas, convites, aprovação). */
    public static function check(Contratacao $contratacao): bool
    {
        return $contratacao->status === ContratacaoStatus::EM_VENDOR_LIST;
    }

    /** Consulta + documentação/usuários após vencedor. */
    public static function checkConsulta(Contratacao $contratacao): bool
    {
        return in_array($contratacao->status, [
            ContratacaoStatus::EM_VENDOR_LIST,
            ContratacaoStatus::VENCEDOR_DEFINIDO,
        ], true);
    }
}

<?php

namespace App\Modules\Contratacao\Domain\Policies;

use App\Models\Contratacao;
use App\Modules\Contratacao\Domain\ContratacaoStatus;

final class ContratacaoElegivelParaRetornarAjustes
{
    public static function check(Contratacao $contratacao, int $pendentes): bool
    {
        return $contratacao->status === ContratacaoStatus::EM_ANALISE && $pendentes > 0;
    }
}

<?php

namespace App\Modules\Contratacao\Domain\Policies;

use App\Models\ContratacaoApontamento;

final class ApontamentoContratacaoPendente
{
    public static function estaPendente(ContratacaoApontamento $apontamento): bool
    {
        return $apontamento->status === 'pendente';
    }
}

<?php

namespace App\Modules\Contratacao\Domain\Policies;

use App\Models\Contratacao;

final class ContratacaoPertenceAoTenant
{
    public static function check(Contratacao $contratacao, string $tenantId): bool
    {
        return $contratacao->tenant_id === $tenantId;
    }
}

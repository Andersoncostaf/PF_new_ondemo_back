<?php

namespace App\Modules\Identidade\Domain\Policies;

use App\Models\Tenant;

final class TenantAtivo
{
    public static function check(Tenant $tenant): bool
    {
        return $tenant->status === 'ativo';
    }
}

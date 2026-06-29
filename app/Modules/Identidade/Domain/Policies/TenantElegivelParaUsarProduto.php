<?php

namespace App\Modules\Identidade\Domain\Policies;

use App\Models\Tenant;
use Illuminate\Support\Carbon;

final class TenantElegivelParaUsarProduto
{
    public static function check(Tenant $tenant, ?Carbon $now = null): bool
    {
        $now ??= Carbon::now();

        if ($tenant->subscription_status === 'active') {
            return true;
        }

        return $tenant->subscription_status === 'trial'
            && $now->lte($tenant->trial_ends_at);
    }
}

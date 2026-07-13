<?php

namespace App\Modules\Identidade\Application\DTO;

use Illuminate\Support\Carbon;

final class TenantBoasVindasMailPayload
{
    public function __construct(
        public readonly string $nomeAdmin,
        public readonly string $emailAdmin,
        public readonly string $razaoSocial,
        public readonly string $portalUrl,
        public readonly string $loginUrl,
        public readonly Carbon $trialEndsAt,
    ) {}
}

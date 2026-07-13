<?php

namespace App\Modules\Identidade\Application\Port\Out;

use App\Modules\Identidade\Application\DTO\TenantBoasVindasMailPayload;

interface TenantBoasVindasMailPort
{
    public function enviar(TenantBoasVindasMailPayload $payload): void;
}

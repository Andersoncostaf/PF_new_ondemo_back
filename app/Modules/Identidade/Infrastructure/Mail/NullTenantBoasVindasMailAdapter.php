<?php

namespace App\Modules\Identidade\Infrastructure\Mail;

use App\Modules\Identidade\Application\DTO\TenantBoasVindasMailPayload;
use App\Modules\Identidade\Application\Port\Out\TenantBoasVindasMailPort;

final class NullTenantBoasVindasMailAdapter implements TenantBoasVindasMailPort
{
    public function enviar(TenantBoasVindasMailPayload $payload): void
    {
        // no-op quando o envio estiver desabilitado
    }
}

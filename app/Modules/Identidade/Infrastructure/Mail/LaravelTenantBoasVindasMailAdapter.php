<?php

namespace App\Modules\Identidade\Infrastructure\Mail;

use App\Modules\Identidade\Application\DTO\TenantBoasVindasMailPayload;
use App\Modules\Identidade\Application\Port\Out\TenantBoasVindasMailPort;
use Illuminate\Support\Facades\Mail;

final class LaravelTenantBoasVindasMailAdapter implements TenantBoasVindasMailPort
{
    public function enviar(TenantBoasVindasMailPayload $payload): void
    {
        Mail::to($payload->emailAdmin)->send(new TenantBoasVindasMailable($payload));
    }
}

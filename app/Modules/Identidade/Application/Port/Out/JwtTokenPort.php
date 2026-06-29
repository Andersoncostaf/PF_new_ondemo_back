<?php

namespace App\Modules\Identidade\Application\Port\Out;

use App\Models\UsuarioCliente;

interface JwtTokenPort
{
    /**
     * @return array{token: string, expires_in: int}
     */
    public function issueForUsuarioCliente(UsuarioCliente $usuario): array;

    /**
     * @return array{sub: string, tenant_id: string, perfil: string, tipo_conta: string, jti: string, exp: int}
     */
    public function decode(string $token): array;

    public function invalidate(string $token): void;

    public function isInvalidated(string $jti): bool;
}

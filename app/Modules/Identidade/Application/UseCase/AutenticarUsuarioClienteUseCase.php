<?php

namespace App\Modules\Identidade\Application\UseCase;

use App\Models\Tenant;
use App\Modules\Identidade\Application\DTO\AuthResult;
use App\Modules\Identidade\Application\DTO\LoginInput;
use App\Modules\Identidade\Application\Port\Out\JwtTokenPort;
use App\Modules\Identidade\Application\Port\Out\UsuarioClienteRepositoryPort;
use App\Modules\Identidade\Domain\Exceptions\CredenciaisInvalidasException;
use App\Modules\Identidade\Domain\Policies\TenantAtivo;
use App\Modules\Identidade\Domain\Policies\UsuarioClienteAtivo;
use Illuminate\Support\Facades\Hash;

final class AutenticarUsuarioClienteUseCase
{
    public function __construct(
        private UsuarioClienteRepositoryPort $usuarioRepository,
        private JwtTokenPort $jwtToken,
    ) {}

    public function executar(Tenant $tenant, LoginInput $input): AuthResult
    {
        $usuario = $this->usuarioRepository->findByTenantAndEmail($tenant->id, $input->email);

        if ($usuario === null || ! Hash::check($input->password, $usuario->password)) {
            throw new CredenciaisInvalidasException;
        }

        if (! TenantAtivo::check($tenant) || ! UsuarioClienteAtivo::check($usuario)) {
            throw new CredenciaisInvalidasException;
        }

        $usuario->setRelation('tenant', $tenant);

        $tokenData = $this->jwtToken->issueForUsuarioCliente($usuario);

        return new AuthResult(
            token: $tokenData['token'],
            expiresIn: $tokenData['expires_in'],
            usuario: $usuario,
            tenant: $tenant,
        );
    }
}

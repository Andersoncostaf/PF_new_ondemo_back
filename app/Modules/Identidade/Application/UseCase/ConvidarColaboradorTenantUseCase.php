<?php

namespace App\Modules\Identidade\Application\UseCase;

use App\Models\UsuarioCliente;
use App\Modules\Identidade\Application\Port\Out\UsuarioClienteRepositoryPort;
use App\Modules\Identidade\Domain\Exceptions\EmailJaCadastradoNoTenantException;
use App\Modules\Identidade\Domain\Exceptions\PerfilOperacionalInvalidoException;
use App\Modules\Identidade\Domain\Policies\EmailUnicoPorTenant;
use App\Modules\Identidade\Domain\Policies\PerfilOperacionalColaborador;
use Illuminate\Support\Str;

final class ConvidarColaboradorTenantUseCase
{
    public function __construct(
        private UsuarioClienteRepositoryPort $repository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function executar(
        UsuarioCliente $admin,
        string $nome,
        string $email,
        string $password,
        string $perfil,
        ?string $cargo = null,
    ): array {
        if (! PerfilOperacionalColaborador::isValid($perfil)) {
            throw new PerfilOperacionalInvalidoException;
        }

        $email = Str::lower($email);

        if (! EmailUnicoPorTenant::check(
            $this->repository->existsByTenantAndEmail($admin->tenant_id, $email)
        )) {
            throw new EmailJaCadastradoNoTenantException;
        }

        $usuario = $this->repository->create([
            'tenant_id' => $admin->tenant_id,
            'nome' => $nome,
            'email' => $email,
            'password' => $password,
            'cargo' => $cargo,
            'perfil' => $perfil,
            'status' => 'ativo',
        ]);

        return [
            'id' => $usuario->id,
            'nome' => $usuario->nome,
            'email' => $usuario->email,
            'cargo' => $usuario->cargo,
            'perfil' => $usuario->perfil,
            'status' => $usuario->status,
        ];
    }
}

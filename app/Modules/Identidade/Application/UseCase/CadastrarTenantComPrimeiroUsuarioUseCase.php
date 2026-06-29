<?php

namespace App\Modules\Identidade\Application\UseCase;

use App\Models\Tenant;
use App\Models\UsuarioCliente;
use App\Modules\Identidade\Application\DTO\AuthResult;
use App\Modules\Identidade\Application\DTO\CadastroInput;
use App\Modules\Identidade\Application\Port\Out\JwtTokenPort;
use App\Modules\Identidade\Application\Port\Out\TenantRepositoryPort;
use App\Modules\Identidade\Application\Port\Out\UsuarioClienteRepositoryPort;
use App\Modules\Identidade\Domain\Exceptions\CnpjDuplicadoException;
use App\Modules\Identidade\Domain\Exceptions\EmailJaCadastradoNoTenantException;
use App\Modules\Identidade\Domain\Exceptions\SlugDuplicadoException;
use App\Modules\Identidade\Domain\Policies\EmailUnicoPorTenant;
use App\Modules\Identidade\Domain\Services\CnpjValidator;
use App\Modules\Identidade\Domain\Services\SlugGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CadastrarTenantComPrimeiroUsuarioUseCase
{
    public function __construct(
        private TenantRepositoryPort $tenantRepository,
        private UsuarioClienteRepositoryPort $usuarioRepository,
        private JwtTokenPort $jwtToken,
    ) {}

    public function executar(CadastroInput $input): AuthResult
    {
        $cnpj = CnpjValidator::normalize($input->cnpj);

        if ($this->tenantRepository->existsByCnpj($cnpj)) {
            throw new CnpjDuplicadoException;
        }

        $slug = $input->slug !== null && $input->slug !== ''
            ? Str::slug($input->slug, '-', 'pt')
            : SlugGenerator::fromRazaoSocial($input->razaoSocial);

        if ($slug === '' || $this->tenantRepository->existsBySlug($slug)) {
            throw new SlugDuplicadoException;
        }

        $now = Carbon::now();
        $trialEndsAt = $now->copy()->addDays(15);

        /** @var array{tenant: Tenant, usuario: UsuarioCliente} $result */
        $result = DB::transaction(function () use ($input, $cnpj, $slug, $now, $trialEndsAt) {
            $tenant = $this->tenantRepository->create([
                'slug' => $slug,
                'razao_social' => $input->razaoSocial,
                'cnpj' => $cnpj,
                'status' => 'ativo',
                'trial_starts_at' => $now,
                'trial_ends_at' => $trialEndsAt,
                'subscription_status' => 'trial',
            ]);

            if (! EmailUnicoPorTenant::check(
                $this->usuarioRepository->existsByTenantAndEmail($tenant->id, $input->email)
            )) {
                throw new EmailJaCadastradoNoTenantException;
            }

            $usuario = $this->usuarioRepository->create([
                'tenant_id' => $tenant->id,
                'nome' => $input->nome,
                'email' => Str::lower($input->email),
                'password' => $input->password,
                'cargo' => $input->cargo,
                'perfil' => 'admin_tenant',
                'status' => 'ativo',
            ]);

            return ['tenant' => $tenant, 'usuario' => $usuario];
        });

        $tokenData = $this->jwtToken->issueForUsuarioCliente($result['usuario']);

        return new AuthResult(
            token: $tokenData['token'],
            expiresIn: $tokenData['expires_in'],
            usuario: $result['usuario'],
            tenant: $result['tenant'],
        );
    }
}

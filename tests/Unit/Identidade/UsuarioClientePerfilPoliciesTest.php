<?php

namespace Tests\Unit\Identidade;

use App\Models\Tenant;
use App\Models\UsuarioCliente;
use App\Modules\Identidade\Domain\Policies\UsuarioClienteElegivelParaContratacaoAprovacao;
use App\Modules\Identidade\Domain\Policies\UsuarioClienteElegivelParaContratacaoWizard;
use PHPUnit\Framework\TestCase;

class UsuarioClientePerfilPoliciesTest extends TestCase
{
    /** @dataProvider wizardPerfisProvider */
    public function test_usuario_elegivel_para_wizard(string $perfil, bool $esperado): void
    {
        $usuario = $this->usuarioComPerfil($perfil);

        $this->assertSame($esperado, UsuarioClienteElegivelParaContratacaoWizard::check($usuario));
    }

    /** @dataProvider aprovacaoPerfisProvider */
    public function test_usuario_elegivel_para_aprovacao(string $perfil, bool $esperado): void
    {
        $usuario = $this->usuarioComPerfil($perfil);

        $this->assertSame($esperado, UsuarioClienteElegivelParaContratacaoAprovacao::check($usuario));
    }

    public static function wizardPerfisProvider(): array
    {
        return [
            ['area', true],
            ['admin_tenant', true],
            ['compras', false],
            ['gestor', false],
        ];
    }

    public static function aprovacaoPerfisProvider(): array
    {
        return [
            ['compras', true],
            ['gestor', true],
            ['admin_tenant', true],
            ['area', false],
            ['fiscal', false],
        ];
    }

    private function usuarioComPerfil(string $perfil): UsuarioCliente
    {
        $tenant = new Tenant([
            'status' => 'ativo',
            'subscription_status' => 'active',
        ]);

        $usuario = new UsuarioCliente([
            'status' => 'ativo',
            'perfil' => $perfil,
        ]);
        $usuario->setRelation('tenant', $tenant);

        return $usuario;
    }
}

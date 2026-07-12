<?php

namespace Tests\Feature\Identidade;

use App\Models\Tenant;
use App\Models\UsuarioCliente;
use App\Modules\Identidade\Infrastructure\Mail\TenantBoasVindasMailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class IdentidadeFase0Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'identidade.jwt.secret' => 'test-jwt-secret-key-for-fase0',
            'identidade.jwt.ttl' => 3600,
            'identidade.welcome_mail.enabled' => true,
            'identidade.frontend_tenant_url_template' => 'http://portalfornecedor.{slug}.local:4200',
        ]);
    }

    public function test_cadastro_envia_email_de_boas_vindas(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/public/cadastro', [
            'razao_social' => 'Empresa E-mail Ltda',
            'cnpj' => '11222333000181',
            'slug' => 'empresa-email',
            'nome' => 'Carlos Admin',
            'email' => 'carlos@empresa.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        Mail::assertSent(TenantBoasVindasMailable::class, function (TenantBoasVindasMailable $mail): bool {
            return $mail->hasTo('carlos@empresa.local')
                && $mail->payload->nomeAdmin === 'Carlos Admin'
                && $mail->payload->razaoSocial === 'Empresa E-mail Ltda'
                && $mail->payload->portalUrl === 'http://portalfornecedor.empresa-email.local:4200'
                && $mail->payload->loginUrl === 'http://portalfornecedor.empresa-email.local:4200/auth/login';
        });
    }

    public function test_cadastro_cria_tenant_usuario_e_retorna_jwt(): void
    {
        $response = $this->postJson('/api/v1/public/cadastro', [
            'razao_social' => 'Empresa Nova Ltda',
            'cnpj' => '11222333000181',
            'nome' => 'Maria Admin',
            'email' => 'maria@empresa.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'cargo' => 'Diretora',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'token',
                'token_type',
                'expires_in',
                'usuario' => ['id', 'nome', 'email', 'perfil'],
                'tenant' => ['id', 'slug', 'razao_social', 'subscription_status', 'trial_ends_at'],
                'portal_url',
            ])
            ->assertJsonPath('usuario.perfil', 'admin_tenant')
            ->assertJsonPath('tenant.subscription_status', 'trial');

        $this->assertDatabaseHas('tenants', [
            'cnpj' => '11222333000181',
            'status' => 'ativo',
            'subscription_status' => 'trial',
        ]);

        $this->assertDatabaseHas('usuarios_cliente', [
            'email' => 'maria@empresa.local',
            'perfil' => 'admin_tenant',
            'status' => 'ativo',
        ]);
    }

    public function test_cadastro_rejeita_cnpj_duplicado(): void
    {
        Tenant::query()->create([
            'slug' => 'existente',
            'razao_social' => 'Existente Ltda',
            'cnpj' => '11222333000181',
            'status' => 'ativo',
            'trial_starts_at' => Carbon::now(),
            'trial_ends_at' => Carbon::now()->addDays(15),
            'subscription_status' => 'trial',
        ]);

        $response = $this->postJson('/api/v1/public/cadastro', [
            'razao_social' => 'Outra Empresa',
            'cnpj' => '11222333000181',
            'nome' => 'João',
            'email' => 'joao@outra.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'CNPJ já cadastrado.');
    }

    public function test_cadastro_rejeita_slug_reservado(): void
    {
        $response = $this->postJson('/api/v1/public/cadastro', [
            'razao_social' => 'Empresa Nova Ltda',
            'cnpj' => '11222333000181',
            'slug' => 'cadastro',
            'nome' => 'Maria Admin',
            'email' => 'maria@empresa.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Este endereço não está disponível.');
    }

    public function test_slug_disponivel_retorna_true_para_slug_livre(): void
    {
        $response = $this->getJson('/api/v1/public/slug-disponivel?slug=minha-pme');

        $response->assertOk()
            ->assertJsonPath('disponivel', true)
            ->assertJsonPath('slug', 'minha-pme')
            ->assertJsonPath('sugestao', null);
    }

    public function test_slug_disponivel_retorna_false_para_slug_reservado(): void
    {
        $response = $this->getJson('/api/v1/public/slug-disponivel?slug=cadastro');

        $response->assertOk()
            ->assertJsonPath('disponivel', false)
            ->assertJsonPath('slug', 'cadastro');
    }

    public function test_slug_disponivel_retorna_false_e_sugestao_para_slug_existente(): void
    {
        Tenant::query()->create([
            'slug' => 'minha-pme',
            'razao_social' => 'Minha PME Ltda',
            'cnpj' => '04252011000110',
            'status' => 'ativo',
            'trial_starts_at' => Carbon::now(),
            'trial_ends_at' => Carbon::now()->addDays(15),
            'subscription_status' => 'trial',
        ]);

        $response = $this->getJson('/api/v1/public/slug-disponivel?slug=minha-pme');

        $response->assertOk()
            ->assertJsonPath('disponivel', false)
            ->assertJsonPath('sugestao', 'minha-pme-2');
    }

    public function test_login_bloqueado_no_host_cadastro(): void
    {
        $this->seedTenantDemo();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@clientex.local',
            'password' => 'password',
        ], [
            'Origin' => 'http://portalfornecedor.cadastro.local:4200',
        ]);

        $response->assertNotFound()
            ->assertJsonPath(
                'message',
                'Este endereço é para criar conta. Acesse o portal da sua empresa.'
            );
    }

    public function test_login_emite_jwt_com_tenant_resolvido_por_header(): void
    {
        $tenant = $this->seedTenantDemo();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@clientex.local',
            'password' => 'password',
        ], [
            'X-Tenant-Slug' => $tenant->slug,
        ]);

        $response->assertOk()
            ->assertJsonPath('usuario.email', 'admin@clientex.local')
            ->assertJsonPath('tenant.slug', 'clientex');

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_falha_com_mensagem_generica(): void
    {
        $tenant = $this->seedTenantDemo();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@clientex.local',
            'password' => 'senha-errada',
        ], [
            'X-Tenant-Slug' => $tenant->slug,
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'Credenciais inválidas.');
    }

    public function test_me_retorna_usuario_e_tenant(): void
    {
        $tenant = $this->seedTenantDemo();
        $token = $this->loginToken($tenant, 'admin@clientex.local', 'password');

        $response = $this->getJson('/api/v1/me', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJsonPath('usuario.email', 'admin@clientex.local')
            ->assertJsonPath('tenant.slug', 'clientex')
            ->assertJsonPath('usuario.preferencias', null);
    }

    public function test_login_inclui_preferencias_no_usuario(): void
    {
        $tenant = $this->seedTenantDemo();

        UsuarioCliente::query()
            ->where('email', 'admin@clientex.local')
            ->update(['preferencias' => ['theme' => 'dark']]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@clientex.local',
            'password' => 'password',
        ], [
            'X-Tenant-Slug' => $tenant->slug,
        ]);

        $response->assertOk()
            ->assertJsonPath('usuario.preferencias.theme', 'dark');
    }

    public function test_patch_me_preferencias_persiste_theme(): void
    {
        $tenant = $this->seedTenantDemo();
        $token = $this->loginToken($tenant, 'admin@clientex.local', 'password');

        $response = $this->patchJson('/api/v1/me/preferencias', [
            'theme' => 'dark',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJsonPath('preferencias.theme', 'dark');

        $this->assertDatabaseHas('usuarios_cliente', [
            'email' => 'admin@clientex.local',
        ]);

        $usuario = UsuarioCliente::query()->where('email', 'admin@clientex.local')->first();
        $this->assertSame(['theme' => 'dark'], $usuario->preferencias);

        $this->getJson('/api/v1/me', [
            'Authorization' => "Bearer {$token}",
        ])->assertOk()
            ->assertJsonPath('usuario.preferencias.theme', 'dark');
    }

    public function test_patch_me_preferencias_rejeita_theme_invalido(): void
    {
        $tenant = $this->seedTenantDemo();
        $token = $this->loginToken($tenant, 'admin@clientex.local', 'password');

        $this->patchJson('/api/v1/me/preferencias', [
            'theme' => 'neon',
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertUnprocessable();
    }

    public function test_patch_me_preferencias_faz_merge_sem_apagar_outras_chaves(): void
    {
        $tenant = $this->seedTenantDemo();

        UsuarioCliente::query()
            ->where('email', 'admin@clientex.local')
            ->update(['preferencias' => ['locale' => 'pt-BR', 'theme' => 'light']]);

        $token = $this->loginToken($tenant, 'admin@clientex.local', 'password');

        $this->patchJson('/api/v1/me/preferencias', [
            'theme' => 'system',
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertOk()
            ->assertJsonPath('preferencias.theme', 'system')
            ->assertJsonPath('preferencias.locale', 'pt-BR');
    }

    public function test_me_modulos_inclui_contratacao_para_admin_tenant_em_trial(): void
    {
        $tenant = $this->seedTenantDemo();
        $token = $this->loginToken($tenant, 'admin@clientex.local', 'password');

        $response = $this->getJson('/api/v1/me/modulos', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJsonPath('modulos.0.codigo', 'contratacao')
            ->assertJsonPath('modulos.0.rota', '/contratacao');
    }

    public function test_me_modulos_nao_inclui_contratacao_para_perfil_compras(): void
    {
        $tenant = $this->seedTenantDemo();

        UsuarioCliente::query()->create([
            'tenant_id' => $tenant->id,
            'nome' => 'Compras Demo',
            'email' => 'compras@clientex.local',
            'password' => 'password',
            'perfil' => 'compras',
            'status' => 'ativo',
        ]);

        $token = $this->loginToken($tenant, 'compras@clientex.local', 'password');

        $response = $this->getJson('/api/v1/me/modulos', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJsonPath('modulos', []);
    }

    public function test_logout_invalida_token(): void
    {
        $tenant = $this->seedTenantDemo();
        $token = $this->loginToken($tenant, 'admin@clientex.local', 'password');

        $this->postJson('/api/v1/auth/logout', [], [
            'Authorization' => "Bearer {$token}",
        ])->assertOk();

        $this->getJson('/api/v1/me', [
            'Authorization' => "Bearer {$token}",
        ])->assertUnauthorized();
    }

    private function seedTenantDemo(): Tenant
    {
        $tenant = Tenant::query()->create([
            'slug' => 'clientex',
            'razao_social' => 'Cliente Exemplo Ltda',
            'cnpj' => '04252011000110',
            'status' => 'ativo',
            'trial_starts_at' => Carbon::now(),
            'trial_ends_at' => Carbon::now()->addDays(15),
            'subscription_status' => 'trial',
        ]);

        UsuarioCliente::query()->create([
            'tenant_id' => $tenant->id,
            'nome' => 'Admin Demo',
            'email' => 'admin@clientex.local',
            'password' => 'password',
            'perfil' => 'admin_tenant',
            'status' => 'ativo',
        ]);

        return $tenant;
    }

    private function loginToken(Tenant $tenant, string $email, string $password): string
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
        ], [
            'X-Tenant-Slug' => $tenant->slug,
        ]);

        $response->assertOk();

        return (string) $response->json('token');
    }
}

<?php

namespace Tests\Feature\Contratacao;

use App\Models\Contratacao;
use App\Models\Tenant;
use App\Models\UsuarioCliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ContratacaoWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'identidade.jwt.secret' => 'test-jwt-secret-key-for-fase0',
            'identidade.jwt.ttl' => 3600,
        ]);
    }

    public function test_cria_rascunho_de_contratacao(): void
    {
        $tenant = $this->seedTenantDemo();
        $token = $this->loginToken($tenant, 'admin@clientex.local', 'password');

        $response = $this->postJson('/api/v1/contratacao', [
            'titulo' => 'Manutenção predial',
            'categoria_servico' => 'Facilities',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertCreated()
            ->assertJsonPath('titulo', 'Manutenção predial')
            ->assertJsonPath('status', 'rascunho');

        $this->assertDatabaseHas('contratacoes', [
            'tenant_id' => $tenant->id,
            'titulo' => 'Manutenção predial',
            'status' => 'rascunho',
        ]);
    }

    public function test_submete_contratacao_completa(): void
    {
        $tenant = $this->seedTenantDemo();
        $token = $this->loginToken($tenant, 'admin@clientex.local', 'password');

        $create = $this->postJson('/api/v1/contratacao', [
            'titulo' => 'Limpeza industrial',
            'categoria_servico' => 'Serviços gerais',
            'termo_referencia' => 'Escopo detalhado da limpeza.',
            'qqp_itens' => [
                ['descricao' => 'Horas de limpeza', 'quantidade' => 40, 'unidade' => 'h'],
            ],
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $uuid = (string) $create->json('uuid');

        $response = $this->postJson("/api/v1/contratacao/{$uuid}/submeter", [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'submetido');

        $this->assertDatabaseHas('contratacoes', [
            'uuid' => $uuid,
            'status' => 'submetido',
        ]);
    }

    public function test_nao_edita_contratacao_submetida(): void
    {
        $tenant = $this->seedTenantDemo();
        $usuario = UsuarioCliente::query()->where('email', 'admin@clientex.local')->firstOrFail();

        $contratacao = Contratacao::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id' => $tenant->id,
            'criado_por_usuario_id' => $usuario->id,
            'titulo' => 'Serviço fechado',
            'categoria_servico' => 'TI',
            'termo_referencia' => 'TR',
            'status' => 'submetido',
        ]);

        $token = $this->loginToken($tenant, 'admin@clientex.local', 'password');

        $response = $this->patchJson("/api/v1/contratacao/{$contratacao->uuid}", [
            'titulo' => 'Título alterado',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'CONTRATACAO_NAO_EDITAVEL');
    }

    public function test_isolamento_entre_tenants(): void
    {
        $tenantA = $this->seedTenantDemo();
        $tenantB = Tenant::query()->create([
            'slug' => 'outro',
            'razao_social' => 'Outro Tenant Ltda',
            'cnpj' => '11222333000181',
            'status' => 'ativo',
            'trial_starts_at' => Carbon::now(),
            'trial_ends_at' => Carbon::now()->addDays(15),
            'subscription_status' => 'trial',
        ]);

        UsuarioCliente::query()->create([
            'tenant_id' => $tenantB->id,
            'nome' => 'Admin B',
            'email' => 'admin@outro.local',
            'password' => 'password',
            'perfil' => 'admin_tenant',
            'status' => 'ativo',
        ]);

        $usuarioA = UsuarioCliente::query()->where('email', 'admin@clientex.local')->firstOrFail();

        $contratacaoA = Contratacao::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id' => $tenantA->id,
            'criado_por_usuario_id' => $usuarioA->id,
            'titulo' => 'Privada A',
            'status' => 'rascunho',
        ]);

        $tokenB = $this->loginToken($tenantB, 'admin@outro.local', 'password');

        $this->getJson("/api/v1/contratacao/{$contratacaoA->uuid}", [
            'Authorization' => "Bearer {$tokenB}",
        ])->assertNotFound();
    }

    public function test_perfil_compras_recebe_403_na_api_contratacao(): void
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

        $this->getJson('/api/v1/contratacao', [
            'Authorization' => "Bearer {$token}",
        ])->assertForbidden()
            ->assertJsonPath('code', 'CONTRATACAO_ACESSO_NEGADO');
    }

    public function test_lista_contratacoes_do_tenant(): void
    {
        $tenant = $this->seedTenantDemo();
        $usuario = UsuarioCliente::query()->where('email', 'admin@clientex.local')->firstOrFail();

        Contratacao::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id' => $tenant->id,
            'criado_por_usuario_id' => $usuario->id,
            'titulo' => 'Item 1',
            'status' => 'rascunho',
        ]);

        $token = $this->loginToken($tenant, 'admin@clientex.local', 'password');

        $response = $this->getJson('/api/v1/contratacao', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.titulo', 'Item 1');
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

<?php

namespace Tests\Feature\Contratacao;

use App\Models\Contratacao;
use App\Models\Tenant;
use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Domain\ContratacaoStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ContratacaoVendorListTest extends TestCase
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

    public function test_aprovar_analise_transiciona_para_aprovado_compras(): void
    {
        [$tenant, $contratacao] = $this->seedContratacaoEmAnalise(withToken: false);

        $gestor = UsuarioCliente::query()->create([
            'tenant_id' => $tenant->id,
            'nome' => 'Gestor VL',
            'email' => 'gestor@cliente-vl.local',
            'password' => 'password',
            'perfil' => 'gestor',
            'status' => 'ativo',
        ]);

        $token = $this->loginToken($tenant, $gestor->email, 'password');

        $response = $this->postJson("/api/v1/contratacao/aprovacao/{$contratacao->uuid}/aprovar-analise", [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJsonPath('status', ContratacaoStatus::APROVADO_COMPRAS);

        $this->assertDatabaseHas('contratacoes', [
            'uuid' => $contratacao->uuid,
            'status' => ContratacaoStatus::APROVADO_COMPRAS,
        ]);
    }

    public function test_fila_compras_inclui_aprovado_e_em_vendor_list(): void
    {
        [$tenant, $contratacao, $token] = $this->seedContratacaoEmAnalise();
        $contratacao->status = ContratacaoStatus::APROVADO_COMPRAS;
        $contratacao->save();

        $response = $this->getJson('/api/v1/contratacao/compras/fila', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.uuid', $contratacao->uuid)
            ->assertJsonPath('data.0.status', ContratacaoStatus::APROVADO_COMPRAS);
    }

    public function test_assumir_vendor_list_transiciona_de_aprovado_compras(): void
    {
        [$tenant, $contratacao, $token] = $this->seedContratacaoEmAnalise();
        $contratacao->status = ContratacaoStatus::APROVADO_COMPRAS;
        $contratacao->save();

        $response = $this->postJson("/api/v1/contratacao/compras/{$contratacao->uuid}/assumir-vendor-list", [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJsonPath('status', ContratacaoStatus::EM_VENDOR_LIST);

        $this->assertDatabaseHas('contratacoes', [
            'uuid' => $contratacao->uuid,
            'status' => ContratacaoStatus::EM_VENDOR_LIST,
        ]);
    }

    public function test_cadastra_e_remove_fornecedor_na_vendor_list(): void
    {
        [$tenant, $contratacao, $token] = $this->seedContratacaoEmAnalise();
        $contratacao->status = ContratacaoStatus::EM_VENDOR_LIST;
        $contratacao->save();

        $create = $this->postJson("/api/v1/contratacao/compras/vendor-list/{$contratacao->uuid}/fornecedores", [
            'cnpj' => '04252011000110',
            'razao_social' => 'Fornecedor Teste Ltda',
            'telefone' => '91999999999',
            'vendedor' => 'Maria Comercial',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $create->assertCreated()
            ->assertJsonPath('razao_social', 'Fornecedor Teste Ltda')
            ->assertJsonPath('vendedor', 'Maria Comercial')
            ->assertJsonPath('aceite', false);

        $fornecedorUuid = (string) $create->json('uuid');

        $this->assertDatabaseHas('contratacao_fornecedores', [
            'contratacao_id' => $contratacao->id,
            'cnpj' => '04252011000110',
        ]);

        $list = $this->getJson("/api/v1/contratacao/compras/vendor-list/{$contratacao->uuid}/fornecedores", [
            'Authorization' => "Bearer {$token}",
        ]);

        $list->assertOk()
            ->assertJsonPath('data.0.uuid', $fornecedorUuid);

        $aceite = $this->postJson(
            "/api/v1/contratacao/compras/vendor-list/{$contratacao->uuid}/fornecedores/{$fornecedorUuid}/aceite",
            [],
            ['Authorization' => "Bearer {$token}"],
        );

        $aceite->assertOk()
            ->assertJsonPath('aceite', true)
            ->assertJsonPath('status_participacao', 'aceito');

        $this->assertDatabaseHas('contratacao_fornecedores', [
            'uuid' => $fornecedorUuid,
            'aceite' => true,
            'status_participacao' => 'aceito',
        ]);

        $this->deleteJson("/api/v1/contratacao/compras/vendor-list/{$contratacao->uuid}/fornecedores/{$fornecedorUuid}", [], [
            'Authorization' => "Bearer {$token}",
        ])->assertOk();

        $this->assertDatabaseMissing('contratacao_fornecedores', [
            'uuid' => $fornecedorUuid,
        ]);
    }

    public function test_exige_vendedor_ao_cadastrar_fornecedor(): void
    {
        [$tenant, $contratacao, $token] = $this->seedContratacaoEmAnalise();
        $contratacao->status = ContratacaoStatus::EM_VENDOR_LIST;
        $contratacao->save();

        $this->postJson("/api/v1/contratacao/compras/vendor-list/{$contratacao->uuid}/fornecedores", [
            'cnpj' => '04252011000110',
            'razao_social' => 'Sem Vendedor Ltda',
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertUnprocessable();
    }

    public function test_bloqueia_cnpj_duplicado_na_contratacao(): void
    {
        [$tenant, $contratacao, $token] = $this->seedContratacaoEmAnalise();
        $contratacao->status = ContratacaoStatus::EM_VENDOR_LIST;
        $contratacao->save();

        $payload = [
            'cnpj' => '04252011000110',
            'razao_social' => 'Fornecedor A',
            'vendedor' => 'João Vendas',
        ];

        $this->postJson("/api/v1/contratacao/compras/vendor-list/{$contratacao->uuid}/fornecedores", $payload, [
            'Authorization' => "Bearer {$token}",
        ])->assertCreated();

        $this->postJson("/api/v1/contratacao/compras/vendor-list/{$contratacao->uuid}/fornecedores", $payload, [
            'Authorization' => "Bearer {$token}",
        ])->assertUnprocessable();
    }

    public function test_isolamento_fornecedores_entre_tenants(): void
    {
        [$tenantA, $contratacaoA, $tokenA] = $this->seedContratacaoEmAnalise();
        $contratacaoA->status = ContratacaoStatus::EM_VENDOR_LIST;
        $contratacaoA->save();

        $tenantB = Tenant::query()->create([
            'slug' => 'outro-vl',
            'razao_social' => 'Outro Tenant VL',
            'cnpj' => '11222333000181',
            'status' => 'ativo',
            'trial_starts_at' => Carbon::now(),
            'trial_ends_at' => Carbon::now()->addDays(15),
            'subscription_status' => 'trial',
        ]);

        UsuarioCliente::query()->create([
            'tenant_id' => $tenantB->id,
            'nome' => 'Compras B',
            'email' => 'comprasb@outro.local',
            'password' => 'password',
            'perfil' => 'compras',
            'status' => 'ativo',
        ]);

        $tokenB = $this->loginToken($tenantB, 'comprasb@outro.local', 'password');

        $this->getJson("/api/v1/contratacao/compras/vendor-list/{$contratacaoA->uuid}", [
            'Authorization' => "Bearer {$tokenB}",
        ])->assertNotFound();
    }

    public function test_compras_nao_acessa_fila_aprovacao(): void
    {
        [$tenant, $contratacao, $token] = $this->seedContratacaoEmAnalise();

        $this->getJson('/api/v1/contratacao/aprovacao/pendentes', [
            'Authorization' => "Bearer {$token}",
        ])->assertForbidden();
    }

    /**
     * @return array{0: Tenant, 1: Contratacao, 2?: string}
     */
    private function seedContratacaoEmAnalise(bool $withToken = true): array
    {
        $tenant = Tenant::query()->create([
            'slug' => 'cliente-vl',
            'razao_social' => 'Cliente Vendor List',
            'cnpj' => '04252011000110',
            'status' => 'ativo',
            'trial_starts_at' => Carbon::now(),
            'trial_ends_at' => Carbon::now()->addDays(15),
            'subscription_status' => 'trial',
        ]);

        $compras = UsuarioCliente::query()->create([
            'tenant_id' => $tenant->id,
            'nome' => 'Compras VL',
            'email' => 'compras@cliente-vl.local',
            'password' => 'password',
            'perfil' => 'compras',
            'status' => 'ativo',
        ]);

        $contratacao = Contratacao::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'tenant_id' => $tenant->id,
            'criado_por_usuario_id' => $compras->id,
            'titulo' => 'Contratação VL',
            'categoria_servico' => 'Serviços',
            'termo_referencia' => 'TR',
            'status' => ContratacaoStatus::EM_ANALISE,
            'analista_usuario_id' => $compras->id,
            'analise_iniciada_em' => Carbon::now(),
        ]);

        if (! $withToken) {
            return [$tenant, $contratacao];
        }

        $token = $this->loginToken($tenant, 'compras@cliente-vl.local', 'password');

        return [$tenant, $contratacao, $token];
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

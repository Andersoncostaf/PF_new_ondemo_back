<?php

namespace Tests\Feature\Contratacao;

use App\Models\Contratacao;
use App\Models\Tenant;
use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Domain\ContratacaoStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContratacaoEnriquecerFornecedorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'identidade.jwt.secret' => 'test-jwt-secret-key-for-fase0',
            'identidade.jwt.ttl' => 3600,
            'contratacao.enrichment.brasil_api.enabled' => true,
            'contratacao.enrichment.brasil_api.base_url' => 'https://brasilapi.test/api/cnpj/v1',
            'contratacao.enrichment.web_search.enabled' => true,
        ]);
    }

    public function test_enriquece_por_cnpj_via_brasil_api(): void
    {
        Http::fake([
            'brasilapi.test/*' => Http::response([
                'cnpj' => '11222333000181',
                'razao_social' => 'Dinaldo Energia Ltda',
                'nome_fantasia' => 'Dinaldoenergia',
                'ddd_telefone_1' => '9132123456',
                'email' => 'contato@dinaldoenergia.com.br',
                'municipio' => 'BELEM',
                'uf' => 'PA',
                'qsa' => [
                    ['nome_socio' => 'João da Silva'],
                ],
            ], 200),
            'html.duckduckgo.com/*' => Http::response('<html></html>', 200),
        ]);

        [$tenant, $contratacao, $token] = $this->seedVendorListAtiva();

        $response = $this->postJson(
            "/api/v1/contratacao/compras/vendor-list/{$contratacao->uuid}/fornecedores/enriquecer",
            [
                'cnpj' => '11222333000181',
                'razao_social' => 'Dinaldoenergia',
            ],
            ['Authorization' => "Bearer {$token}"],
        );

        $response->assertOk()
            ->assertJsonPath('encontrado', true)
            ->assertJsonPath('cnpj', '11222333000181')
            ->assertJsonPath('razao_social', 'Dinaldo Energia Ltda')
            ->assertJsonPath('telefone', '9132123456')
            ->assertJsonPath('email', 'contato@dinaldoenergia.com.br')
            ->assertJsonPath('vendedor', 'João da Silva');
    }

    public function test_enriquece_por_nome_via_web_e_completa_cnpj(): void
    {
        Http::fake([
            'html.duckduckgo.com/*' => Http::response(
                <<<'HTML'
                <html><body>
                <a class="result__a" href="https://duckduckgo.com/l/?uddg=https%3A%2F%2Fwww.dinaldoenergia.com.br">Dinaldoenergia - Contato</a>
                <a class="result__snippet">Empresa Dinaldoenergia CNPJ 11.222.333/0001-81 telefone (91) 3212-3456 contato@dinaldo.com</a>
                <a class="result__a" href="https://duckduckgo.com/l/?uddg=https%3A%2F%2Finstagram.com%2Fdinaldoenergia">Instagram</a>
                </body></html>
                HTML,
                200,
            ),
            'brasilapi.test/11222333000181' => Http::response([
                'cnpj' => '11222333000181',
                'razao_social' => 'Dinaldo Energia Ltda',
                'ddd_telefone_1' => '9132123456',
                'email' => 'contato@dinaldo.com',
                'municipio' => 'BELEM',
                'uf' => 'PA',
                'qsa' => [],
            ], 200),
        ]);

        [$tenant, $contratacao, $token] = $this->seedVendorListAtiva();

        $response = $this->postJson(
            "/api/v1/contratacao/compras/vendor-list/{$contratacao->uuid}/fornecedores/enriquecer",
            ['razao_social' => 'Dinaldoenergia', 'uf' => 'PA'],
            ['Authorization' => "Bearer {$token}"],
        );

        $response->assertOk()
            ->assertJsonPath('encontrado', true)
            ->assertJsonPath('cnpj', '11222333000181')
            ->assertJsonPath('site', 'https://www.dinaldoenergia.com.br')
            ->assertJsonPath('instagram', 'https://instagram.com/dinaldoenergia');
    }

    public function test_busca_cnpj_manual_usa_brasil_api_como_fallback(): void
    {
        Http::fake([
            'brasilapi.test/*' => Http::response([
                'cnpj' => '11222333000181',
                'razao_social' => 'Empresa Teste Ltda',
                'ddd_telefone_1' => '11987654321',
                'email' => 'teste@empresa.com',
                'municipio' => 'SAO PAULO',
                'uf' => 'SP',
                'qsa' => [],
            ], 200),
        ]);

        [$tenant, $contratacao, $token] = $this->seedVendorListAtiva();

        $response = $this->getJson(
            "/api/v1/contratacao/compras/vendor-list/{$contratacao->uuid}/fornecedores/buscar?cnpj=11222333000181",
            ['Authorization' => "Bearer {$token}"],
        );

        $response->assertOk()
            ->assertJsonPath('encontrado', true)
            ->assertJsonPath('origem', 'brasil_api')
            ->assertJsonPath('razao_social', 'Empresa Teste Ltda');
    }

    /**
     * @return array{0: Tenant, 1: Contratacao, 2: string}
     */
    private function seedVendorListAtiva(): array
    {
        $tenant = Tenant::query()->create([
            'slug' => 'cliente-enrich',
            'razao_social' => 'Cliente Enrich',
            'cnpj' => '04252011000110',
            'status' => 'ativo',
            'trial_starts_at' => Carbon::now(),
            'trial_ends_at' => Carbon::now()->addDays(15),
            'subscription_status' => 'trial',
        ]);

        UsuarioCliente::query()->create([
            'tenant_id' => $tenant->id,
            'nome' => 'Compras Enrich',
            'email' => 'compras@enrich.local',
            'password' => 'password',
            'perfil' => 'compras',
            'status' => 'ativo',
        ]);

        $contratacao = Contratacao::query()->create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'criado_por_usuario_id' => UsuarioCliente::query()->where('tenant_id', $tenant->id)->value('id'),
            'titulo' => 'Contratação enrich',
            'categoria_servico' => 'Energia',
            'local' => 'Belém - PA',
            'termo_referencia' => 'Termo',
            'status' => ContratacaoStatus::EM_VENDOR_LIST,
        ]);

        $token = $this->loginToken($tenant, 'compras@enrich.local', 'password');

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

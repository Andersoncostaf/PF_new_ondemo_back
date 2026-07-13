<?php

namespace Tests\Feature\Contratacao;

use App\Models\Contratacao;
use App\Models\ContratacaoFornecedor;
use App\Models\FornecedorCatalogo;
use App\Models\Tenant;
use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Domain\ContratacaoStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContratacaoSugestaoFornecedorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'identidade.jwt.secret' => 'test-jwt-secret-key-for-fase0',
            'identidade.jwt.ttl' => 3600,
            'contratacao.sugestao.n8n.enabled' => false,
            'contratacao.sugestao.web_search.enabled' => false,
            'contratacao.sugestao.llm.api_key' => '',
            'contratacao.sugestao.cache_ttl_hours' => 24,
        ]);
    }

    public function test_retorna_fornecedor_do_historico(): void
    {
        [$tenant, $contratacao, $token] = $this->seedVendorListAtiva();
        $outra = $this->criarContratacao($tenant, 'Manutenção elétrica', 'Belém - PA');

        ContratacaoFornecedor::query()->create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'contratacao_id' => $outra->id,
            'cnpj' => '11222333000181',
            'razao_social' => 'Elétrica Norte Ltda',
            'telefone' => '91999990000',
            'status_participacao' => 'convidado',
        ]);

        $contratacao->update([
            'categoria_servico' => 'Manutenção elétrica',
            'local' => 'Belém - PA',
        ]);

        $response = $this->postJson(
            "/api/v1/contratacao/compras/vendor-list/{$contratacao->uuid}/sugestoes-fornecedores",
            [],
            ['Authorization' => "Bearer {$token}"],
        );

        $response->assertOk()
            ->assertJsonPath('sugestoes.0.origem', 'historico_tenant')
            ->assertJsonPath('sugestoes.0.razao_social', 'Elétrica Norte Ltda')
            ->assertJsonPath('meta.cache_hit', false);
    }

    public function test_retorna_fornecedor_do_catalogo_tenant(): void
    {
        [$tenant, $contratacao, $token] = $this->seedVendorListAtiva();

        FornecedorCatalogo::query()->create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'cnpj' => '99888777000166',
            'razao_social' => 'Catálogo Serviços PA',
            'categoria_servico' => 'Serviços',
            'cidade' => 'Belém',
            'uf' => 'PA',
            'ativo' => true,
        ]);

        $response = $this->postJson(
            "/api/v1/contratacao/compras/vendor-list/{$contratacao->uuid}/sugestoes-fornecedores",
            [],
            ['Authorization' => "Bearer {$token}"],
        );

        $response->assertOk()
            ->assertJsonPath('sugestoes.0.origem', 'catalogo_tenant')
            ->assertJsonPath('sugestoes.0.razao_social', 'Catálogo Serviços PA');
    }

    public function test_nao_sugere_cnpj_ja_cadastrado_na_contratacao(): void
    {
        [$tenant, $contratacao, $token] = $this->seedVendorListAtiva();

        ContratacaoFornecedor::query()->create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'contratacao_id' => $contratacao->id,
            'cnpj' => '99888777000166',
            'razao_social' => 'Já Cadastrado',
            'status_participacao' => 'convidado',
        ]);

        FornecedorCatalogo::query()->create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'cnpj' => '99888777000166',
            'razao_social' => 'Catálogo Serviços PA',
            'ativo' => true,
        ]);

        $response = $this->postJson(
            "/api/v1/contratacao/compras/vendor-list/{$contratacao->uuid}/sugestoes-fornecedores",
            [],
            ['Authorization' => "Bearer {$token}"],
        );

        $response->assertOk();
        $cnpjs = collect($response->json('sugestoes'))->pluck('cnpj')->all();
        $this->assertNotContains('99888777000166', $cnpjs);
    }

    public function test_cache_hit_na_segunda_chamada(): void
    {
        [$tenant, $contratacao, $token] = $this->seedVendorListAtiva();

        FornecedorCatalogo::query()->create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'cnpj' => '99888777000166',
            'razao_social' => 'Catálogo Cache',
            'ativo' => true,
        ]);

        $url = "/api/v1/contratacao/compras/vendor-list/{$contratacao->uuid}/sugestoes-fornecedores";
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->postJson($url, [], $headers)->assertOk()->assertJsonPath('meta.cache_hit', false);
        $this->postJson($url, [], $headers)->assertOk()->assertJsonPath('meta.cache_hit', true);
    }

    public function test_forcar_regeneracao_ignora_cache(): void
    {
        [$tenant, $contratacao, $token] = $this->seedVendorListAtiva();

        FornecedorCatalogo::query()->create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'cnpj' => '99888777000166',
            'razao_social' => 'Catálogo Cache',
            'ativo' => true,
        ]);

        $url = "/api/v1/contratacao/compras/vendor-list/{$contratacao->uuid}/sugestoes-fornecedores";
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->postJson($url, [], $headers)->assertOk();
        $this->postJson($url, ['forcar_regeneracao' => true], $headers)
            ->assertOk()
            ->assertJsonPath('meta.cache_hit', false);
    }

    public function test_n8n_completa_sugestoes_quando_habilitado(): void
    {
        config([
            'contratacao.sugestao.n8n.enabled' => true,
            'contratacao.sugestao.n8n.webhook_url' => 'https://n8n.test/webhook/sugestao',
            'contratacao.sugestao.n8n.webhook_secret' => 'secret-test',
        ]);

        Http::fake([
            'https://n8n.test/*' => Http::response([
                'sugestoes' => [
                    [
                        'cnpj' => '55444333000155',
                        'razao_social' => 'IA Externa Ltda',
                        'telefone' => '91988887777',
                        'email' => 'ia@externa.com',
                        'cidade' => 'Belém',
                        'uf' => 'PA',
                        'motivo' => 'Sugestão via n8n.',
                    ],
                ],
            ], 200),
        ]);

        [$tenant, $contratacao, $token] = $this->seedVendorListAtiva();

        $response = $this->postJson(
            "/api/v1/contratacao/compras/vendor-list/{$contratacao->uuid}/sugestoes-fornecedores",
            [],
            ['Authorization' => "Bearer {$token}"],
        );

        $response->assertOk()
            ->assertJsonPath('sugestoes.0.origem', 'ia_externa')
            ->assertJsonPath('sugestoes.0.razao_social', 'IA Externa Ltda');

        Http::assertSentCount(1);
    }

    public function test_web_search_completa_sugestoes_quando_sem_catalogo(): void
    {
        config([
            'contratacao.sugestao.web_search.enabled' => true,
        ]);

        $html = <<<'HTML'
<html><body>
<a class="result__a" href="#">Manutenção Vilela Serviços Ltda - Contato</a>
<a class="result__snippet">Empresa de manutenção predial em Travessa Vilela. Tel (91) 98888-7777 CNPJ 11.222.333/0001-81</a>
<a class="result__a" href="#">Eletrica Norte Manutenção Belém</a>
<a class="result__snippet">Serviços elétricos e prediais perto de Trav Vilela.</a>
</body></html>
HTML;

        Http::fake([
            'html.duckduckgo.com/*' => Http::response($html, 200),
        ]);

        [$tenant, $contratacao, $token] = $this->seedVendorListAtiva();
        $contratacao->update([
            'categoria_servico' => 'Manutenção',
            'local' => 'Trav Vilela',
        ]);

        $response = $this->postJson(
            "/api/v1/contratacao/compras/vendor-list/{$contratacao->uuid}/sugestoes-fornecedores",
            ['forcar_regeneracao' => true],
            ['Authorization' => "Bearer {$token}"],
        );

        $response->assertOk()
            ->assertJsonPath('sugestoes.0.origem', 'ia_externa')
            ->assertJsonPath('sugestoes.0.razao_social', 'Manutenção Vilela Serviços Ltda');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'duckduckgo.com'));
    }

    public function test_llm_completa_sugestoes_quando_chave_configurada(): void
    {
        config([
            'contratacao.sugestao.llm.api_key' => 'sk-test',
            'contratacao.sugestao.llm.base_url' => 'https://api.openai.test/v1',
            'contratacao.sugestao.llm.model' => 'gpt-test',
            'contratacao.sugestao.web_search.enabled' => false,
        ]);

        Http::fake([
            'api.openai.test/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'sugestoes' => [
                                    [
                                        'cnpj' => '',
                                        'razao_social' => 'Manut Predial Amazônia',
                                        'telefone' => '91999990000',
                                        'email' => null,
                                        'cidade' => 'Belém',
                                        'uf' => 'PA',
                                        'motivo' => 'Especializada em manutenção predial na região.',
                                    ],
                                ],
                            ], JSON_THROW_ON_ERROR),
                        ],
                    ],
                ],
            ], 200),
        ]);

        [$tenant, $contratacao, $token] = $this->seedVendorListAtiva();

        $response = $this->postJson(
            "/api/v1/contratacao/compras/vendor-list/{$contratacao->uuid}/sugestoes-fornecedores",
            ['forcar_regeneracao' => true],
            ['Authorization' => "Bearer {$token}"],
        );

        $response->assertOk()
            ->assertJsonPath('sugestoes.0.origem', 'ia_externa')
            ->assertJsonPath('sugestoes.0.razao_social', 'Manut Predial Amazônia');
    }

    public function test_bloqueia_contratacao_fora_de_vendor_list(): void
    {
        [$tenant, $contratacao, $token] = $this->seedVendorListAtiva();
        $contratacao->status = ContratacaoStatus::EM_ANALISE;
        $contratacao->save();

        $this->postJson(
            "/api/v1/contratacao/compras/vendor-list/{$contratacao->uuid}/sugestoes-fornecedores",
            [],
            ['Authorization' => "Bearer {$token}"],
        )->assertUnprocessable();
    }

    /**
     * @return array{0: Tenant, 1: Contratacao, 2: string}
     */
    private function seedVendorListAtiva(): array
    {
        $tenant = Tenant::query()->create([
            'slug' => 'cliente-sugestao',
            'razao_social' => 'Cliente Sugestão',
            'cnpj' => '04252011000110',
            'status' => 'ativo',
            'trial_starts_at' => Carbon::now(),
            'trial_ends_at' => Carbon::now()->addDays(15),
            'subscription_status' => 'trial',
        ]);

        UsuarioCliente::query()->create([
            'tenant_id' => $tenant->id,
            'nome' => 'Compras Sugestão',
            'email' => 'compras@sugestao.local',
            'password' => 'password',
            'perfil' => 'compras',
            'status' => 'ativo',
        ]);

        $contratacao = $this->criarContratacao($tenant, 'Serviços', 'Belém - PA');
        $contratacao->status = ContratacaoStatus::EM_VENDOR_LIST;
        $contratacao->save();

        $token = $this->loginToken($tenant, 'compras@sugestao.local', 'password');

        return [$tenant, $contratacao, $token];
    }

    private function criarContratacao(Tenant $tenant, string $categoria, string $local): Contratacao
    {
        $usuario = UsuarioCliente::query()->where('tenant_id', $tenant->id)->first();

        return Contratacao::query()->create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'criado_por_usuario_id' => $usuario?->id,
            'titulo' => 'Contratação teste',
            'categoria_servico' => $categoria,
            'local' => $local,
            'termo_referencia' => 'Termo de referência teste',
            'status' => ContratacaoStatus::EM_VENDOR_LIST,
        ]);
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

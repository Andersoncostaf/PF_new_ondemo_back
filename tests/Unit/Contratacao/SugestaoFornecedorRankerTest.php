<?php

namespace Tests\Unit\Contratacao;

use App\Modules\Contratacao\Application\Service\SugestaoFornecedorRanker;
use PHPUnit\Framework\TestCase;

class SugestaoFornecedorRankerTest extends TestCase
{
    private SugestaoFornecedorRanker $ranker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ranker = new SugestaoFornecedorRanker;
    }

    public function test_historico_pontua_mais_que_catalogo_com_mesmo_perfil(): void
    {
        $contexto = [
            'categoria_servico' => 'Manutenção elétrica',
            'local_tokens' => ['belém', 'pa'],
            'termo_tokens' => ['elétrica', 'quadro'],
        ];

        $candidatos = [
            [
                'cnpj' => '11111111000191',
                'razao_social' => 'Histórico Elétrica',
                'origem' => 'historico_tenant',
                'categoria_servico' => 'Manutenção elétrica',
                'local' => 'Belém - PA',
                'participacoes' => 2,
            ],
            [
                'cnpj' => '22222222000182',
                'razao_social' => 'Catálogo Elétrica',
                'origem' => 'catalogo_tenant',
                'categoria_servico' => 'Manutenção elétrica',
                'cidade' => 'Belém',
                'uf' => 'PA',
                'participacoes' => 0,
            ],
        ];

        $resultado = $this->ranker->ranquear($candidatos, $contexto, [], 3);

        $this->assertCount(2, $resultado);
        $this->assertSame('historico_tenant', $resultado[0]['origem']);
        $this->assertGreaterThan($resultado[1]['score'], $resultado[0]['score']);
    }

    public function test_exclui_cnpjs_ja_cadastrados(): void
    {
        $contexto = [
            'categoria_servico' => 'Serviços',
            'local_tokens' => [],
            'termo_tokens' => [],
        ];

        $candidatos = [
            [
                'cnpj' => '04252011000110',
                'razao_social' => 'Fornecedor A',
                'origem' => 'catalogo_tenant',
            ],
        ];

        $resultado = $this->ranker->ranquear($candidatos, $contexto, ['04252011000110'], 3);

        $this->assertSame([], $resultado);
    }

    public function test_dedupe_por_cnpj_mantem_maior_score(): void
    {
        $contexto = [
            'categoria_servico' => 'Serviços',
            'local_tokens' => [],
            'termo_tokens' => [],
        ];

        $candidatos = [
            [
                'cnpj' => '04252011000110',
                'razao_social' => 'Fornecedor Catálogo',
                'origem' => 'catalogo_tenant',
            ],
            [
                'cnpj' => '04.252.011/0001-10',
                'razao_social' => 'Fornecedor Histórico',
                'origem' => 'historico_tenant',
                'participacoes' => 1,
            ],
        ];

        $resultado = $this->ranker->ranquear($candidatos, $contexto, [], 3);

        $this->assertCount(1, $resultado);
        $this->assertSame('historico_tenant', $resultado[0]['origem']);
    }
}

<?php

namespace Tests\Unit\Contratacao;

use App\Models\Contratacao;
use App\Models\ContratacaoApontamento;
use App\Modules\Contratacao\Domain\ContratacaoStatus;
use App\Modules\Contratacao\Domain\Policies\ApontamentoContratacaoPendente;
use App\Modules\Contratacao\Domain\Policies\ContratacaoElegivelParaAprovarAnalise;
use App\Modules\Contratacao\Domain\Policies\ContratacaoElegivelParaAssumirAnalise;
use App\Modules\Contratacao\Domain\Policies\ContratacaoElegivelParaRetornarAjustes;
use PHPUnit\Framework\TestCase;

class ContratacaoAprovacaoPoliciesTest extends TestCase
{
    public function test_contratacao_elegivel_para_assumir_analise(): void
    {
        $contratacao = new Contratacao(['status' => ContratacaoStatus::AGUARDANDO_ANALISE_COMPRAS]);

        $this->assertTrue(ContratacaoElegivelParaAssumirAnalise::check($contratacao));

        $contratacao->status = ContratacaoStatus::EM_ANALISE;
        $this->assertFalse(ContratacaoElegivelParaAssumirAnalise::check($contratacao));
    }

    public function test_contratacao_elegivel_para_retornar_ajustes(): void
    {
        $contratacao = new Contratacao(['status' => ContratacaoStatus::EM_ANALISE]);

        $this->assertTrue(ContratacaoElegivelParaRetornarAjustes::check($contratacao, 1));
        $this->assertFalse(ContratacaoElegivelParaRetornarAjustes::check($contratacao, 0));
    }

    public function test_contratacao_elegivel_para_aprovar_analise(): void
    {
        $contratacao = new Contratacao(['status' => ContratacaoStatus::EM_ANALISE]);

        $this->assertTrue(ContratacaoElegivelParaAprovarAnalise::check($contratacao, 0));
        $this->assertFalse(ContratacaoElegivelParaAprovarAnalise::check($contratacao, 2));
    }

    public function test_apontamento_pendente(): void
    {
        $pendente = new ContratacaoApontamento(['status' => 'pendente']);
        $respondido = new ContratacaoApontamento(['status' => 'respondido']);

        $this->assertTrue(ApontamentoContratacaoPendente::estaPendente($pendente));
        $this->assertFalse(ApontamentoContratacaoPendente::estaPendente($respondido));
    }
}

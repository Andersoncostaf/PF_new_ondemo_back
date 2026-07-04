<?php

namespace Tests\Unit\Contratacao;

use App\Models\Contratacao;
use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Domain\ContratacaoStatus;
use App\Modules\Contratacao\Domain\Policies\ContratacaoElegivelParaVendorList;
use App\Modules\Contratacao\Domain\Policies\FornecedorCnpjUnicoNaContratacao;
use App\Modules\Contratacao\Domain\Policies\UsuarioPodeEditarVendorList;
use PHPUnit\Framework\TestCase;

class ContratacaoVendorListPoliciesTest extends TestCase
{
    public function test_contratacao_elegivel_para_vendor_list(): void
    {
        $contratacao = new Contratacao(['status' => ContratacaoStatus::EM_VENDOR_LIST]);
        $this->assertTrue(ContratacaoElegivelParaVendorList::check($contratacao));

        $contratacao->status = ContratacaoStatus::EM_ANALISE;
        $this->assertFalse(ContratacaoElegivelParaVendorList::check($contratacao));
    }

    public function test_fornecedor_cnpj_unico_na_contratacao(): void
    {
        $this->assertTrue(FornecedorCnpjUnicoNaContratacao::check('04.252.011/0001-10', ['11222333000181']));
        $this->assertFalse(FornecedorCnpjUnicoNaContratacao::check('04252011000110', ['04252011000110']));
        $this->assertTrue(FornecedorCnpjUnicoNaContratacao::cnpjValido('04252011000110'));
        $this->assertFalse(FornecedorCnpjUnicoNaContratacao::cnpjValido('123'));
    }

    public function test_usuario_pode_editar_vendor_list(): void
    {
        $analistaId = 'a22dc996-0000-4000-8000-000000000001';
        $contratacao = new Contratacao(['analista_usuario_id' => $analistaId]);

        $compras = new UsuarioCliente(['perfil' => 'compras']);
        $compras->id = 'u-compras';
        $gestor = new UsuarioCliente(['perfil' => 'gestor']);
        $gestor->id = 'u-gestor';
        $analista = new UsuarioCliente(['perfil' => 'area']);
        $analista->id = $analistaId;

        $this->assertTrue(UsuarioPodeEditarVendorList::check($compras, $contratacao));
        $this->assertFalse(UsuarioPodeEditarVendorList::check($gestor, $contratacao));
        $this->assertTrue(UsuarioPodeEditarVendorList::check($analista, $contratacao));
    }
}

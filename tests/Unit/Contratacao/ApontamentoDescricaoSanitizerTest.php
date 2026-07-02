<?php

namespace Tests\Unit\Contratacao;

use App\Support\ApontamentoDescricaoSanitizer;
use PHPUnit\Framework\TestCase;

class ApontamentoDescricaoSanitizerTest extends TestCase
{
    public function test_rejeita_descricao_vazia_ou_so_html_vazio(): void
    {
        $this->assertFalse(ApontamentoDescricaoSanitizer::temConteudoGravavel(
            ApontamentoDescricaoSanitizer::sanitize('<p><br></p>'),
        ));
        $this->assertFalse(ApontamentoDescricaoSanitizer::temConteudoGravavel(
            ApontamentoDescricaoSanitizer::sanitize('   '),
        ));
    }

    public function test_aceita_texto_util(): void
    {
        $this->assertTrue(ApontamentoDescricaoSanitizer::temConteudoGravavel(
            ApontamentoDescricaoSanitizer::sanitize('<p>Ajustar escopo</p>'),
        ));
    }
}

<?php

namespace Tests\Unit\Identidade;

use App\Modules\Identidade\Domain\Services\CnpjValidator;
use PHPUnit\Framework\TestCase;

class CnpjValidatorTest extends TestCase
{
    public function test_valida_cnpj_correto(): void
    {
        $this->assertTrue(CnpjValidator::isValid('11222333000181'));
        $this->assertTrue(CnpjValidator::isValid('04.252.011/0001-10'));
    }

    public function test_rejeita_cnpj_invalido(): void
    {
        $this->assertFalse(CnpjValidator::isValid('12345678000199'));
        $this->assertFalse(CnpjValidator::isValid('00000000000000'));
        $this->assertFalse(CnpjValidator::isValid('123'));
    }

    public function test_normaliza_apenas_digitos(): void
    {
        $this->assertSame('04252011000110', CnpjValidator::normalize('04.252.011/0001-10'));
    }
}

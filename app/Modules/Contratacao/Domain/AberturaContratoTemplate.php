<?php

namespace App\Modules\Contratacao\Domain;

/**
 * Checklist canônico da Abertura de Contrato (clonado por fornecedor).
 * Validades: FGTS 30d; Federal/Estadual/Trabalhista/INSS/Falência 180d;
 * Prefeitura 90d; Alvará 365d. Item 5 é condicional (optante do Simples).
 */
final class AberturaContratoTemplate
{
    public const CONDICAO_OPTANTE_SIMPLES = 'optante_simples';

    /**
     * @return list<array{codigo:string,label:string,ordem:int,obrigatorio:bool,condicional:bool,condicao:?string,controla_vencimento:bool,validade_dias:?int,parent_codigo:?string}>
     */
    public static function itens(): array
    {
        return [
            self::item('1', 'Contrato Social e Alterações', 1),
            self::item('2', 'Cartão CNPJ', 2),
            self::item('3', 'Inscrição Estadual', 3),
            self::item('4', 'Alvará de Localização e Funcionamento - Municipal', 4, controlaVencimento: true, validadeDias: 365),
            self::item('5', 'Declaração do Simples Nacional (Anexo I-V p/ INSS + alíquota ISS)', 5, condicional: true, condicao: self::CONDICAO_OPTANTE_SIMPLES),
            self::item('6', 'CND - Certidão Negativa de Débitos', 6, obrigatorio: false),
            self::item('6.1', 'Receita Federal / Dívida Ativa da União', 7, controlaVencimento: true, validadeDias: 180, parentCodigo: '6'),
            self::item('6.2', 'INSS', 8, controlaVencimento: true, validadeDias: 180, parentCodigo: '6'),
            self::item('6.3', 'FGTS', 9, controlaVencimento: true, validadeDias: 30, parentCodigo: '6'),
            self::item('6.4', 'Estadual', 10, controlaVencimento: true, validadeDias: 180, parentCodigo: '6'),
            self::item('6.5', 'Prefeitura (Municipal)', 11, controlaVencimento: true, validadeDias: 90, parentCodigo: '6'),
            self::item('6.6', 'Falência (Cível)', 12, controlaVencimento: true, validadeDias: 180, parentCodigo: '6'),
            self::item('6.7', 'Débitos Trabalhistas (CNDT)', 13, controlaVencimento: true, validadeDias: 180, parentCodigo: '6'),
        ];
    }

    /**
     * @return array{codigo:string,label:string,ordem:int,obrigatorio:bool,condicional:bool,condicao:?string,controla_vencimento:bool,validade_dias:?int,parent_codigo:?string}
     */
    private static function item(
        string $codigo,
        string $label,
        int $ordem,
        bool $obrigatorio = true,
        bool $condicional = false,
        ?string $condicao = null,
        bool $controlaVencimento = false,
        ?int $validadeDias = null,
        ?string $parentCodigo = null,
    ): array {
        return [
            'codigo' => $codigo,
            'label' => $label,
            'ordem' => $ordem,
            'obrigatorio' => $obrigatorio,
            'condicional' => $condicional,
            'condicao' => $condicao,
            'controla_vencimento' => $controlaVencimento,
            'validade_dias' => $validadeDias,
            'parent_codigo' => $parentCodigo,
        ];
    }
}

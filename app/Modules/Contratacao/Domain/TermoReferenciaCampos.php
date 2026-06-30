<?php

namespace App\Modules\Contratacao\Domain;

final class TermoReferenciaCampos
{
    public const KEYS = [
        'objetivo',
        'escopo',
        'gestor_contrato',
        'materiais_recursos',
        'responsabilidade_contratante',
        'responsabilidade_contratada',
        'ferramentas_equipamentos',
        'mao_de_obra',
        'regime_trabalho',
        'documentos_exigidos',
        'prazo_execucao',
        'formas_pagamento',
        'subcontratacao',
        'comissionamento',
        'condicoes_gerais',
        'visita_tecnica',
    ];

    /**
     * @param array<string, mixed>|null $campos
     */
    public static function isComplete(?array $campos): bool
    {
        if ($campos === null) {
            return false;
        }

        foreach (self::KEYS as $key) {
            if (blank($campos[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed>|null $campos
     *
     * @return list<string>
     */
    public static function missingKeys(?array $campos): array
    {
        $missing = [];

        foreach (self::KEYS as $key) {
            if ($campos === null || blank($campos[$key] ?? null)) {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    /**
     * @param array<string, mixed> $campos
     */
    public static function toText(array $campos): string
    {
        $labels = [
            'objetivo' => 'Objetivo',
            'escopo' => 'Escopo',
            'gestor_contrato' => 'Gestor do contrato',
            'materiais_recursos' => 'Materiais / recursos',
            'responsabilidade_contratante' => 'Responsabilidade da contratante',
            'responsabilidade_contratada' => 'Responsabilidade da contratada',
            'ferramentas_equipamentos' => 'Ferramentas e equipamentos',
            'mao_de_obra' => 'Mão de obra',
            'regime_trabalho' => 'Regime de trabalho',
            'documentos_exigidos' => 'Documentos exigidos',
            'prazo_execucao' => 'Prazo de execução',
            'formas_pagamento' => 'Formas de pagamento',
            'subcontratacao' => 'Subcontratação',
            'comissionamento' => 'Comissionamento',
            'condicoes_gerais' => 'Condições gerais',
            'visita_tecnica' => 'Visita técnica',
        ];

        $sections = [];

        foreach (self::KEYS as $key) {
            $value = trim((string) ($campos[$key] ?? ''));

            if ($value === '') {
                continue;
            }

            $sections[] = ($labels[$key] ?? $key)."\n".$value;
        }

        return implode("\n\n", $sections);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, string|null>
     */
    public static function normalize(array $data): array
    {
        $normalized = [];

        foreach (self::KEYS as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];
            $normalized[$key] = $value !== null ? trim((string) $value) : null;
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public static function validationRules(string $prefix = 'termo_referencia_campos'): array
    {
        $rules = [
            $prefix => ['sometimes', 'nullable', 'array'],
        ];

        foreach (self::KEYS as $key) {
            $rules["{$prefix}.{$key}"] = ['nullable', 'string', 'max:10000'];
        }

        return $rules;
    }
}

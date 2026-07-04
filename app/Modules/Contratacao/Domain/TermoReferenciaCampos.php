<?php

namespace App\Modules\Contratacao\Domain;

final class TermoReferenciaCampos
{
    public const CUSTOM_KEY = 'campos_personalizados';

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
            if (! self::fieldHasContent($campos[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private static function normalizeFieldValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags(str_replace('&nbsp;', ' ', $trimmed)) ?? ''));

        if ($plain === '') {
            return '';
        }

        return $trimmed;
    }

    private static function fieldHasContent(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        $normalized = self::normalizeFieldValue((string) $value);

        return $normalized !== null && $normalized !== '';
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
            if ($campos === null || ! self::fieldHasContent($campos[$key] ?? null)) {
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

        foreach (self::customFieldsFrom($campos) as $custom) {
            $sections[] = $custom['titulo']."\n".$custom['conteudo'];
        }

        return implode("\n\n", $sections);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        $normalized = [];

        foreach (self::KEYS as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];
            $normalized[$key] = self::normalizeFieldValue($value !== null ? (string) $value : null);
        }

        if (array_key_exists(self::CUSTOM_KEY, $data) && is_array($data[self::CUSTOM_KEY])) {
            $custom = self::normalizeCustomFields($data[self::CUSTOM_KEY]);

            if ($custom !== []) {
                $normalized[self::CUSTOM_KEY] = $custom;
            }
        }

        return $normalized;
    }

    /**
     * @param array<int, mixed> $items
     *
     * @return list<array{id: string, titulo: string, conteudo: string, ordem: int}>
     */
    public static function normalizeCustomFields(array $items): array
    {
        $normalized = [];

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $titulo = trim((string) ($item['titulo'] ?? ''));
            $conteudo = self::normalizeFieldValue(isset($item['conteudo']) ? (string) $item['conteudo'] : null) ?? '';

            if ($titulo === '' && $conteudo === '') {
                continue;
            }

            $normalized[] = [
                'id' => ! blank($item['id'] ?? null) ? (string) $item['id'] : (string) \Illuminate\Support\Str::uuid(),
                'titulo' => $titulo,
                'conteudo' => $conteudo,
                'ordem' => (int) ($item['ordem'] ?? $index),
            ];
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed>|null $campos
     *
     * @return list<array{titulo: string, conteudo: string}>
     */
    public static function customFieldsFrom(?array $campos): array
    {
        if ($campos === null || ! isset($campos[self::CUSTOM_KEY]) || ! is_array($campos[self::CUSTOM_KEY])) {
            return [];
        }

        $fields = [];

        foreach ($campos[self::CUSTOM_KEY] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $titulo = trim((string) ($item['titulo'] ?? ''));
            $conteudo = trim((string) ($item['conteudo'] ?? ''));

            if ($titulo === '' || $conteudo === '') {
                continue;
            }

            $fields[] = [
                'titulo' => $titulo,
                'conteudo' => $conteudo,
            ];
        }

        return $fields;
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

        $rules["{$prefix}.".self::CUSTOM_KEY] = ['sometimes', 'nullable', 'array'];
        $rules["{$prefix}.".self::CUSTOM_KEY.'.*.id'] = ['nullable', 'string', 'max:64'];
        $rules["{$prefix}.".self::CUSTOM_KEY.'.*.titulo'] = ['nullable', 'string', 'max:255'];
        $rules["{$prefix}.".self::CUSTOM_KEY.'.*.conteudo'] = ['nullable', 'string', 'max:10000'];
        $rules["{$prefix}.".self::CUSTOM_KEY.'.*.ordem'] = ['nullable', 'integer', 'min:0'];

        return $rules;
    }
}

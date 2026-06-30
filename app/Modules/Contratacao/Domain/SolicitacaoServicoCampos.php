<?php

namespace App\Modules\Contratacao\Domain;

final class SolicitacaoServicoCampos
{
    public const KEYS = [
        'codigo_servico',
        'centro_custo',
        'projeto',
        'fase',
        'conta_financeira',
        'conta_contabil',
        'transacao',
        'valor_servico',
        'observacao_ss',
    ];

    /**
     * @param array<string, mixed>|null $data
     * @return array<string, string|null>|null
     */
    public static function normalize(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $normalized = [];

        foreach (self::KEYS as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];

            if ($value === null || $value === '') {
                $normalized[$key] = null;
                continue;
            }

            $normalized[$key] = is_scalar($value) ? (string) $value : null;
        }

        $hasValue = collect($normalized)->contains(fn ($v) => $v !== null && $v !== '');

        return $hasValue ? $normalized : null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function validationRules(): array
    {
        $rules = [];

        foreach (self::KEYS as $key) {
            $rules["solicitacao_servico.{$key}"] = ['nullable', 'string', 'max:255'];
        }

        $rules['solicitacao_servico.observacao_ss'] = ['nullable', 'string', 'max:2000'];

        return $rules;
    }
}

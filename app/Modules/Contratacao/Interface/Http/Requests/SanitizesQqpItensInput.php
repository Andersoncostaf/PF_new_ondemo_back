<?php

namespace App\Modules\Contratacao\Interface\Http\Requests;

trait SanitizesQqpItensInput
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('qqp_itens') || ! is_array($this->input('qqp_itens'))) {
            return;
        }

        $filtered = array_values(array_filter(
            $this->input('qqp_itens'),
            static fn ($item): bool => is_array($item) && trim((string) ($item['descricao'] ?? '')) !== ''
        ));

        $this->merge([
            'qqp_itens' => $filtered === [] ? null : $filtered,
        ]);
    }
}

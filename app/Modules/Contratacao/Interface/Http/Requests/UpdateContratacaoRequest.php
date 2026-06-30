<?php

namespace App\Modules\Contratacao\Interface\Http\Requests;

class UpdateContratacaoRequest extends ContratacaoRequestBase
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = $this->sharedRules();

        foreach (array_keys($rules) as $key) {
            if (! str_starts_with($key, 'solicitacao_servico.')) {
                $rules[$key] = array_merge(['sometimes'], (array) $rules[$key]);
            }
        }

        $rules['solicitacao_servico'] = ['sometimes', 'nullable', 'array'];

        return $rules;
    }
}

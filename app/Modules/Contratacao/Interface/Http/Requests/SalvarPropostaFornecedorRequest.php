<?php

namespace App\Modules\Contratacao\Interface\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalvarPropostaFornecedorRequest extends FormRequest
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
        return [
            'proposta_inicial' => ['nullable', 'numeric', 'min:0'],
            'proposta_equalizada' => ['nullable', 'numeric', 'min:0'],
            'proposta_final' => ['nullable', 'numeric', 'min:0'],
            'condicao_pagamento_dias' => ['nullable', 'integer', 'min:0'],
            'observacao_proposta' => ['nullable', 'string'],
            'optante_simples' => ['nullable', 'boolean'],
        ];
    }
}

<?php

namespace App\Modules\Contratacao\Interface\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContratacaoRequest extends FormRequest
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
            'titulo' => ['nullable', 'string', 'max:255'],
            'categoria_servico' => ['nullable', 'string', 'max:128'],
            'local' => ['nullable', 'string', 'max:255'],
            'prazo_desejado' => ['nullable', 'date'],
            'termo_referencia' => ['nullable', 'string'],
            'qqp_itens' => ['nullable', 'array'],
            'qqp_itens.*.descricao' => ['required_with:qqp_itens', 'string', 'max:500'],
            'qqp_itens.*.quantidade' => ['nullable', 'numeric', 'min:0.0001'],
            'qqp_itens.*.unidade' => ['nullable', 'string', 'max:32'],
            'qqp_itens.*.ordem' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

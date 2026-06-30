<?php

namespace App\Modules\Contratacao\Interface\Http\Requests;

use App\Modules\Contratacao\Domain\TermoReferenciaCampos;
use Illuminate\Foundation\Http\FormRequest;

class UpdateContratacaoRequest extends FormRequest
{
    use SanitizesQqpItensInput;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
            'titulo' => ['sometimes', 'nullable', 'string', 'max:255'],
            'categoria_servico' => ['sometimes', 'nullable', 'string', 'max:128'],
            'local' => ['sometimes', 'nullable', 'string', 'max:255'],
            'prazo_desejado' => ['sometimes', 'nullable', 'date'],
            'termo_referencia' => ['sometimes', 'nullable', 'string'],
            'qqp_itens' => ['sometimes', 'nullable', 'array'],
            'qqp_itens.*.descricao' => ['required_with:qqp_itens', 'string', 'max:500'],
            'qqp_itens.*.quantidade' => ['nullable', 'numeric', 'min:0.0001'],
            'qqp_itens.*.unidade' => ['nullable', 'string', 'max:32'],
            'qqp_itens.*.ordem' => ['nullable', 'integer', 'min:0'],
        ], TermoReferenciaCampos::validationRules());
    }
}

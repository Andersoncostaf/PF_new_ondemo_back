<?php

namespace App\Modules\Contratacao\Interface\Http\Requests;

use App\Modules\Contratacao\Domain\AvaliacaoTecnicaCriterios;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarAvaliacaoTecnicaRequest extends FormRequest
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
            'observacao' => ['nullable', 'string'],
            'fornecedor_vencedor_uuid' => ['nullable', 'string', 'uuid'],
            'itens' => ['nullable', 'array'],
            'itens.*.codigo' => ['required_with:itens', 'string', Rule::in(AvaliacaoTecnicaCriterios::codigosValidos())],
            'itens.*.nota' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'itens.*.observacao' => ['nullable', 'string'],
        ];
    }
}

<?php

namespace App\Modules\Contratacao\Interface\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DefinirFornecedorVencedorRequest extends FormRequest
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
            'fornecedor_uuid' => ['required', 'string', 'uuid'],
        ];
    }
}

<?php

namespace App\Modules\Contratacao\Interface\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GerarSugestoesFornecedorRequest extends FormRequest
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
            'limite' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'forcar_regeneracao' => ['sometimes', 'boolean'],
        ];
    }
}

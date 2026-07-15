<?php

namespace App\Modules\Contratacao\Interface\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DispensarVisitaTecnicaRequest extends FormRequest
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
            'justificativa' => ['nullable', 'string', 'max:600'],
            'observacao' => ['nullable', 'string', 'max:600'],
        ];
    }
}

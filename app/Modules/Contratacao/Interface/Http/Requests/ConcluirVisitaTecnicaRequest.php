<?php

namespace App\Modules\Contratacao\Interface\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConcluirVisitaTecnicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->hora) && preg_match('/^\d{2}:\d{2}/', $this->hora)) {
            $this->merge(['hora' => substr($this->hora, 0, 5)]);
        }
    }

    public function rules(): array
    {
        return [
            'observacao' => ['nullable', 'string', 'max:600'],
            'data' => ['nullable', 'date', 'required_with:hora,local'],
            'hora' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/', 'required_with:data,local'],
            'local' => ['nullable', 'string', 'max:500', 'required_with:data,hora'],
        ];
    }
}

<?php

namespace App\Modules\Contratacao\Interface\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgendarVisitaTecnicaRequest extends FormRequest
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
            'data' => ['required', 'date'],
            'hora' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'local' => ['required', 'string', 'max:500'],
            'observacao' => ['nullable', 'string', 'max:600'],
        ];
    }
}

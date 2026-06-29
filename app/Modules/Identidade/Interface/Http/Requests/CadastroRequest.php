<?php

namespace App\Modules\Identidade\Interface\Http\Requests;

use App\Modules\Identidade\Domain\Services\CnpjValidator;
use Illuminate\Foundation\Http\FormRequest;

class CadastroRequest extends FormRequest
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
            'razao_social' => ['required', 'string', 'max:255'],
            'cnpj' => [
                'required',
                'string',
                'size:14',
                'regex:/^\d{14}$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! CnpjValidator::isValid((string) $value)) {
                        $fail('CNPJ inválido.');
                    }
                },
            ],
            'slug' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'cargo' => ['nullable', 'string', 'max:128'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('cnpj')) {
            $this->merge([
                'cnpj' => CnpjValidator::normalize((string) $this->input('cnpj')),
            ]);
        }

        if ($this->has('slug') && is_string($this->input('slug'))) {
            $this->merge([
                'slug' => strtolower(trim($this->input('slug'))),
            ]);
        }
    }
}

<?php

namespace App\Modules\Contratacao\Interface\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnriquecerFornecedorRequest extends FormRequest
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
            'cnpj' => ['nullable', 'string', 'max:20'],
            'razao_social' => ['nullable', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'vendedor' => ['nullable', 'string', 'max:255'],
            'cidade' => ['nullable', 'string', 'max:120'],
            'uf' => ['nullable', 'string', 'max:2'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $cnpj = preg_replace('/\D+/', '', (string) $this->input('cnpj', '')) ?? '';
            $razao = trim((string) $this->input('razao_social', ''));
            if ($cnpj === '' && $razao === '') {
                $validator->errors()->add('razao_social', 'Informe CNPJ ou razão social para enriquecer.');
            }
        });
    }
}

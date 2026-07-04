<?php

namespace App\Modules\Contratacao\Interface\Http\Requests;

use App\Modules\Contratacao\Domain\Policies\FornecedorCnpjUnicoNaContratacao;
use Illuminate\Foundation\Http\FormRequest;

class StoreContratacaoFornecedorRequest extends FormRequest
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
            'cnpj' => ['required', 'string', 'max:20', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! FornecedorCnpjUnicoNaContratacao::cnpjValido((string) $value)) {
                    $fail('Informe um CNPJ válido com 14 dígitos.');
                }
            }],
            'razao_social' => ['required', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'vendedor' => ['nullable', 'string', 'max:255'],
        ];
    }
}

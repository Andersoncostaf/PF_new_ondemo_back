<?php

namespace App\Modules\Contratacao\Interface\Http\Requests;

use App\Modules\Contratacao\Domain\FornecedorUsuarioPerfil;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFornecedorUsuarioRequest extends FormRequest
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
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:32'],
            'perfil' => ['nullable', 'string', Rule::in(FornecedorUsuarioPerfil::todos())],
        ];
    }
}

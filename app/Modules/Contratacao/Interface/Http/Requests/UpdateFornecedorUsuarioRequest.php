<?php

namespace App\Modules\Contratacao\Interface\Http\Requests;

use App\Modules\Contratacao\Domain\FornecedorUsuarioPerfil;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFornecedorUsuarioRequest extends FormRequest
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
            'nome' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:32'],
            'perfil' => ['sometimes', 'string', Rule::in(FornecedorUsuarioPerfil::todos())],
        ];
    }
}

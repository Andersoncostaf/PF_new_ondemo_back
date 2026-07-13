<?php

namespace App\Modules\Identidade\Interface\Http\Requests;

use App\Modules\Identidade\Domain\Policies\PerfilOperacionalColaborador;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AtualizarColaboradorRequest extends FormRequest
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
            'cargo' => ['nullable', 'string', 'max:128'],
            'perfil' => ['sometimes', 'string', Rule::in(PerfilOperacionalColaborador::PERFIS)],
            'status' => ['sometimes', 'string', Rule::in(['ativo', 'inativo'])],
        ];
    }
}

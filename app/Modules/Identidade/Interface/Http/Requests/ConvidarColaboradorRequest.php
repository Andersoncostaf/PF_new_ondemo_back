<?php

namespace App\Modules\Identidade\Interface\Http\Requests;

use App\Modules\Identidade\Domain\Policies\PerfilOperacionalColaborador;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConvidarColaboradorRequest extends FormRequest
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
            'password' => ['required', 'string', 'min:8'],
            'perfil' => ['required', 'string', Rule::in(PerfilOperacionalColaborador::PERFIS)],
            'cargo' => ['nullable', 'string', 'max:128'],
        ];
    }
}

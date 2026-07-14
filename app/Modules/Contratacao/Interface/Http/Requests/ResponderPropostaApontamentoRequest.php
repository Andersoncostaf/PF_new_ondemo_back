<?php

namespace App\Modules\Contratacao\Interface\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResponderPropostaApontamentoRequest extends FormRequest
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
            'resposta' => ['required', 'string', 'min:1'],
        ];
    }
}

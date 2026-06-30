<?php

namespace App\Modules\Contratacao\Interface\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContratacaoAnexoRequest extends FormRequest
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
        $maxKb = (int) config('contratacao.anexos_max_kb', 10240);

        return [
            'descricao' => ['nullable', 'string', 'max:255'],
            'arquivo' => ['required', 'file', 'max:'.$maxKb],
        ];
    }
}

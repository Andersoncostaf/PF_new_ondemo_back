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

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxMb = (int) config('contratacao.anexos_max_kb', 10240) / 1024;

        return [
            'arquivo.required' => 'Selecione um arquivo para anexar.',
            'arquivo.file' => 'O anexo deve ser um arquivo válido.',
            'arquivo.max' => "O arquivo excede o limite de {$maxMb} MB.",
        ];
    }
}

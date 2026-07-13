<?php

namespace App\Modules\Contratacao\Interface\Http\Requests;

use App\Modules\Contratacao\Domain\ContratacaoStatus;
use App\Support\ApontamentoDescricaoSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SalvarApontamentoRequest extends FormRequest
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
            'etapa' => ['required', 'string', Rule::in(ContratacaoStatus::ETAPAS_APONTAMENTO)],
            'descricao' => ['nullable', 'string'],
            'arquivo' => ['nullable', 'file', 'max:51200'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $descricao = ApontamentoDescricaoSanitizer::sanitize((string) $this->input('descricao', ''));
            $temDescricao = ApontamentoDescricaoSanitizer::temConteudoGravavel($descricao);
            $temArquivo = $this->hasFile('arquivo') && $this->file('arquivo')?->isValid();

            if (! $temDescricao && ! $temArquivo) {
                $validator->errors()->add(
                    'descricao',
                    'Informe texto/imagem na descrição ou anexe um arquivo no apontamento.',
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('etapa')) {
            $this->merge(['etapa' => strtolower((string) $this->input('etapa'))]);
        }
    }
}

<?php

namespace App\Modules\Contratacao\Interface\Http\Requests;

use App\Modules\Contratacao\Domain\SolicitacaoServicoCampos;
use App\Modules\Contratacao\Domain\TermoReferenciaCampos;
use Illuminate\Foundation\Http\FormRequest;

abstract class ContratacaoRequestBase extends FormRequest
{
    use SanitizesQqpItensInput;

    /**
     * @return array<string, mixed>
     */
    protected function sharedRules(): array
    {
        return array_merge([
            'titulo' => ['nullable', 'string', 'max:255'],
            'categoria_servico' => ['nullable', 'string', 'max:128'],
            'local' => ['nullable', 'string', 'max:255'],
            'prazo_desejado' => ['nullable', 'date'],
            'termo_referencia' => ['nullable', 'string'],
            'empresa' => ['nullable', 'string', 'max:255'],
            'empresa_cnpj' => ['nullable', 'string', 'max:18'],
            'empresa_endereco' => ['nullable', 'string', 'max:500'],
            'departamento' => ['nullable', 'string', 'max:128'],
            'qqp_itens' => ['nullable', 'array'],
            'qqp_itens.*.descricao' => ['required_with:qqp_itens', 'string', 'max:800'],
            'qqp_itens.*.quantidade' => ['nullable', 'numeric', 'min:0.0001'],
            'qqp_itens.*.unidade' => ['nullable', 'string', 'max:32'],
            'qqp_itens.*.valor_unitario' => ['nullable', 'numeric', 'min:0'],
            'qqp_itens.*.ordem' => ['nullable', 'integer', 'min:0'],
        ], TermoReferenciaCampos::validationRules(), SolicitacaoServicoCampos::validationRules());
    }
}

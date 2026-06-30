<?php

namespace App\Modules\Contratacao\Interface\Http\Requests;

class StoreContratacaoRequest extends ContratacaoRequestBase
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
        return $this->sharedRules();
    }
}

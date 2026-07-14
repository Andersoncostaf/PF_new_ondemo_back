<?php

namespace App\Modules\Contratacao\Interface\Http\Requests;

use App\Modules\Contratacao\Domain\AberturaItemStatusAnalise;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnalisarAberturaItemRequest extends FormRequest
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
            'status_analise' => ['required', 'string', Rule::in(AberturaItemStatusAnalise::analise())],
            'observacao_analise' => ['nullable', 'string'],
            'vencimento' => ['nullable', 'date'],
        ];
    }
}

<?php

namespace App\Modules\Identidade\Interface\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RedefinirSenhaColaboradorRequest extends FormRequest
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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}

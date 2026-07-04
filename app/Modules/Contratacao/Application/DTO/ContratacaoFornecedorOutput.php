<?php

namespace App\Modules\Contratacao\Application\DTO;

use App\Models\ContratacaoFornecedor;

final class ContratacaoFornecedorOutput
{
    /**
     * @return array<string, mixed>
     */
    public static function fromModel(ContratacaoFornecedor $fornecedor): array
    {
        return [
            'uuid' => $fornecedor->uuid,
            'cnpj' => $fornecedor->cnpj,
            'razao_social' => $fornecedor->razao_social,
            'telefone' => $fornecedor->telefone,
            'email' => $fornecedor->email,
            'vendedor' => $fornecedor->vendedor,
            'aceite' => (bool) $fornecedor->aceite,
            'status_participacao' => $fornecedor->status_participacao,
            'created_at' => $fornecedor->created_at?->toIso8601String(),
        ];
    }
}

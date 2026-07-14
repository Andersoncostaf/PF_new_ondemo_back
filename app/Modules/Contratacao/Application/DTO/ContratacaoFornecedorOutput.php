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
            'proposta_inicial' => $fornecedor->proposta_inicial !== null ? (float) $fornecedor->proposta_inicial : null,
            'proposta_equalizada' => $fornecedor->proposta_equalizada !== null ? (float) $fornecedor->proposta_equalizada : null,
            'proposta_final' => $fornecedor->proposta_final !== null ? (float) $fornecedor->proposta_final : null,
            'condicao_pagamento_dias' => $fornecedor->condicao_pagamento_dias,
            'vencedor' => (bool) $fornecedor->vencedor,
            'observacao_proposta' => $fornecedor->observacao_proposta,
            'abertura_contrato_status' => $fornecedor->abertura_contrato_status ?? 'nao_iniciada',
            'abertura_solicitada_em' => $fornecedor->abertura_solicitada_em?->toIso8601String(),
            'abertura_enviada_em' => $fornecedor->abertura_enviada_em?->toIso8601String(),
            'abertura_confirmada_em' => $fornecedor->abertura_confirmada_em?->toIso8601String(),
            'optante_simples' => (bool) $fornecedor->optante_simples,
            'created_at' => $fornecedor->created_at?->toIso8601String(),
        ];
    }
}

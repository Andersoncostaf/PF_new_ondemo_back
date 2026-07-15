<?php

namespace App\Modules\Contratacao\Application\DTO;

use App\Models\ContratacaoFornecedor;
use App\Modules\Contratacao\Domain\VisitaTecnicaResolucao;
use App\Modules\Contratacao\Domain\VisitaTecnicaStatus;

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
            'visita_tecnica_status' => VisitaTecnicaStatus::normalizar($fornecedor->visita_tecnica_status),
            'visita_tecnica_resolucao' => VisitaTecnicaResolucao::normalizar($fornecedor->visita_tecnica_resolucao),
            'visita_tecnica_necessaria' => $fornecedor->visita_tecnica_necessaria,
            'visita_agendada_data' => $fornecedor->visita_agendada_data?->format('Y-m-d'),
            'visita_agendada_hora' => $fornecedor->visita_agendada_hora,
            'visita_agendada_local' => $fornecedor->visita_agendada_local,
            'visita_agendada_por_compras_em' => $fornecedor->visita_agendada_por_compras_em?->toIso8601String(),
            'visita_tecnica_observacao' => $fornecedor->visita_tecnica_observacao,
            'visita_dispensa_justificativa' => $fornecedor->visita_dispensa_justificativa,
            'visita_tecnica_concluida_em' => $fornecedor->visita_tecnica_concluida_em?->toIso8601String(),
            'visita_tecnica_dispensada_em' => $fornecedor->visita_tecnica_dispensada_em?->toIso8601String(),
            'created_at' => $fornecedor->created_at?->toIso8601String(),
        ];
    }
}

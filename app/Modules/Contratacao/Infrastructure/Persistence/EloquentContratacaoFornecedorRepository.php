<?php

namespace App\Modules\Contratacao\Infrastructure\Persistence;

use App\Models\Contratacao;
use App\Models\ContratacaoFornecedor;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoFornecedorRepositoryPort;
use App\Modules\Contratacao\Domain\AberturaContratoStatus;
use App\Modules\Contratacao\Domain\FornecedorParticipacaoStatus;
use App\Modules\Contratacao\Domain\Policies\FornecedorCnpjUnicoNaContratacao;
use App\Modules\Contratacao\Domain\VisitaTecnicaResolucao;
use App\Modules\Contratacao\Domain\VisitaTecnicaStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class EloquentContratacaoFornecedorRepository implements ContratacaoFornecedorRepositoryPort
{
    public function listByContratacao(Contratacao $contratacao): Collection
    {
        return $contratacao->fornecedores()->orderBy('created_at')->get();
    }

    public function findByUuidForContratacao(Contratacao $contratacao, string $fornecedorUuid): ?ContratacaoFornecedor
    {
        return $contratacao->fornecedores()
            ->where(function ($q) use ($fornecedorUuid) {
                $q->where('uuid', $fornecedorUuid)->orWhere('id', $fornecedorUuid);
            })
            ->first();
    }

    public function countByContratacao(Contratacao $contratacao): int
    {
        return $contratacao->fornecedores()->count();
    }

    public function create(Contratacao $contratacao, string $tenantId, array $attributes): ContratacaoFornecedor
    {
        $cnpj = FornecedorCnpjUnicoNaContratacao::normalizarCnpj((string) ($attributes['cnpj'] ?? ''));

        return ContratacaoFornecedor::query()->create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'contratacao_id' => $contratacao->id,
            'cnpj' => $cnpj,
            'razao_social' => $attributes['razao_social'],
            'telefone' => $attributes['telefone'] ?? null,
            'email' => $attributes['email'] ?? null,
            'vendedor' => $attributes['vendedor'] ?? null,
            'aceite' => false,
            'status_participacao' => FornecedorParticipacaoStatus::CONVIDADO,
            'vencedor' => false,
            'abertura_contrato_status' => AberturaContratoStatus::NAO_INICIADA,
            'optante_simples' => false,
            'visita_tecnica_status' => VisitaTecnicaStatus::NAO_INICIADA,
            'visita_tecnica_resolucao' => VisitaTecnicaResolucao::PENDENTE,
        ]);
    }

    public function marcarAceiteParticipacao(ContratacaoFornecedor $fornecedor): ContratacaoFornecedor
    {
        $fornecedor->aceite = true;
        $fornecedor->status_participacao = FornecedorParticipacaoStatus::ACEITO;
        $fornecedor->save();

        return $fornecedor->fresh() ?? $fornecedor;
    }

    public function updateProposta(ContratacaoFornecedor $fornecedor, array $dados): ContratacaoFornecedor
    {
        foreach ([
            'proposta_inicial',
            'proposta_equalizada',
            'proposta_final',
            'condicao_pagamento_dias',
            'observacao_proposta',
            'optante_simples',
        ] as $campo) {
            if (array_key_exists($campo, $dados)) {
                $fornecedor->{$campo} = $dados[$campo];
            }
        }

        if ($fornecedor->status_participacao === FornecedorParticipacaoStatus::CONVIDADO
            || $fornecedor->status_participacao === FornecedorParticipacaoStatus::ACEITO) {
            $fornecedor->status_participacao = FornecedorParticipacaoStatus::EM_COTACAO;
        }

        $fornecedor->save();

        return $fornecedor->fresh() ?? $fornecedor;
    }

    public function definirVencedor(Contratacao $contratacao, ContratacaoFornecedor $vencedor): ContratacaoFornecedor
    {
        ContratacaoFornecedor::query()
            ->where('contratacao_id', $contratacao->id)
            ->update(['vencedor' => false]);

        $vencedor->vencedor = true;
        $vencedor->save();

        return $vencedor->fresh() ?? $vencedor;
    }

    public function updateAberturaStatus(
        ContratacaoFornecedor $fornecedor,
        string $status,
        ?\DateTimeInterface $solicitadaEm = null,
        ?\DateTimeInterface $enviadaEm = null,
        ?\DateTimeInterface $confirmadaEm = null,
    ): ContratacaoFornecedor {
        $fornecedor->abertura_contrato_status = $status;

        if ($solicitadaEm !== null) {
            $fornecedor->abertura_solicitada_em = $solicitadaEm;
        }
        if ($enviadaEm !== null) {
            $fornecedor->abertura_enviada_em = $enviadaEm;
        }
        if ($confirmadaEm !== null) {
            $fornecedor->abertura_confirmada_em = $confirmadaEm;
        }

        $fornecedor->save();

        return $fornecedor->fresh() ?? $fornecedor;
    }

    public function updateVisitaTecnica(ContratacaoFornecedor $fornecedor, array $dados): ContratacaoFornecedor
    {
        foreach ([
            'visita_tecnica_status',
            'visita_tecnica_resolucao',
            'visita_tecnica_necessaria',
            'visita_agendada_data',
            'visita_agendada_hora',
            'visita_agendada_local',
            'visita_agendada_por_compras_em',
            'visita_tecnica_observacao',
            'visita_dispensa_justificativa',
            'visita_tecnica_concluida_em',
            'visita_tecnica_dispensada_em',
        ] as $campo) {
            if (array_key_exists($campo, $dados)) {
                $fornecedor->{$campo} = $dados[$campo];
            }
        }

        $fornecedor->save();

        return $fornecedor->fresh() ?? $fornecedor;
    }

    public function delete(ContratacaoFornecedor $fornecedor): void
    {
        $fornecedor->delete();
    }
}

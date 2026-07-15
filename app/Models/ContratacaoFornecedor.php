<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContratacaoFornecedor extends Model
{
    use HasUuids;

    protected $table = 'contratacao_fornecedores';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'contratacao_id',
        'cnpj',
        'razao_social',
        'telefone',
        'email',
        'vendedor',
        'aceite',
        'status_participacao',
        'proposta_inicial',
        'proposta_equalizada',
        'proposta_final',
        'condicao_pagamento_dias',
        'vencedor',
        'observacao_proposta',
        'abertura_contrato_status',
        'abertura_solicitada_em',
        'abertura_enviada_em',
        'abertura_confirmada_em',
        'optante_simples',
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
    ];

    protected $casts = [
        'aceite' => 'boolean',
        'vencedor' => 'boolean',
        'optante_simples' => 'boolean',
        'proposta_inicial' => 'decimal:2',
        'proposta_equalizada' => 'decimal:2',
        'proposta_final' => 'decimal:2',
        'condicao_pagamento_dias' => 'integer',
        'abertura_solicitada_em' => 'datetime',
        'abertura_enviada_em' => 'datetime',
        'abertura_confirmada_em' => 'datetime',
        'visita_tecnica_necessaria' => 'boolean',
        'visita_agendada_data' => 'date',
        'visita_agendada_por_compras_em' => 'datetime',
        'visita_tecnica_concluida_em' => 'datetime',
        'visita_tecnica_dispensada_em' => 'datetime',
    ];

    public function contratacao(): BelongsTo
    {
        return $this->belongsTo(Contratacao::class, 'contratacao_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(ContratacaoFornecedorUsuario::class, 'contratacao_fornecedor_id')->orderBy('created_at');
    }

    public function aberturaItens(): HasMany
    {
        return $this->hasMany(ContratacaoAberturaItem::class, 'contratacao_fornecedor_id')->orderBy('ordem');
    }

    public function propostaApontamentos(): HasMany
    {
        return $this->hasMany(ContratacaoPropostaApontamento::class, 'contratacao_fornecedor_id')->orderBy('created_at');
    }
}

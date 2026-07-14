<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContratacaoAberturaItem extends Model
{
    use HasUuids;

    protected $table = 'contratacao_abertura_itens';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'contratacao_fornecedor_id',
        'codigo',
        'label',
        'ordem',
        'obrigatorio',
        'condicional',
        'condicao',
        'controla_vencimento',
        'validade_dias',
        'parent_codigo',
        'padrao',
        'status_analise',
        'observacao_analise',
        'vencimento',
        'nome_arquivo',
        'storage_path',
    ];

    protected $casts = [
        'obrigatorio' => 'boolean',
        'condicional' => 'boolean',
        'controla_vencimento' => 'boolean',
        'padrao' => 'boolean',
        'ordem' => 'integer',
        'validade_dias' => 'integer',
        'vencimento' => 'date',
    ];

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(ContratacaoFornecedor::class, 'contratacao_fornecedor_id');
    }

    public function apontamentos(): HasMany
    {
        return $this->hasMany(ContratacaoAberturaApontamento::class, 'abertura_item_id')->orderBy('created_at');
    }
}

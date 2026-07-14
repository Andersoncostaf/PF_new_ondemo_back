<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContratacaoAvaliacaoTecnica extends Model
{
    use HasUuids;

    protected $table = 'contratacao_avaliacoes_tecnicas';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'contratacao_id',
        'status',
        'fornecedor_vencedor_id',
        'indice_percentual',
        'observacao',
        'delegado_para_usuario_id',
    ];

    protected $casts = [
        'indice_percentual' => 'decimal:2',
    ];

    public function contratacao(): BelongsTo
    {
        return $this->belongsTo(Contratacao::class, 'contratacao_id');
    }

    public function fornecedorVencedor(): BelongsTo
    {
        return $this->belongsTo(ContratacaoFornecedor::class, 'fornecedor_vencedor_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ContratacaoAvaliacaoTecnicaItem::class, 'avaliacao_id');
    }

    public function delegadoPara(): BelongsTo
    {
        return $this->belongsTo(UsuarioCliente::class, 'delegado_para_usuario_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratacaoPropostaApontamento extends Model
{
    use HasUuids;

    protected $table = 'contratacao_proposta_apontamentos';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'contratacao_fornecedor_id',
        'descricao',
        'status',
        'autor_origem',
        'resposta',
    ];

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(ContratacaoFornecedor::class, 'contratacao_fornecedor_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratacaoAberturaApontamento extends Model
{
    use HasUuids;

    protected $table = 'contratacao_abertura_apontamentos';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'abertura_item_id',
        'descricao',
        'status',
        'autor_origem',
        'resposta',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ContratacaoAberturaItem::class, 'abertura_item_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratacaoAvaliacaoTecnicaItem extends Model
{
    use HasUuids;

    protected $table = 'contratacao_avaliacao_tecnica_itens';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'avaliacao_id',
        'codigo',
        'label',
        'peso_percentual',
        'nota',
        'observacao',
    ];

    protected $casts = [
        'peso_percentual' => 'decimal:2',
        'nota' => 'decimal:2',
    ];

    public function avaliacao(): BelongsTo
    {
        return $this->belongsTo(ContratacaoAvaliacaoTecnica::class, 'avaliacao_id');
    }
}

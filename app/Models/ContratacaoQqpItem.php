<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratacaoQqpItem extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'contratacao_qqp_itens';

    protected $fillable = [
        'contratacao_id',
        'ordem',
        'descricao',
        'quantidade',
        'unidade',
    ];

    protected $casts = [
        'quantidade' => 'decimal:4',
        'ordem' => 'integer',
    ];

    public function contratacao(): BelongsTo
    {
        return $this->belongsTo(Contratacao::class, 'contratacao_id');
    }
}

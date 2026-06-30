<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratacaoAnexo extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'contratacao_anexos';

    protected $fillable = [
        'contratacao_id',
        'descricao',
        'nome_arquivo',
        'storage_path',
        'mime_type',
        'tamanho_bytes',
    ];

    protected $casts = [
        'tamanho_bytes' => 'integer',
    ];

    public function contratacao(): BelongsTo
    {
        return $this->belongsTo(Contratacao::class, 'contratacao_id');
    }
}

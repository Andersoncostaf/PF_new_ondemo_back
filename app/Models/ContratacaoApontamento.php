<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratacaoApontamento extends Model
{
    use HasUuids;

    protected $table = 'contratacao_apontamentos';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'contratacao_id',
        'etapa',
        'descricao',
        'status',
        'autor_usuario_id',
        'respondedor_usuario_id',
        'resposta',
        'nome_arquivo',
        'storage_path',
        'mime_type',
        'tamanho_bytes',
    ];

    public function contratacao(): BelongsTo
    {
        return $this->belongsTo(Contratacao::class, 'contratacao_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(UsuarioCliente::class, 'autor_usuario_id');
    }

    public function respondedor(): BelongsTo
    {
        return $this->belongsTo(UsuarioCliente::class, 'respondedor_usuario_id');
    }
}

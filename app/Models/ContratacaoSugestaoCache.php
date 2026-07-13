<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratacaoSugestaoCache extends Model
{
    use HasUuids;

    protected $table = 'contratacao_sugestoes_cache';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'contratacao_id',
        'payload_json',
        'contexto_hash',
        'expira_em',
        'created_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'expira_em' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function contratacao(): BelongsTo
    {
        return $this->belongsTo(Contratacao::class, 'contratacao_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}

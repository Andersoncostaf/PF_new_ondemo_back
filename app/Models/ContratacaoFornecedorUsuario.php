<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratacaoFornecedorUsuario extends Model
{
    use HasUuids;

    protected $table = 'contratacao_fornecedor_usuarios';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'contratacao_fornecedor_id',
        'nome',
        'email',
        'telefone',
        'perfil',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(ContratacaoFornecedor::class, 'contratacao_fornecedor_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}

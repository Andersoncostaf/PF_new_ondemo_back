<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FornecedorCatalogo extends Model
{
    use HasUuids;

    protected $table = 'fornecedores_catalogo';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id',
        'cnpj',
        'razao_social',
        'telefone',
        'email',
        'categoria_servico',
        'cidade',
        'uf',
        'tags',
        'ativo',
    ];

    protected $casts = [
        'tags' => 'array',
        'ativo' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}

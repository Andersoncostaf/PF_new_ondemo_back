<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratacaoFornecedor extends Model
{
    use HasUuids;

    protected $table = 'contratacao_fornecedores';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'contratacao_id',
        'cnpj',
        'razao_social',
        'telefone',
        'email',
        'vendedor',
        'aceite',
        'status_participacao',
    ];

    protected $casts = [
        'aceite' => 'boolean',
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantFilial extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'tenant_filiais';

    protected $fillable = [
        'tenant_id',
        'codigo',
        'razao_social',
        'cnpj',
        'endereco',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function contratacoes(): HasMany
    {
        return $this->hasMany(Contratacao::class, 'filial_id');
    }
}

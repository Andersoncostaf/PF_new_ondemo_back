<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contratacao extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'contratacoes';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'criado_por_usuario_id',
        'titulo',
        'categoria_servico',
        'local',
        'prazo_desejado',
        'termo_referencia',
        'termo_referencia_campos',
        'status',
    ];

    protected $casts = [
        'prazo_desejado' => 'date',
        'termo_referencia_campos' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(UsuarioCliente::class, 'criado_por_usuario_id');
    }

    public function qqpItens(): HasMany
    {
        return $this->hasMany(ContratacaoQqpItem::class, 'contratacao_id')->orderBy('ordem');
    }
}

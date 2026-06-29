<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantPaymentMethod extends Model
{
    use HasUuids;

    protected $table = 'tenant_payment_methods';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id',
        'gateway_payment_method_id',
        'tipo',
        'ultimos_quatro',
        'bandeira',
        'padrao',
        'ativo',
    ];

    protected $casts = [
        'padrao' => 'boolean',
        'ativo' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}

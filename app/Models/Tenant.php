<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'slug',
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'status',
        'trial_starts_at',
        'trial_ends_at',
        'subscription_status',
        'plan_code',
        'gateway_customer_id',
        'gateway_subscription_id',
    ];

    protected $casts = [
        'trial_starts_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    public function usuariosCliente(): HasMany
    {
        return $this->hasMany(UsuarioCliente::class, 'tenant_id');
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(TenantPaymentMethod::class, 'tenant_id');
    }

    public function subscriptionInvoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class, 'tenant_id');
    }
}

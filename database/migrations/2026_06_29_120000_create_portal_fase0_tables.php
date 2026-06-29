<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('slug', 64)->unique();
                $table->string('razao_social', 255);
                $table->string('nome_fantasia', 255)->nullable();
                $table->string('cnpj', 14)->unique();
                $table->string('status', 32)->default('pendente');
                $table->timestampTz('trial_starts_at');
                $table->timestampTz('trial_ends_at');
                $table->string('subscription_status', 32)->default('trial');
                $table->string('plan_code', 64)->nullable();
                $table->string('gateway_customer_id', 255)->nullable();
                $table->string('gateway_subscription_id', 255)->nullable();
                $table->timestampsTz();

                $table->index('status');
                $table->index('subscription_status');
            });
        }

        if (! Schema::hasTable('usuarios_cliente')) {
            Schema::create('usuarios_cliente', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('nome', 255);
                $table->string('email', 255);
                $table->string('password', 255);
                $table->string('telefone', 20)->nullable();
                $table->string('cargo', 128)->nullable();
                $table->string('perfil', 32);
                $table->string('status', 32)->default('pendente');
                $table->timestampTz('email_verified_at')->nullable();
                $table->timestampsTz();

                $table->unique(['tenant_id', 'email']);
                $table->index('email');
            });
        }

        if (! Schema::hasTable('tenant_payment_methods')) {
            Schema::create('tenant_payment_methods', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('gateway_payment_method_id', 255);
                $table->string('tipo', 32);
                $table->string('ultimos_quatro', 4)->nullable();
                $table->string('bandeira', 32)->nullable();
                $table->boolean('padrao')->default(false);
                $table->boolean('ativo')->default(true);
                $table->timestampsTz();

                $table->unique(['tenant_id', 'gateway_payment_method_id']);
            });
        }

        if (! Schema::hasTable('subscription_invoices')) {
            Schema::create('subscription_invoices', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('gateway_invoice_id', 255)->unique();
                $table->string('gateway_subscription_id', 255)->nullable();
                $table->unsignedBigInteger('amount_cents');
                $table->string('currency', 3)->default('BRL');
                $table->string('status', 32);
                $table->timestampTz('period_start')->nullable();
                $table->timestampTz('period_end')->nullable();
                $table->timestampTz('due_at')->nullable();
                $table->timestampTz('paid_at')->nullable();
                $table->timestampsTz();

                $table->index(['tenant_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_invoices');
        Schema::dropIfExists('tenant_payment_methods');
        Schema::dropIfExists('usuarios_cliente');
        Schema::dropIfExists('tenants');
    }
};

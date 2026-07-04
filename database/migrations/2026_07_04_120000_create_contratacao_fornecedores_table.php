<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contratacao_fornecedores')) {
            return;
        }

        Schema::create('contratacao_fornecedores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('uuid')->unique();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('contratacao_id')->constrained('contratacoes')->cascadeOnDelete();
            $table->string('cnpj', 20);
            $table->string('razao_social', 255);
            $table->string('telefone', 32)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('vendedor', 255)->nullable();
            $table->boolean('aceite')->default(false);
            $table->string('status_participacao', 32)->default('convidado');
            $table->timestampsTz();

            $table->unique(['tenant_id', 'contratacao_id', 'cnpj'], 'contratacao_fornecedores_tenant_contratacao_cnpj_unique');
            $table->index(['contratacao_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratacao_fornecedores');
    }
};

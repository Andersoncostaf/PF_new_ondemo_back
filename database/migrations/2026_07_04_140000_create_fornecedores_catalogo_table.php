<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fornecedores_catalogo')) {
            return;
        }

        Schema::create('fornecedores_catalogo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('cnpj', 20);
            $table->string('razao_social', 255);
            $table->string('telefone', 32)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('categoria_servico', 255)->nullable();
            $table->string('cidade', 120)->nullable();
            $table->char('uf', 2)->nullable();
            $table->jsonb('tags')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestampsTz();

            $table->unique(['tenant_id', 'cnpj'], 'fornecedores_catalogo_tenant_cnpj_unique');
            $table->index(['tenant_id', 'categoria_servico', 'ativo'], 'fornecedores_catalogo_tenant_cat_ativo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fornecedores_catalogo');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contratacoes')) {
            Schema::create('contratacoes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('uuid')->unique();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignUuid('criado_por_usuario_id')->constrained('usuarios_cliente')->restrictOnDelete();
                $table->string('titulo', 255)->nullable();
                $table->string('categoria_servico', 128)->nullable();
                $table->string('local', 255)->nullable();
                $table->date('prazo_desejado')->nullable();
                $table->text('termo_referencia')->nullable();
                $table->string('status', 32)->default('rascunho');
                $table->timestampsTz();

                $table->index(['tenant_id', 'status']);
                $table->index(['tenant_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('contratacao_qqp_itens')) {
            Schema::create('contratacao_qqp_itens', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('contratacao_id')->constrained('contratacoes')->cascadeOnDelete();
                $table->unsignedSmallInteger('ordem')->default(0);
                $table->string('descricao', 500);
                $table->decimal('quantidade', 12, 4)->default(1);
                $table->string('unidade', 32)->default('un');
                $table->timestampsTz();

                $table->index(['contratacao_id', 'ordem']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contratacao_qqp_itens');
        Schema::dropIfExists('contratacoes');
    }
};

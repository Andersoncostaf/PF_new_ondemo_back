<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratacoes', function (Blueprint $table) {
            if (! Schema::hasColumn('contratacoes', 'analista_usuario_id')) {
                $table->foreignUuid('analista_usuario_id')->nullable()->constrained('usuarios_cliente')->nullOnDelete();
            }
            if (! Schema::hasColumn('contratacoes', 'analise_iniciada_em')) {
                $table->timestampTz('analise_iniciada_em')->nullable();
            }
        });

        if (! Schema::hasTable('contratacao_apontamentos')) {
            Schema::create('contratacao_apontamentos', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('uuid')->unique();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignUuid('contratacao_id')->constrained('contratacoes')->cascadeOnDelete();
                $table->string('etapa', 64);
                $table->text('descricao')->nullable();
                $table->string('status', 32)->default('pendente');
                $table->foreignUuid('autor_usuario_id')->nullable()->constrained('usuarios_cliente')->nullOnDelete();
                $table->foreignUuid('respondedor_usuario_id')->nullable()->constrained('usuarios_cliente')->nullOnDelete();
                $table->text('resposta')->nullable();
                $table->string('nome_arquivo', 255)->nullable();
                $table->string('storage_path', 512)->nullable();
                $table->string('mime_type', 128)->nullable();
                $table->unsignedBigInteger('tamanho_bytes')->default(0);
                $table->timestampsTz();

                $table->index(['contratacao_id', 'etapa']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contratacao_apontamentos');

        Schema::table('contratacoes', function (Blueprint $table) {
            if (Schema::hasColumn('contratacoes', 'analista_usuario_id')) {
                $table->dropConstrainedForeignId('analista_usuario_id');
            }
            if (Schema::hasColumn('contratacoes', 'analise_iniciada_em')) {
                $table->dropColumn('analise_iniciada_em');
            }
        });
    }
};

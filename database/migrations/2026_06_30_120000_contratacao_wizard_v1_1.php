<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenant_filiais')) {
            Schema::create('tenant_filiais', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('codigo', 16);
                $table->string('razao_social', 255);
                $table->string('cnpj', 14);
                $table->text('endereco')->default('');
                $table->timestampsTz();

                $table->unique(['tenant_id', 'codigo']);
                $table->index('tenant_id');
            });
        }

        Schema::table('contratacoes', function (Blueprint $table) {
            if (! Schema::hasColumn('contratacoes', 'filial_id')) {
                $table->foreignUuid('filial_id')->nullable()->constrained('tenant_filiais')->nullOnDelete();
            }
            if (! Schema::hasColumn('contratacoes', 'departamento')) {
                $table->string('departamento', 128)->nullable();
            }
            if (! Schema::hasColumn('contratacoes', 'solicitacao_servico')) {
                $table->json('solicitacao_servico')->nullable();
            }
        });

        Schema::table('contratacao_qqp_itens', function (Blueprint $table) {
            if (! Schema::hasColumn('contratacao_qqp_itens', 'valor_unitario')) {
                $table->decimal('valor_unitario', 14, 4)->default(0);
            }
        });

        if (! Schema::hasTable('contratacao_anexos')) {
            Schema::create('contratacao_anexos', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('contratacao_id')->constrained('contratacoes')->cascadeOnDelete();
                $table->string('descricao', 255)->default('');
                $table->string('nome_arquivo', 255);
                $table->string('storage_path', 512);
                $table->string('mime_type', 128)->nullable();
                $table->unsignedBigInteger('tamanho_bytes')->default(0);
                $table->timestampsTz();

                $table->index('contratacao_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contratacao_anexos');

        Schema::table('contratacao_qqp_itens', function (Blueprint $table) {
            if (Schema::hasColumn('contratacao_qqp_itens', 'valor_unitario')) {
                $table->dropColumn('valor_unitario');
            }
        });

        Schema::table('contratacoes', function (Blueprint $table) {
            if (Schema::hasColumn('contratacoes', 'filial_id')) {
                $table->dropConstrainedForeignId('filial_id');
            }
            if (Schema::hasColumn('contratacoes', 'departamento')) {
                $table->dropColumn('departamento');
            }
            if (Schema::hasColumn('contratacoes', 'solicitacao_servico')) {
                $table->dropColumn('solicitacao_servico');
            }
        });

        Schema::dropIfExists('tenant_filiais');
    }
};

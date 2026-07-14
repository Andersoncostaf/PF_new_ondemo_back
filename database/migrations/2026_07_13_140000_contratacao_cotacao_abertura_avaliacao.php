<?php

use App\Modules\Contratacao\Domain\ContratacaoStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONSTRAINT = 'contratacoes_status_check';

    public function up(): void
    {
        Schema::table('contratacao_fornecedores', function (Blueprint $table) {
            if (! Schema::hasColumn('contratacao_fornecedores', 'proposta_inicial')) {
                $table->decimal('proposta_inicial', 15, 2)->nullable();
            }
            if (! Schema::hasColumn('contratacao_fornecedores', 'proposta_equalizada')) {
                $table->decimal('proposta_equalizada', 15, 2)->nullable();
            }
            if (! Schema::hasColumn('contratacao_fornecedores', 'proposta_final')) {
                $table->decimal('proposta_final', 15, 2)->nullable();
            }
            if (! Schema::hasColumn('contratacao_fornecedores', 'condicao_pagamento_dias')) {
                $table->integer('condicao_pagamento_dias')->nullable();
            }
            if (! Schema::hasColumn('contratacao_fornecedores', 'vencedor')) {
                $table->boolean('vencedor')->default(false);
            }
            if (! Schema::hasColumn('contratacao_fornecedores', 'observacao_proposta')) {
                $table->text('observacao_proposta')->nullable();
            }
            if (! Schema::hasColumn('contratacao_fornecedores', 'abertura_contrato_status')) {
                $table->string('abertura_contrato_status', 64)->default('nao_iniciada');
            }
            if (! Schema::hasColumn('contratacao_fornecedores', 'abertura_solicitada_em')) {
                $table->timestampTz('abertura_solicitada_em')->nullable();
            }
            if (! Schema::hasColumn('contratacao_fornecedores', 'abertura_enviada_em')) {
                $table->timestampTz('abertura_enviada_em')->nullable();
            }
            if (! Schema::hasColumn('contratacao_fornecedores', 'abertura_confirmada_em')) {
                $table->timestampTz('abertura_confirmada_em')->nullable();
            }
            if (! Schema::hasColumn('contratacao_fornecedores', 'optante_simples')) {
                $table->boolean('optante_simples')->default(false);
            }
        });

        if (! Schema::hasTable('contratacao_fornecedor_usuarios')) {
            Schema::create('contratacao_fornecedor_usuarios', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('uuid')->unique();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignUuid('contratacao_fornecedor_id')->constrained('contratacao_fornecedores')->cascadeOnDelete();
                $table->string('nome', 255);
                $table->string('email', 255);
                $table->string('telefone', 32)->nullable();
                $table->string('perfil', 16)->default('PADRAO');
                $table->boolean('ativo')->default(true);
                $table->timestampsTz();

                $table->index(['contratacao_fornecedor_id', 'ativo']);
            });
        }

        if (! Schema::hasTable('contratacao_avaliacoes_tecnicas')) {
            Schema::create('contratacao_avaliacoes_tecnicas', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('uuid')->unique();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignUuid('contratacao_id')->constrained('contratacoes')->cascadeOnDelete();
                $table->string('status', 32)->default('rascunho');
                $table->foreignUuid('fornecedor_vencedor_id')->nullable()->constrained('contratacao_fornecedores')->nullOnDelete();
                $table->decimal('indice_percentual', 8, 2)->nullable();
                $table->text('observacao')->nullable();
                $table->foreignUuid('delegado_para_usuario_id')->nullable()->constrained('usuarios_cliente')->nullOnDelete();
                $table->timestampsTz();

                $table->unique('contratacao_id');
            });
        }

        if (! Schema::hasTable('contratacao_avaliacao_tecnica_itens')) {
            Schema::create('contratacao_avaliacao_tecnica_itens', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('uuid')->unique();
                $table->foreignUuid('avaliacao_id')->constrained('contratacao_avaliacoes_tecnicas')->cascadeOnDelete();
                $table->string('codigo', 64);
                $table->string('label', 255);
                $table->decimal('peso_percentual', 8, 2);
                $table->decimal('nota', 4, 2)->nullable();
                $table->text('observacao')->nullable();
                $table->timestampsTz();

                $table->unique(['avaliacao_id', 'codigo']);
            });
        }

        if (! Schema::hasTable('contratacao_abertura_itens')) {
            Schema::create('contratacao_abertura_itens', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('uuid')->unique();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignUuid('contratacao_fornecedor_id')->constrained('contratacao_fornecedores')->cascadeOnDelete();
                $table->string('codigo', 32);
                $table->string('label', 255);
                $table->unsignedInteger('ordem')->default(0);
                $table->boolean('obrigatorio')->default(true);
                $table->boolean('condicional')->default(false);
                $table->string('condicao', 64)->nullable();
                $table->boolean('controla_vencimento')->default(false);
                $table->unsignedInteger('validade_dias')->nullable();
                $table->string('parent_codigo', 32)->nullable();
                $table->boolean('padrao')->default(true);
                $table->string('status_analise', 16)->default('pendente');
                $table->text('observacao_analise')->nullable();
                $table->date('vencimento')->nullable();
                $table->string('nome_arquivo', 255)->nullable();
                $table->string('storage_path', 512)->nullable();
                $table->timestampsTz();

                $table->index(['contratacao_fornecedor_id', 'ordem']);
                $table->unique(['contratacao_fornecedor_id', 'codigo'], 'contratacao_abertura_itens_fornecedor_codigo_unique');
            });
        }

        if (! Schema::hasTable('contratacao_proposta_apontamentos')) {
            Schema::create('contratacao_proposta_apontamentos', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('uuid')->unique();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignUuid('contratacao_fornecedor_id')->constrained('contratacao_fornecedores')->cascadeOnDelete();
                $table->text('descricao');
                $table->string('status', 32)->default('aberto');
                $table->string('autor_origem', 32)->default('COMPRAS');
                $table->text('resposta')->nullable();
                $table->timestampsTz();

                $table->index(['contratacao_fornecedor_id', 'status']);
            });
        }

        if (! Schema::hasTable('contratacao_abertura_apontamentos')) {
            Schema::create('contratacao_abertura_apontamentos', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('uuid')->unique();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignUuid('abertura_item_id')->constrained('contratacao_abertura_itens')->cascadeOnDelete();
                $table->text('descricao');
                $table->string('status', 32)->default('aberto');
                $table->string('autor_origem', 32)->default('COMPRAS');
                $table->text('resposta')->nullable();
                $table->timestampsTz();

                $table->index(['abertura_item_id', 'status']);
            });
        }

        $this->replaceStatusCheck([
            ContratacaoStatus::RASCUNHO,
            ContratacaoStatus::AGUARDANDO_ANALISE_COMPRAS,
            ContratacaoStatus::EM_ANALISE,
            ContratacaoStatus::AGUARDANDO_AJUSTE_AREA,
            ContratacaoStatus::APROVADO_COMPRAS,
            ContratacaoStatus::EM_VENDOR_LIST,
            ContratacaoStatus::VENCEDOR_DEFINIDO,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('contratacao_abertura_apontamentos');
        Schema::dropIfExists('contratacao_proposta_apontamentos');
        Schema::dropIfExists('contratacao_abertura_itens');
        Schema::dropIfExists('contratacao_avaliacao_tecnica_itens');
        Schema::dropIfExists('contratacao_avaliacoes_tecnicas');
        Schema::dropIfExists('contratacao_fornecedor_usuarios');

        Schema::table('contratacao_fornecedores', function (Blueprint $table) {
            foreach ([
                'proposta_inicial',
                'proposta_equalizada',
                'proposta_final',
                'condicao_pagamento_dias',
                'vencedor',
                'observacao_proposta',
                'abertura_contrato_status',
                'abertura_solicitada_em',
                'abertura_enviada_em',
                'abertura_confirmada_em',
                'optante_simples',
            ] as $column) {
                if (Schema::hasColumn('contratacao_fornecedores', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        $this->replaceStatusCheck([
            ContratacaoStatus::RASCUNHO,
            ContratacaoStatus::AGUARDANDO_ANALISE_COMPRAS,
            ContratacaoStatus::EM_ANALISE,
            ContratacaoStatus::AGUARDANDO_AJUSTE_AREA,
            ContratacaoStatus::APROVADO_COMPRAS,
            ContratacaoStatus::EM_VENDOR_LIST,
        ]);
    }

    /**
     * @param list<string> $statuses
     */
    private function replaceStatusCheck(array $statuses): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        $quoted = implode(', ', array_map(
            fn (string $status) => "'".str_replace("'", "''", $status)."'",
            $statuses,
        ));

        DB::statement('ALTER TABLE contratacoes DROP CONSTRAINT IF EXISTS '.self::CONSTRAINT);
        DB::statement(
            'ALTER TABLE contratacoes ADD CONSTRAINT '.self::CONSTRAINT.' CHECK (status IN ('.$quoted.'))',
        );
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratacao_fornecedores', function (Blueprint $table) {
            if (! Schema::hasColumn('contratacao_fornecedores', 'visita_tecnica_status')) {
                $table->string('visita_tecnica_status', 40)->default('nao_iniciada');
            }
            if (! Schema::hasColumn('contratacao_fornecedores', 'visita_tecnica_resolucao')) {
                $table->string('visita_tecnica_resolucao', 40)->default('pendente');
            }
            if (! Schema::hasColumn('contratacao_fornecedores', 'visita_tecnica_necessaria')) {
                $table->boolean('visita_tecnica_necessaria')->nullable();
            }
            if (! Schema::hasColumn('contratacao_fornecedores', 'visita_agendada_data')) {
                $table->date('visita_agendada_data')->nullable();
            }
            if (! Schema::hasColumn('contratacao_fornecedores', 'visita_agendada_hora')) {
                $table->string('visita_agendada_hora', 10)->nullable();
            }
            if (! Schema::hasColumn('contratacao_fornecedores', 'visita_agendada_local')) {
                $table->string('visita_agendada_local', 500)->nullable();
            }
            if (! Schema::hasColumn('contratacao_fornecedores', 'visita_agendada_por_compras_em')) {
                $table->timestampTz('visita_agendada_por_compras_em')->nullable();
            }
            if (! Schema::hasColumn('contratacao_fornecedores', 'visita_tecnica_observacao')) {
                $table->string('visita_tecnica_observacao', 600)->nullable();
            }
            if (! Schema::hasColumn('contratacao_fornecedores', 'visita_dispensa_justificativa')) {
                $table->string('visita_dispensa_justificativa', 600)->nullable();
            }
            if (! Schema::hasColumn('contratacao_fornecedores', 'visita_tecnica_concluida_em')) {
                $table->timestampTz('visita_tecnica_concluida_em')->nullable();
            }
            if (! Schema::hasColumn('contratacao_fornecedores', 'visita_tecnica_dispensada_em')) {
                $table->timestampTz('visita_tecnica_dispensada_em')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contratacao_fornecedores', function (Blueprint $table) {
            foreach ([
                'visita_tecnica_status',
                'visita_tecnica_resolucao',
                'visita_tecnica_necessaria',
                'visita_agendada_data',
                'visita_agendada_hora',
                'visita_agendada_local',
                'visita_agendada_por_compras_em',
                'visita_tecnica_observacao',
                'visita_dispensa_justificativa',
                'visita_tecnica_concluida_em',
                'visita_tecnica_dispensada_em',
            ] as $column) {
                if (Schema::hasColumn('contratacao_fornecedores', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

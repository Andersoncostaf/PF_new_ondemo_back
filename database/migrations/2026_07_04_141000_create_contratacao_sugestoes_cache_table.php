<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contratacao_sugestoes_cache')) {
            return;
        }

        Schema::create('contratacao_sugestoes_cache', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('contratacao_id')->constrained('contratacoes')->cascadeOnDelete();
            $table->jsonb('payload_json');
            $table->string('contexto_hash', 64);
            $table->timestampTz('expira_em');
            $table->timestampTz('created_at')->useCurrent();

            $table->unique('contratacao_id', 'contratacao_sugestoes_cache_contratacao_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratacao_sugestoes_cache');
    }
};

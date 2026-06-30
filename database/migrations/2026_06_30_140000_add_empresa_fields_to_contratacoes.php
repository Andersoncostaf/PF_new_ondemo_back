<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratacoes', function (Blueprint $table) {
            if (! Schema::hasColumn('contratacoes', 'empresa')) {
                $table->string('empresa', 255)->nullable();
            }
            if (! Schema::hasColumn('contratacoes', 'empresa_cnpj')) {
                $table->string('empresa_cnpj', 18)->nullable();
            }
            if (! Schema::hasColumn('contratacoes', 'empresa_endereco')) {
                $table->string('empresa_endereco', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contratacoes', function (Blueprint $table) {
            if (Schema::hasColumn('contratacoes', 'empresa_endereco')) {
                $table->dropColumn('empresa_endereco');
            }
            if (Schema::hasColumn('contratacoes', 'empresa_cnpj')) {
                $table->dropColumn('empresa_cnpj');
            }
            if (Schema::hasColumn('contratacoes', 'empresa')) {
                $table->dropColumn('empresa');
            }
        });
    }
};

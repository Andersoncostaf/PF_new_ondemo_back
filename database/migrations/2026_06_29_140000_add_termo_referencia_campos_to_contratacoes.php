<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contratacoes') && ! Schema::hasColumn('contratacoes', 'termo_referencia_campos')) {
            Schema::table('contratacoes', function (Blueprint $table) {
                $table->jsonb('termo_referencia_campos')->nullable()->after('termo_referencia');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('contratacoes', 'termo_referencia_campos')) {
            Schema::table('contratacoes', function (Blueprint $table) {
                $table->dropColumn('termo_referencia_campos');
            });
        }
    }
};

<?php

use App\Modules\Contratacao\Domain\ContratacaoStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONSTRAINT = 'contratacoes_status_check';

    /** @var list<string> */
    private const ALLOWED_STATUSES = [
        ContratacaoStatus::RASCUNHO,
        ContratacaoStatus::AGUARDANDO_ANALISE_COMPRAS,
        ContratacaoStatus::EM_ANALISE,
        ContratacaoStatus::AGUARDANDO_AJUSTE_AREA,
        ContratacaoStatus::APROVADO_COMPRAS,
        ContratacaoStatus::EM_VENDOR_LIST,
    ];

    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        $this->replaceStatusCheck(self::ALLOWED_STATUSES);
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        $withoutVendorList = array_values(array_filter(
            self::ALLOWED_STATUSES,
            fn (string $status) => $status !== ContratacaoStatus::EM_VENDOR_LIST,
        ));

        $this->replaceStatusCheck($withoutVendorList);
    }

    /**
     * @param list<string> $statuses
     */
    private function replaceStatusCheck(array $statuses): void
    {
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

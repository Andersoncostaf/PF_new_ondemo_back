<?php

namespace App\Modules\Contratacao\Application\Port\Out;

use App\Models\Contratacao;
use App\Modules\Contratacao\Application\DTO\ContratacaoInput;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ContratacaoRepositoryPort
{
    public function createRascunho(string $tenantId, string $usuarioId, ContratacaoInput $input): Contratacao;

    public function findByUuidForTenant(string $uuid, string $tenantId): ?Contratacao;

    public function updateRascunho(Contratacao $contratacao, ContratacaoInput $input): Contratacao;

    public function submeter(Contratacao $contratacao): Contratacao;

    public function listByTenant(string $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
}

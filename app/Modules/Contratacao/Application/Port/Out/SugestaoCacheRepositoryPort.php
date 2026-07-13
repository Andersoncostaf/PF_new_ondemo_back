<?php

namespace App\Modules\Contratacao\Application\Port\Out;

use App\Models\Contratacao;

interface SugestaoCacheRepositoryPort
{
    /**
     * @return array<string, mixed>|null
     */
    public function buscarValido(Contratacao $contratacao, string $contextoHash): ?array;

    /**
     * @param array<string, mixed> $payload
     */
    public function salvar(Contratacao $contratacao, string $tenantId, string $contextoHash, array $payload): void;

    public function invalidar(Contratacao $contratacao): void;
}

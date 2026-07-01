<?php

namespace App\Modules\Contratacao\Application\DTO;

use Illuminate\Http\Request;

final class ContratacaoListFilter
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly ?string $dataInicio = null,
        public readonly ?string $dataFim = null,
        public readonly ?string $numero = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $dataInicio = $request->query('data_inicio');
        $dataFim = $request->query('data_fim');
        $numero = $request->query('numero');

        return new self(
            page: max(1, (int) $request->query('page', 1)),
            perPage: min(100, max(1, (int) $request->query('per_page', 20))),
            dataInicio: is_string($dataInicio) && $dataInicio !== '' ? $dataInicio : null,
            dataFim: is_string($dataFim) && $dataFim !== '' ? $dataFim : null,
            numero: is_string($numero) && trim($numero) !== '' ? trim($numero) : null,
        );
    }
}

<?php

namespace App\Modules\Identidade\Application\DTO;

final class ModuloItem
{
    public function __construct(
        public readonly string $codigo,
        public readonly string $label,
        public readonly string $rota,
    ) {}

    /**
     * @return array{codigo: string, label: string, rota: string}
     */
    public function toArray(): array
    {
        return [
            'codigo' => $this->codigo,
            'label' => $this->label,
            'rota' => $this->rota,
        ];
    }
}

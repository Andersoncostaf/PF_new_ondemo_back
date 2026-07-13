<?php

namespace App\Modules\Identidade\Application\DTO;

final class ModuloItem
{
    public function __construct(
        public readonly string $codigo,
        public readonly string $label,
        public readonly string $rota,
        public readonly ?string $grupo = null,
    ) {}

    /**
     * @return array{codigo: string, label: string, rota: string, grupo?: string}
     */
    public function toArray(): array
    {
        $data = [
            'codigo' => $this->codigo,
            'label' => $this->label,
            'rota' => $this->rota,
        ];

        if ($this->grupo !== null) {
            $data['grupo'] = $this->grupo;
        }

        return $data;
    }
}

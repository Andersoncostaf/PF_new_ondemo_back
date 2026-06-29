<?php

namespace App\Modules\Contratacao\Application\DTO;

final class QqpItemInput
{
    public function __construct(
        public readonly string $descricao,
        public readonly float $quantidade,
        public readonly string $unidade,
        public readonly int $ordem = 0,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, int $index = 0): self
    {
        return new self(
            descricao: (string) ($data['descricao'] ?? ''),
            quantidade: (float) ($data['quantidade'] ?? 1),
            unidade: (string) ($data['unidade'] ?? 'un'),
            ordem: (int) ($data['ordem'] ?? $index),
        );
    }
}

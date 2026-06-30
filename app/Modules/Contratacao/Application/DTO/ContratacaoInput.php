<?php

namespace App\Modules\Contratacao\Application\DTO;

use App\Modules\Contratacao\Domain\TermoReferenciaCampos;

final class ContratacaoInput
{
    /**
     * @param list<QqpItemInput>|null $qqpItens
     * @param array<string, string|null>|null $termoReferenciaCampos
     */
    public function __construct(
        public readonly ?string $titulo = null,
        public readonly ?string $categoriaServico = null,
        public readonly ?string $local = null,
        public readonly ?string $prazoDesejado = null,
        public readonly ?string $termoReferencia = null,
        public readonly ?array $termoReferenciaCampos = null,
        public readonly ?array $qqpItens = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $qqpItens = null;

        if (array_key_exists('qqp_itens', $data) && is_array($data['qqp_itens'])) {
            $qqpItens = [];
            foreach ($data['qqp_itens'] as $index => $item) {
                if (is_array($item)) {
                    $qqpItens[] = QqpItemInput::fromArray($item, $index);
                }
            }
        }

        $termoReferenciaCampos = null;

        if (array_key_exists('termo_referencia_campos', $data) && is_array($data['termo_referencia_campos'])) {
            $termoReferenciaCampos = TermoReferenciaCampos::normalize($data['termo_referencia_campos']);
        }

        return new self(
            titulo: array_key_exists('titulo', $data) ? ($data['titulo'] !== null ? (string) $data['titulo'] : null) : null,
            categoriaServico: array_key_exists('categoria_servico', $data) ? ($data['categoria_servico'] !== null ? (string) $data['categoria_servico'] : null) : null,
            local: array_key_exists('local', $data) ? ($data['local'] !== null ? (string) $data['local'] : null) : null,
            prazoDesejado: array_key_exists('prazo_desejado', $data) ? ($data['prazo_desejado'] !== null ? (string) $data['prazo_desejado'] : null) : null,
            termoReferencia: array_key_exists('termo_referencia', $data) ? ($data['termo_referencia'] !== null ? (string) $data['termo_referencia'] : null) : null,
            termoReferenciaCampos: $termoReferenciaCampos,
            qqpItens: $qqpItens,
        );
    }
}

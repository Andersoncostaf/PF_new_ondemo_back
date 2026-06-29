<?php

namespace App\Modules\Contratacao\Application\DTO;

use App\Models\Contratacao;

final class ContratacaoOutput
{
    /**
     * @return array<string, mixed>
     */
    public static function fromModel(Contratacao $contratacao): array
    {
        $contratacao->loadMissing('qqpItens');

        return [
            'uuid' => $contratacao->uuid,
            'titulo' => $contratacao->titulo,
            'categoria_servico' => $contratacao->categoria_servico,
            'local' => $contratacao->local,
            'prazo_desejado' => $contratacao->prazo_desejado?->format('Y-m-d'),
            'termo_referencia' => $contratacao->termo_referencia,
            'status' => $contratacao->status,
            'criado_por_usuario_id' => $contratacao->criado_por_usuario_id,
            'created_at' => $contratacao->created_at?->toIso8601String(),
            'updated_at' => $contratacao->updated_at?->toIso8601String(),
            'qqp_itens' => $contratacao->qqpItens->map(fn ($item) => [
                'id' => $item->id,
                'ordem' => $item->ordem,
                'descricao' => $item->descricao,
                'quantidade' => (float) $item->quantidade,
                'unidade' => $item->unidade,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function listItem(Contratacao $contratacao): array
    {
        return [
            'uuid' => $contratacao->uuid,
            'titulo' => $contratacao->titulo,
            'categoria_servico' => $contratacao->categoria_servico,
            'status' => $contratacao->status,
            'created_at' => $contratacao->created_at?->toIso8601String(),
            'updated_at' => $contratacao->updated_at?->toIso8601String(),
        ];
    }
}

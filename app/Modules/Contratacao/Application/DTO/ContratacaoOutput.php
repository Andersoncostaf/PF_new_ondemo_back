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
        $contratacao->loadMissing(['qqpItens', 'anexos']);

        return [
            'uuid' => $contratacao->uuid,
            'titulo' => $contratacao->titulo,
            'categoria_servico' => $contratacao->categoria_servico,
            'local' => $contratacao->local,
            'prazo_desejado' => $contratacao->prazo_desejado?->format('Y-m-d'),
            'empresa' => $contratacao->empresa,
            'empresa_cnpj' => $contratacao->empresa_cnpj,
            'empresa_endereco' => $contratacao->empresa_endereco,
            'departamento' => $contratacao->departamento,
            'termo_referencia' => $contratacao->termo_referencia,
            'termo_referencia_campos' => $contratacao->termo_referencia_campos ?? [],
            'solicitacao_servico' => $contratacao->solicitacao_servico ?? [],
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
                'valor_unitario' => (float) $item->valor_unitario,
            ])->values()->all(),
            'anexos' => $contratacao->anexos->map(fn ($anexo) => [
                'id' => $anexo->id,
                'descricao' => $anexo->descricao,
                'nome_arquivo' => $anexo->nome_arquivo,
                'mime_type' => $anexo->mime_type,
                'tamanho_bytes' => $anexo->tamanho_bytes,
                'created_at' => $anexo->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function listItem(Contratacao $contratacao): array
    {
        $contratacao->loadMissing(['criadoPor']);

        return [
            'uuid' => $contratacao->uuid,
            'numero_exibicao' => self::numeroExibicao($contratacao->uuid),
            'titulo' => $contratacao->titulo,
            'empresa' => $contratacao->empresa,
            'empresa_cnpj' => $contratacao->empresa_cnpj,
            'departamento' => $contratacao->departamento,
            'criado_por_nome' => $contratacao->criadoPor?->nome,
            'categoria_servico' => $contratacao->categoria_servico,
            'status' => $contratacao->status,
            'created_at' => $contratacao->created_at?->toIso8601String(),
            'updated_at' => $contratacao->updated_at?->toIso8601String(),
            'fornecedor_vencedor' => null,
            'data_inicio_analise' => null,
            'responsavel_analise' => null,
            'apontamentos_pendentes' => null,
        ];
    }

    private static function numeroExibicao(string $uuid): string
    {
        return strtoupper(substr(str_replace('-', '', $uuid), 0, 8));
    }
}

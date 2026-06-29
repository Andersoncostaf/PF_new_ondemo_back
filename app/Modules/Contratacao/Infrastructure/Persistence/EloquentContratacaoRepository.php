<?php

namespace App\Modules\Contratacao\Infrastructure\Persistence;

use App\Models\Contratacao;
use App\Models\ContratacaoQqpItem;
use App\Modules\Contratacao\Application\DTO\ContratacaoInput;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoRepositoryPort;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

final class EloquentContratacaoRepository implements ContratacaoRepositoryPort
{
    public function createRascunho(string $tenantId, string $usuarioId, ContratacaoInput $input): Contratacao
    {
        $contratacao = Contratacao::query()->create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'criado_por_usuario_id' => $usuarioId,
            'titulo' => $input->titulo,
            'categoria_servico' => $input->categoriaServico,
            'local' => $input->local,
            'prazo_desejado' => $input->prazoDesejado,
            'termo_referencia' => $input->termoReferencia,
            'status' => 'rascunho',
        ]);

        if ($input->qqpItens !== null) {
            $this->syncQqpItens($contratacao, $input->qqpItens);
        }

        return $contratacao->fresh(['qqpItens']);
    }

    public function findByUuidForTenant(string $uuid, string $tenantId): ?Contratacao
    {
        return Contratacao::query()
            ->with('qqpItens')
            ->where('uuid', $uuid)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function updateRascunho(Contratacao $contratacao, ContratacaoInput $input): Contratacao
    {
        $attributes = [];

        if ($input->titulo !== null) {
            $attributes['titulo'] = $input->titulo;
        }

        if ($input->categoriaServico !== null) {
            $attributes['categoria_servico'] = $input->categoriaServico;
        }

        if ($input->local !== null) {
            $attributes['local'] = $input->local;
        }

        if ($input->prazoDesejado !== null) {
            $attributes['prazo_desejado'] = $input->prazoDesejado !== '' ? $input->prazoDesejado : null;
        }

        if ($input->termoReferencia !== null) {
            $attributes['termo_referencia'] = $input->termoReferencia;
        }

        if ($attributes !== []) {
            $contratacao->fill($attributes);
            $contratacao->save();
        }

        if ($input->qqpItens !== null) {
            $this->syncQqpItens($contratacao, $input->qqpItens);
        }

        return $contratacao->fresh(['qqpItens']);
    }

    public function submeter(Contratacao $contratacao): Contratacao
    {
        $contratacao->status = 'submetido';
        $contratacao->save();

        return $contratacao->fresh(['qqpItens']);
    }

    public function listByTenant(string $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return Contratacao::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate(perPage: $perPage, page: $page);
    }

    /**
     * @param list<\App\Modules\Contratacao\Application\DTO\QqpItemInput> $itens
     */
    private function syncQqpItens(Contratacao $contratacao, array $itens): void
    {
        $contratacao->qqpItens()->delete();

        foreach ($itens as $index => $item) {
            if (trim($item->descricao) === '') {
                continue;
            }

            ContratacaoQqpItem::query()->create([
                'contratacao_id' => $contratacao->id,
                'ordem' => $item->ordem > 0 ? $item->ordem : $index,
                'descricao' => $item->descricao,
                'quantidade' => $item->quantidade > 0 ? $item->quantidade : 1,
                'unidade' => $item->unidade !== '' ? $item->unidade : 'un',
            ]);
        }
    }
}

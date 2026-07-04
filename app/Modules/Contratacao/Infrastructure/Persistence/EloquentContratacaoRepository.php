<?php

namespace App\Modules\Contratacao\Infrastructure\Persistence;

use App\Models\Contratacao;
use App\Models\ContratacaoApontamento;
use App\Models\ContratacaoQqpItem;
use App\Modules\Contratacao\Application\DTO\ContratacaoInput;
use App\Modules\Contratacao\Application\DTO\ContratacaoListFilter;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoRepositoryPort;
use App\Modules\Contratacao\Domain\ContratacaoStatus;
use App\Modules\Contratacao\Domain\TermoReferenciaCampos;
use App\Modules\Contratacao\Infrastructure\Storage\ContratacaoAnexoStorage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class EloquentContratacaoRepository implements ContratacaoRepositoryPort
{
    public function __construct(
        private ContratacaoAnexoStorage $storage,
    ) {}
    public function createRascunho(string $tenantId, string $usuarioId, ContratacaoInput $input): Contratacao
    {
        $contratacao = Contratacao::query()->create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'criado_por_usuario_id' => $usuarioId,
            'empresa' => $input->empresa,
            'empresa_cnpj' => $input->empresaCnpj,
            'empresa_endereco' => $input->empresaEndereco,
            'departamento' => $input->departamento,
            'titulo' => $input->titulo,
            'categoria_servico' => $input->categoriaServico,
            'local' => $input->local,
            'prazo_desejado' => $input->prazoDesejado,
            'termo_referencia' => $this->resolveTermoReferenciaText($input),
            'termo_referencia_campos' => $input->termoReferenciaCampos,
            'solicitacao_servico' => $input->solicitacaoServico,
            'status' => 'rascunho',
        ]);

        if ($input->qqpItens !== null) {
            $this->syncQqpItens($contratacao, $input->qqpItens);
        }

        return $contratacao->fresh(['qqpItens', 'anexos']);
    }

    public function findByUuidForTenant(string $uuid, string $tenantId): ?Contratacao
    {
        return Contratacao::query()
            ->with(['qqpItens', 'anexos', 'analista', 'criadoPor'])
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

        if ($input->empresa !== null) {
            $attributes['empresa'] = $input->empresa !== '' ? $input->empresa : null;
        }

        if ($input->empresaCnpj !== null) {
            $attributes['empresa_cnpj'] = $input->empresaCnpj !== '' ? $input->empresaCnpj : null;
        }

        if ($input->empresaEndereco !== null) {
            $attributes['empresa_endereco'] = $input->empresaEndereco !== '' ? $input->empresaEndereco : null;
        }

        if ($input->departamento !== null) {
            $attributes['departamento'] = $input->departamento !== '' ? $input->departamento : null;
        }

        if ($input->solicitacaoServicoTouched && $input->solicitacaoServico !== null) {
            $attributes['solicitacao_servico'] = $input->solicitacaoServico;
        }

        if ($input->termoReferenciaCampos !== null) {
            $mergedCampos = array_merge(
                is_array($contratacao->termo_referencia_campos) ? $contratacao->termo_referencia_campos : [],
                $input->termoReferenciaCampos,
            );
            $attributes['termo_referencia_campos'] = $mergedCampos;
            $attributes['termo_referencia'] = TermoReferenciaCampos::toText($mergedCampos);
        } elseif ($input->termoReferencia !== null) {
            $attributes['termo_referencia'] = $input->termoReferencia;
        }

        if ($attributes !== []) {
            $contratacao->fill($attributes);
            $contratacao->save();
        }

        if ($input->qqpItens !== null) {
            $this->syncQqpItens($contratacao, $input->qqpItens);
        }

        return $contratacao->fresh(['qqpItens', 'anexos']);
    }

    public function deleteRascunho(Contratacao $contratacao): void
    {
        $contratacao->load(['anexos', 'apontamentos']);

        foreach ($contratacao->anexos as $anexo) {
            if ($anexo->storage_path) {
                $this->storage->delete($anexo->storage_path);
            }
        }

        foreach ($contratacao->apontamentos as $apontamento) {
            if ($apontamento->storage_path) {
                $this->storage->delete($apontamento->storage_path);
            }
        }

        $contratacao->delete();
    }

    public function submeter(Contratacao $contratacao): Contratacao
    {
        $contratacao->status = ContratacaoStatus::AGUARDANDO_ANALISE_COMPRAS;
        $contratacao->save();

        return $contratacao->fresh(['qqpItens', 'anexos', 'analista', 'criadoPor']);
    }

    public function listPendentesAprovacao(string $tenantId, ContratacaoListFilter $filter): LengthAwarePaginator
    {
        return $this->listFilaComprasQuery($tenantId, $filter, ContratacaoStatus::FILA_APROVACAO);
    }

    public function listFilaCompras(string $tenantId, ContratacaoListFilter $filter): LengthAwarePaginator
    {
        return $this->listFilaComprasQuery($tenantId, $filter, ContratacaoStatus::FILA_COMPRAS);
    }

    /**
     * @param list<string> $statuses
     */
    private function listFilaComprasQuery(string $tenantId, ContratacaoListFilter $filter, array $statuses): LengthAwarePaginator
    {
        $query = Contratacao::query()
            ->with(['criadoPor', 'analista'])
            ->withCount(['apontamentos as apontamentos_pendentes_count' => fn ($q) => $q->where('status', 'pendente')])
            ->where('tenant_id', $tenantId)
            ->whereIn('status', $statuses);

        if ($filter->dataInicio !== null) {
            $query->whereDate('created_at', '>=', $filter->dataInicio);
        }

        if ($filter->dataFim !== null) {
            $query->whereDate('created_at', '<=', $filter->dataFim);
        }

        if ($filter->numero !== null) {
            $numero = strtoupper($filter->numero);
            $query->where(function ($builder) use ($numero) {
                $builder
                    ->whereRaw('UPPER(SUBSTRING(REPLACE(uuid::text, \'-\', \'\'), 1, 8)) LIKE ?', ["%{$numero}%"])
                    ->orWhere('uuid', 'ilike', "%{$numero}%");
            });
        }

        return $query
            ->orderByDesc('created_at')
            ->paginate(perPage: $filter->perPage, page: $filter->page);
    }

    public function assumirAnalise(Contratacao $contratacao, string $analistaUsuarioId): Contratacao
    {
        $contratacao->status = ContratacaoStatus::EM_ANALISE;
        $contratacao->analista_usuario_id = $analistaUsuarioId;
        $contratacao->analise_iniciada_em = now();
        $contratacao->save();

        return $contratacao->fresh(['qqpItens', 'anexos', 'analista', 'criadoPor']);
    }

    public function countApontamentosPendentes(Contratacao $contratacao): int
    {
        return $contratacao->apontamentos()->where('status', 'pendente')->count();
    }

    public function retornarParaAjustes(Contratacao $contratacao): Contratacao
    {
        $contratacao->status = ContratacaoStatus::AGUARDANDO_AJUSTE_AREA;
        $contratacao->save();

        return $contratacao->fresh(['qqpItens', 'anexos', 'analista', 'criadoPor']);
    }

    public function aprovarAnalise(Contratacao $contratacao): Contratacao
    {
        $contratacao->status = ContratacaoStatus::APROVADO_COMPRAS;
        $contratacao->save();

        return $contratacao->fresh(['qqpItens', 'anexos', 'analista', 'criadoPor']);
    }

    public function assumirVendorList(Contratacao $contratacao, string $compradorUsuarioId): Contratacao
    {
        $contratacao->status = ContratacaoStatus::EM_VENDOR_LIST;
        $contratacao->analista_usuario_id = $compradorUsuarioId;
        $contratacao->save();

        return $contratacao->fresh(['qqpItens', 'anexos', 'analista', 'criadoPor']);
    }

    public function reenviarAposAjustes(Contratacao $contratacao): Contratacao
    {
        $contratacao->status = ContratacaoStatus::AGUARDANDO_ANALISE_COMPRAS;
        $contratacao->analista_usuario_id = null;
        $contratacao->analise_iniciada_em = null;
        $contratacao->save();

        return $contratacao->fresh(['qqpItens', 'anexos', 'analista', 'criadoPor']);
    }

    public function listApontamentos(Contratacao $contratacao, ?string $etapa = null): Collection
    {
        $query = $contratacao->apontamentos()->with(['autor', 'respondedor'])->orderBy('created_at');

        if ($etapa !== null && $etapa !== '') {
            $query->where('etapa', strtolower($etapa));
        }

        return $query->get();
    }

    public function findApontamentoForContratacao(Contratacao $contratacao, string $apontamentoId): ?ContratacaoApontamento
    {
        return $contratacao->apontamentos()
            ->where(function ($q) use ($apontamentoId) {
                $q->where('id', $apontamentoId)->orWhere('uuid', $apontamentoId);
            })
            ->first();
    }

    public function createApontamento(
        Contratacao $contratacao,
        string $tenantId,
        string $autorUsuarioId,
        string $etapa,
        string $descricao,
        ?UploadedFile $arquivo = null,
    ): ContratacaoApontamento {
        $uuid = (string) Str::uuid();
        $data = [
            'uuid' => $uuid,
            'tenant_id' => $tenantId,
            'contratacao_id' => $contratacao->id,
            'etapa' => $etapa,
            'descricao' => $descricao,
            'status' => 'pendente',
            'autor_usuario_id' => $autorUsuarioId,
        ];

        if ($arquivo instanceof UploadedFile && $arquivo->isValid()) {
            $data['nome_arquivo'] = $arquivo->getClientOriginalName();
            $data['storage_path'] = $this->storage->storeApontamento($tenantId, $contratacao->uuid, $uuid, $arquivo);
            $data['mime_type'] = $arquivo->getClientMimeType();
            $data['tamanho_bytes'] = $arquivo->getSize() ?: 0;
        }

        return ContratacaoApontamento::query()->create($data);
    }

    public function updateApontamento(
        ContratacaoApontamento $apontamento,
        string $descricao,
        ?UploadedFile $arquivo = null,
    ): ContratacaoApontamento {
        $apontamento->descricao = $descricao;

        if ($arquivo instanceof UploadedFile && $arquivo->isValid()) {
            if ($apontamento->storage_path) {
                $this->storage->delete($apontamento->storage_path);
            }
            $contratacao = $apontamento->contratacao;
            $apontamento->nome_arquivo = $arquivo->getClientOriginalName();
            $apontamento->storage_path = $this->storage->storeApontamento(
                $apontamento->tenant_id,
                $contratacao->uuid,
                $apontamento->uuid,
                $arquivo,
            );
            $apontamento->mime_type = $arquivo->getClientMimeType();
            $apontamento->tamanho_bytes = $arquivo->getSize() ?: 0;
        }

        $apontamento->save();

        return $apontamento->fresh(['autor', 'respondedor']);
    }

    public function deleteApontamento(ContratacaoApontamento $apontamento): void
    {
        if ($apontamento->storage_path) {
            $this->storage->delete($apontamento->storage_path);
        }

        $apontamento->delete();
    }

    public function responderApontamento(
        ContratacaoApontamento $apontamento,
        string $respondedorUsuarioId,
        string $resposta,
    ): ContratacaoApontamento {
        $apontamento->resposta = $resposta;
        $apontamento->status = 'respondido';
        $apontamento->respondedor_usuario_id = $respondedorUsuarioId;
        $apontamento->save();

        return $apontamento->fresh(['autor', 'respondedor']);
    }

    public function listByTenant(string $tenantId, ContratacaoListFilter $filter): LengthAwarePaginator
    {
        $query = Contratacao::query()
            ->with(['criadoPor', 'analista'])
            ->withCount(['apontamentos as apontamentos_pendentes_count' => fn ($q) => $q->where('status', 'pendente')])
            ->where('tenant_id', $tenantId);

        if ($filter->dataInicio !== null) {
            $query->whereDate('created_at', '>=', $filter->dataInicio);
        }

        if ($filter->dataFim !== null) {
            $query->whereDate('created_at', '<=', $filter->dataFim);
        }

        if ($filter->numero !== null) {
            $numero = strtoupper($filter->numero);
            $query->where(function ($builder) use ($numero) {
                $builder
                    ->whereRaw('UPPER(SUBSTRING(REPLACE(uuid::text, \'-\', \'\'), 1, 8)) LIKE ?', ["%{$numero}%"])
                    ->orWhere('uuid', 'ilike', "%{$numero}%");
            });
        }

        return $query
            ->orderByDesc('created_at')
            ->paginate(perPage: $filter->perPage, page: $filter->page);
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
                'valor_unitario' => $item->valorUnitario >= 0 ? $item->valorUnitario : 0,
            ]);
        }
    }

    private function resolveTermoReferenciaText(ContratacaoInput $input): ?string
    {
        if ($input->termoReferenciaCampos !== null) {
            return TermoReferenciaCampos::toText($input->termoReferenciaCampos);
        }

        return $input->termoReferencia;
    }
}

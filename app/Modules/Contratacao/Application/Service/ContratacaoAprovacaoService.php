<?php

namespace App\Modules\Contratacao\Application\Service;

use App\Models\Contratacao;
use App\Models\ContratacaoApontamento;
use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Application\DTO\ContratacaoListFilter;
use App\Modules\Contratacao\Application\DTO\ContratacaoOutput;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoRepositoryPort;
use App\Modules\Contratacao\Domain\ContratacaoStatus;
use App\Modules\Contratacao\Domain\Exceptions\ApontamentoNaoEditavelException;
use App\Modules\Contratacao\Domain\Exceptions\ApontamentoNaoEncontradoException;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoNaoEncontradaException;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoTransicaoInvalidaException;
use App\Modules\Contratacao\Domain\Policies\ApontamentoContratacaoPendente;
use App\Modules\Contratacao\Domain\Policies\ContratacaoElegivelParaAprovarAnalise;
use App\Modules\Contratacao\Domain\Policies\ContratacaoElegivelParaAssumirAnalise;
use App\Modules\Contratacao\Domain\Policies\ContratacaoElegivelParaRetornarAjustes;
use App\Modules\Contratacao\Domain\Policies\ContratacaoPertenceAoTenant;
use App\Modules\Contratacao\Infrastructure\Storage\ContratacaoAnexoStorage;
use App\Support\ApontamentoDescricaoSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

final class ContratacaoAprovacaoService
{
    public function __construct(
        private ContratacaoRepositoryPort $repository,
        private ContratacaoAnexoStorage $storage,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function listarPendentes(UsuarioCliente $usuario, ContratacaoListFilter $filter): array
    {
        $paginator = $this->repository->listPendentesAprovacao($usuario->tenant_id, $filter);

        return [
            'data' => collect($paginator->items())->map(fn (Contratacao $c) => ContratacaoOutput::listItem($c))->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function listarFila(UsuarioCliente $usuario, ContratacaoListFilter $filter): array
    {
        $paginator = $this->repository->listFilaCompras($usuario->tenant_id, $filter);

        return [
            'data' => collect($paginator->items())->map(fn (Contratacao $c) => ContratacaoOutput::listItem($c))->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function assumirAnalise(UsuarioCliente $usuario, string $uuid): array
    {
        $contratacao = $this->loadOrFail($uuid, $usuario->tenant_id);

        if (! ContratacaoElegivelParaAssumirAnalise::check($contratacao)) {
            throw new ContratacaoTransicaoInvalidaException('Contratação não está aguardando análise de Compras.');
        }

        $contratacao = $this->repository->assumirAnalise($contratacao, $usuario->id);

        return ContratacaoOutput::fromModel($contratacao);
    }

    /**
     * @return array<string, mixed>
     */
    public function obterDetalhe(UsuarioCliente $usuario, string $uuid): array
    {
        $contratacao = $this->loadOrFail($uuid, $usuario->tenant_id);

        return ContratacaoOutput::fromModel($contratacao);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarApontamentos(UsuarioCliente $usuario, string $uuid, ?string $etapa = null): array
    {
        $contratacao = $this->loadOrFail($uuid, $usuario->tenant_id);

        return $this->repository->listApontamentos($contratacao, $etapa)
            ->map(fn (ContratacaoApontamento $a) => $this->serializeApontamento($a))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function salvarApontamento(
        UsuarioCliente $usuario,
        string $uuid,
        string $etapa,
        ?string $descricao,
        ?UploadedFile $arquivo = null,
    ): array {
        $contratacao = $this->loadOrFail($uuid, $usuario->tenant_id);

        if ($contratacao->status !== ContratacaoStatus::EM_ANALISE) {
            throw new ContratacaoTransicaoInvalidaException('Apontamentos só podem ser criados durante a análise.');
        }

        $etapa = strtolower(trim($etapa));
        if (! in_array($etapa, ContratacaoStatus::ETAPAS_APONTAMENTO, true)) {
            throw new ContratacaoTransicaoInvalidaException('Etapa de apontamento inválida.');
        }

        $descricaoSanitizada = ApontamentoDescricaoSanitizer::sanitize((string) $descricao);
        $temDescricao = ApontamentoDescricaoSanitizer::temConteudoGravavel($descricaoSanitizada);
        $temArquivo = $arquivo instanceof UploadedFile && $arquivo->isValid();

        if (! $temDescricao && ! $temArquivo) {
            throw new ContratacaoTransicaoInvalidaException('Informe texto/imagem na descrição ou anexe um arquivo.');
        }

        if ($temArquivo && $arquivo->getSize() > 50 * 1024 * 1024) {
            throw new ContratacaoTransicaoInvalidaException('Arquivo muito grande. O tamanho máximo é 50MB.');
        }

        $apontamento = $this->repository->createApontamento(
            $contratacao,
            $usuario->tenant_id,
            $usuario->id,
            $etapa,
            $descricaoSanitizada,
            $temArquivo ? $arquivo : null,
        );

        return $this->serializeApontamento($apontamento);
    }

    public function excluirApontamento(UsuarioCliente $usuario, string $uuid, string $apontamentoId): void
    {
        $contratacao = $this->loadOrFail($uuid, $usuario->tenant_id);
        $apontamento = $this->findApontamentoOrFail($contratacao, $apontamentoId);

        if (! ApontamentoContratacaoPendente::estaPendente($apontamento)) {
            throw new ApontamentoNaoEditavelException;
        }

        $this->repository->deleteApontamento($apontamento);
    }

    /**
     * @return array{binario: string, nome: string, mime: string|null}
     */
    public function baixarAnexoApontamento(UsuarioCliente $usuario, string $uuid, string $apontamentoId): array
    {
        $contratacao = $this->loadOrFail($uuid, $usuario->tenant_id);
        $apontamento = $this->findApontamentoOrFail($contratacao, $apontamentoId);

        if ($apontamento->storage_path === null || $apontamento->storage_path === '') {
            throw new ApontamentoNaoEncontradoException;
        }

        $binario = $this->storage->get($apontamento->storage_path);
        if ($binario === null) {
            throw new ApontamentoNaoEncontradoException;
        }

        return [
            'binario' => $binario,
            'nome' => $apontamento->nome_arquivo ?? 'anexo',
            'mime' => $apontamento->mime_type,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function retornarParaAjustes(UsuarioCliente $usuario, string $uuid): array
    {
        $contratacao = $this->loadOrFail($uuid, $usuario->tenant_id);
        $pendentes = $this->repository->countApontamentosPendentes($contratacao);

        if (! ContratacaoElegivelParaRetornarAjustes::check($contratacao, $pendentes)) {
            throw new ContratacaoTransicaoInvalidaException('É necessário ao menos um apontamento pendente para retornar à Área.');
        }

        $contratacao = $this->repository->retornarParaAjustes($contratacao);

        return ContratacaoOutput::fromModel($contratacao);
    }

    /**
     * @return array<string, mixed>
     */
    public function aprovarAnalise(UsuarioCliente $usuario, string $uuid): array
    {
        $contratacao = $this->loadOrFail($uuid, $usuario->tenant_id);
        $pendentes = $this->repository->countApontamentosPendentes($contratacao);

        if (! ContratacaoElegivelParaAprovarAnalise::check($contratacao, $pendentes)) {
            throw new ContratacaoTransicaoInvalidaException('Existem apontamentos pendentes ou a contratação não está em análise.');
        }

        $contratacao = $this->repository->aprovarAnalise($contratacao);

        return ContratacaoOutput::fromModel($contratacao);
    }

    /**
     * @return array<string, mixed>
     */
    public function responderApontamento(UsuarioCliente $usuario, string $uuid, string $apontamentoId, string $resposta): array
    {
        $contratacao = $this->loadOrFail($uuid, $usuario->tenant_id);

        if ($contratacao->status !== ContratacaoStatus::AGUARDANDO_AJUSTE_AREA) {
            throw new ContratacaoTransicaoInvalidaException('Contratação não está aguardando resposta da Área.');
        }

        $apontamento = $this->findApontamentoOrFail($contratacao, $apontamentoId);

        if (! ApontamentoContratacaoPendente::estaPendente($apontamento)) {
            throw new ApontamentoNaoEditavelException;
        }

        $texto = trim($resposta);
        if ($texto === '') {
            throw new ContratacaoTransicaoInvalidaException('Informe a resposta ao apontamento.');
        }

        $apontamento = $this->repository->responderApontamento($apontamento, $usuario->id, $texto);

        return $this->serializeApontamento($apontamento);
    }

    /**
     * @return array<string, mixed>
     */
    public function reenviar(UsuarioCliente $usuario, string $uuid): array
    {
        $contratacao = $this->loadOrFail($uuid, $usuario->tenant_id);

        if ($contratacao->status !== ContratacaoStatus::AGUARDANDO_AJUSTE_AREA) {
            throw new ContratacaoTransicaoInvalidaException('Contratação não está aguardando reenvio da Área.');
        }

        $pendentes = $this->repository->countApontamentosPendentes($contratacao);
        if ($pendentes > 0) {
            throw new ContratacaoTransicaoInvalidaException('Responda todos os apontamentos antes de reenviar.');
        }

        $contratacao = $this->repository->reenviarAposAjustes($contratacao);

        return ContratacaoOutput::fromModel($contratacao);
    }

    private function loadOrFail(string $uuid, string $tenantId): Contratacao
    {
        $contratacao = $this->repository->findByUuidForTenant($uuid, $tenantId);

        if ($contratacao === null || ! ContratacaoPertenceAoTenant::check($contratacao, $tenantId)) {
            throw new ContratacaoNaoEncontradaException;
        }

        return $contratacao;
    }

    private function findApontamentoOrFail(Contratacao $contratacao, string $apontamentoId): ContratacaoApontamento
    {
        $apontamento = $this->repository->findApontamentoForContratacao($contratacao, $apontamentoId);

        if ($apontamento === null) {
            throw new ApontamentoNaoEncontradoException;
        }

        return $apontamento;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeApontamento(ContratacaoApontamento $apontamento): array
    {
        $apontamento->loadMissing(['autor', 'respondedor']);

        return [
            'id' => $apontamento->id,
            'uuid' => $apontamento->uuid,
            'etapa' => $apontamento->etapa,
            'descricao' => $apontamento->descricao,
            'status' => $apontamento->status,
            'resposta' => $apontamento->resposta,
            'autor_nome' => $apontamento->autor?->nome,
            'respondedor_nome' => $apontamento->respondedor?->nome,
            'nome_arquivo' => $apontamento->nome_arquivo,
            'tamanho_bytes' => (int) $apontamento->tamanho_bytes,
            'created_at' => $apontamento->created_at?->toIso8601String(),
            'updated_at' => $apontamento->updated_at?->toIso8601String(),
        ];
    }
}

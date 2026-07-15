<?php

namespace App\Modules\Contratacao\Application\Service;

use App\Models\Contratacao;
use App\Models\ContratacaoAberturaApontamento;
use App\Models\ContratacaoAberturaItem;
use App\Models\ContratacaoFornecedor;
use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Application\DTO\ContratacaoFornecedorOutput;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoFornecedorRepositoryPort;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoRepositoryPort;
use App\Modules\Contratacao\Domain\AberturaContratoStatus;
use App\Modules\Contratacao\Domain\AberturaContratoTemplate;
use App\Modules\Contratacao\Domain\AberturaItemStatusAnalise;
use App\Modules\Contratacao\Domain\ContratacaoStatus;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoNaoEncontradaException;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoTransicaoInvalidaException;
use App\Modules\Contratacao\Domain\Policies\ContratacaoPertenceAoTenant;
use App\Modules\Contratacao\Domain\Policies\UsuarioPodeEditarVendorList;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AberturaContratoService
{
    public function __construct(
        private ContratacaoRepositoryPort $contratacaoRepository,
        private ContratacaoFornecedorRepositoryPort $fornecedorRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function obter(UsuarioCliente $usuario, string $uuid, string $fornecedorUuid): array
    {
        [$contratacao, $fornecedor] = $this->loadContexto($usuario, $uuid, $fornecedorUuid, false);

        return $this->serializar($fornecedor);
    }

    /**
     * @return array<string, mixed>
     */
    public function solicitar(UsuarioCliente $usuario, string $uuid, string $fornecedorUuid): array
    {
        [$contratacao, $fornecedor] = $this->loadContexto($usuario, $uuid, $fornecedorUuid, true);

        if (! AberturaContratoStatus::permiteComprasSolicitar($fornecedor->abertura_contrato_status)) {
            throw new ContratacaoTransicaoInvalidaException('Abertura de contrato não pode ser solicitada no status atual.');
        }

        DB::transaction(function () use ($usuario, $fornecedor): void {
            if ($fornecedor->aberturaItens()->count() === 0) {
                foreach (AberturaContratoTemplate::itens() as $template) {
                    ContratacaoAberturaItem::query()->create([
                        'uuid' => (string) Str::uuid(),
                        'tenant_id' => $usuario->tenant_id,
                        'contratacao_fornecedor_id' => $fornecedor->id,
                        'codigo' => $template['codigo'],
                        'label' => $template['label'],
                        'ordem' => $template['ordem'],
                        'obrigatorio' => $template['obrigatorio'],
                        'condicional' => $template['condicional'],
                        'condicao' => $template['condicao'],
                        'controla_vencimento' => $template['controla_vencimento'],
                        'validade_dias' => $template['validade_dias'],
                        'parent_codigo' => $template['parent_codigo'],
                        'padrao' => true,
                        'status_analise' => AberturaItemStatusAnalise::PENDENTE,
                    ]);
                }
            }

            $this->fornecedorRepository->updateAberturaStatus(
                $fornecedor,
                AberturaContratoStatus::AGUARDANDO_ENVIO,
                now(),
            );
        });

        return $this->serializar($fornecedor->fresh(['aberturaItens']) ?? $fornecedor);
    }

    /**
     * @param array<string, mixed> $dados
     * @return array<string, mixed>
     */
    public function analisarItem(
        UsuarioCliente $usuario,
        string $uuid,
        string $fornecedorUuid,
        string $itemUuid,
        array $dados,
    ): array {
        [$contratacao, $fornecedor] = $this->loadContexto($usuario, $uuid, $fornecedorUuid, true);

        if (! AberturaContratoStatus::permiteAnaliseCompras($fornecedor->abertura_contrato_status)) {
            throw new ContratacaoTransicaoInvalidaException('Abertura de contrato não permite análise no status atual.');
        }

        $item = $fornecedor->aberturaItens()
            ->where(function ($q) use ($itemUuid) {
                $q->where('uuid', $itemUuid)->orWhere('id', $itemUuid);
            })
            ->first();

        if ($item === null) {
            throw new ContratacaoNaoEncontradaException;
        }

        $status = AberturaItemStatusAnalise::normalizar($dados['status_analise'] ?? null);
        if (! in_array($status, AberturaItemStatusAnalise::analise(), true)) {
            throw new ContratacaoTransicaoInvalidaException('Status de análise inválido. Use sim, nao ou na.');
        }

        $item->status_analise = $status;
        if (array_key_exists('observacao_analise', $dados)) {
            $item->observacao_analise = $dados['observacao_analise'];
        }
        if (array_key_exists('vencimento', $dados)) {
            $item->vencimento = $dados['vencimento'];
        }
        $item->save();

        if ($status === AberturaItemStatusAnalise::NAO
            && AberturaContratoStatus::normalizar($fornecedor->abertura_contrato_status) !== AberturaContratoStatus::EM_AJUSTE) {
            $this->fornecedorRepository->updateAberturaStatus($fornecedor, AberturaContratoStatus::EM_AJUSTE);
        }

        return $this->serializarItem($item->fresh() ?? $item);
    }

    /**
     * @return array<string, mixed>
     */
    public function confirmar(UsuarioCliente $usuario, string $uuid, string $fornecedorUuid): array
    {
        [$contratacao, $fornecedor] = $this->loadContexto($usuario, $uuid, $fornecedorUuid, true);

        if (AberturaContratoStatus::estaAceito($fornecedor->abertura_contrato_status)) {
            return $this->serializar($fornecedor);
        }

        if (! AberturaContratoStatus::permiteAnaliseCompras($fornecedor->abertura_contrato_status)) {
            throw new ContratacaoTransicaoInvalidaException('Abertura de contrato não pode ser confirmada no status atual.');
        }

        $itens = $fornecedor->aberturaItens()->orderBy('ordem')->get();
        if ($itens->isEmpty()) {
            throw new ContratacaoTransicaoInvalidaException('Solicite a abertura de contrato antes de confirmar.');
        }

        foreach ($itens as $item) {
            if (! $this->itemExigido($item, (bool) $fornecedor->optante_simples)) {
                continue;
            }

            if (! AberturaItemStatusAnalise::ehConforme($item->status_analise)) {
                throw new ContratacaoTransicaoInvalidaException(
                    "Item \"{$item->label}\" ainda não está conforme para confirmar a abertura.",
                );
            }
        }

        DB::transaction(function () use ($fornecedor): void {
            $this->fornecedorRepository->updateAberturaStatus(
                $fornecedor,
                AberturaContratoStatus::ACEITO,
                confirmadaEm: now(),
            );

            if (! $fornecedor->aceite) {
                $fornecedor->aceite = true;
                $fornecedor->save();
            }
        });

        return $this->serializar($fornecedor->fresh(['aberturaItens']) ?? $fornecedor);
    }

    /**
     * @return array<string, mixed>
     */
    public function abrirApontamento(
        UsuarioCliente $usuario,
        string $uuid,
        string $fornecedorUuid,
        string $itemUuid,
        string $descricao,
    ): array {
        [$contratacao, $fornecedor] = $this->loadContexto($usuario, $uuid, $fornecedorUuid, true);

        $item = $fornecedor->aberturaItens()
            ->where(function ($q) use ($itemUuid) {
                $q->where('uuid', $itemUuid)->orWhere('id', $itemUuid);
            })
            ->first();

        if ($item === null) {
            throw new ContratacaoNaoEncontradaException;
        }

        $apontamento = ContratacaoAberturaApontamento::query()->create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $usuario->tenant_id,
            'abertura_item_id' => $item->id,
            'descricao' => $descricao,
            'status' => 'aberto',
            'autor_origem' => 'COMPRAS',
        ]);

        if (AberturaContratoStatus::normalizar($fornecedor->abertura_contrato_status) !== AberturaContratoStatus::EM_AJUSTE
            && ! AberturaContratoStatus::estaAceito($fornecedor->abertura_contrato_status)) {
            $this->fornecedorRepository->updateAberturaStatus($fornecedor, AberturaContratoStatus::EM_AJUSTE);
        }

        return $this->serializarApontamento($apontamento);
    }

    /**
     * @return array<string, mixed>
     */
    public function responderApontamento(
        UsuarioCliente $usuario,
        string $uuid,
        string $fornecedorUuid,
        string $apontamentoUuid,
        string $resposta,
    ): array {
        [$contratacao, $fornecedor] = $this->loadContexto($usuario, $uuid, $fornecedorUuid, true);
        $apontamento = $this->loadApontamento($fornecedor, $apontamentoUuid);

        if ($apontamento->status === 'encerrado') {
            throw new ContratacaoTransicaoInvalidaException('Apontamento já está encerrado.');
        }

        $apontamento->resposta = $resposta;
        $apontamento->status = 'respondido';
        $apontamento->save();

        return $this->serializarApontamento($apontamento);
    }

    /**
     * @return array<string, mixed>
     */
    public function encerrarApontamento(
        UsuarioCliente $usuario,
        string $uuid,
        string $fornecedorUuid,
        string $apontamentoUuid,
    ): array {
        [$contratacao, $fornecedor] = $this->loadContexto($usuario, $uuid, $fornecedorUuid, true);
        $apontamento = $this->loadApontamento($fornecedor, $apontamentoUuid);

        $apontamento->status = 'encerrado';
        $apontamento->save();

        return $this->serializarApontamento($apontamento);
    }

    /**
     * @return array{0: Contratacao, 1: ContratacaoFornecedor}
     */
    private function loadContexto(UsuarioCliente $usuario, string $uuid, string $fornecedorUuid, bool $exigeEdicao): array
    {
        $contratacao = $this->contratacaoRepository->findByUuidForTenant($uuid, $usuario->tenant_id);

        if ($contratacao === null || ! ContratacaoPertenceAoTenant::check($contratacao, $usuario->tenant_id)) {
            throw new ContratacaoNaoEncontradaException;
        }

        if (! in_array($contratacao->status, [
            ContratacaoStatus::EM_VENDOR_LIST,
            ContratacaoStatus::VENCEDOR_DEFINIDO,
        ], true)) {
            throw new ContratacaoTransicaoInvalidaException('Contratação não está elegível para abertura de contrato.');
        }

        if ($exigeEdicao && ! UsuarioPodeEditarVendorList::check($usuario, $contratacao)) {
            throw new ContratacaoTransicaoInvalidaException('Sem permissão para editar a lista de fornecedores.');
        }

        $fornecedor = $this->fornecedorRepository->findByUuidForContratacao($contratacao, $fornecedorUuid);
        if ($fornecedor === null) {
            throw new ContratacaoNaoEncontradaException;
        }

        return [$contratacao, $fornecedor];
    }

    private function loadApontamento(ContratacaoFornecedor $fornecedor, string $apontamentoUuid): ContratacaoAberturaApontamento
    {
        $apontamento = ContratacaoAberturaApontamento::query()
            ->where(function ($q) use ($apontamentoUuid) {
                $q->where('uuid', $apontamentoUuid)->orWhere('id', $apontamentoUuid);
            })
            ->whereHas('item', fn ($q) => $q->where('contratacao_fornecedor_id', $fornecedor->id))
            ->first();

        if ($apontamento === null) {
            throw new ContratacaoNaoEncontradaException;
        }

        return $apontamento;
    }

    private function itemExigido(ContratacaoAberturaItem $item, bool $optanteSimples): bool
    {
        if (! $item->obrigatorio) {
            return false;
        }

        if (! $item->condicional) {
            return true;
        }

        if ($item->condicao === AberturaContratoTemplate::CONDICAO_OPTANTE_SIMPLES) {
            return $optanteSimples;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializar(ContratacaoFornecedor $fornecedor): array
    {
        $fornecedor->loadMissing(['aberturaItens.apontamentos']);

        return [
            'status' => AberturaContratoStatus::normalizar($fornecedor->abertura_contrato_status),
            'fornecedor' => ContratacaoFornecedorOutput::fromModel($fornecedor),
            'itens' => $fornecedor->aberturaItens->map(fn (ContratacaoAberturaItem $item) => $this->serializarItem($item))->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializarItem(ContratacaoAberturaItem $item): array
    {
        $item->loadMissing('apontamentos');

        return [
            'uuid' => $item->uuid,
            'codigo' => $item->codigo,
            'label' => $item->label,
            'ordem' => $item->ordem,
            'obrigatorio' => (bool) $item->obrigatorio,
            'condicional' => (bool) $item->condicional,
            'condicao' => $item->condicao,
            'controla_vencimento' => (bool) $item->controla_vencimento,
            'validade_dias' => $item->validade_dias,
            'parent_codigo' => $item->parent_codigo,
            'padrao' => (bool) $item->padrao,
            'status_analise' => $item->status_analise,
            'observacao_analise' => $item->observacao_analise,
            'vencimento' => $item->vencimento?->format('Y-m-d'),
            'nome_arquivo' => $item->nome_arquivo,
            'apontamentos' => $item->apontamentos->map(fn ($a) => $this->serializarApontamento($a))->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializarApontamento(ContratacaoAberturaApontamento $apontamento): array
    {
        return [
            'uuid' => $apontamento->uuid,
            'descricao' => $apontamento->descricao,
            'status' => $apontamento->status,
            'autor_origem' => $apontamento->autor_origem,
            'resposta' => $apontamento->resposta,
            'created_at' => $apontamento->created_at?->toIso8601String(),
        ];
    }
}

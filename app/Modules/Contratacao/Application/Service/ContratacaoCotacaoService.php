<?php

namespace App\Modules\Contratacao\Application\Service;

use App\Models\Contratacao;
use App\Models\ContratacaoAvaliacaoTecnica;
use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Application\DTO\ContratacaoFornecedorOutput;
use App\Modules\Contratacao\Application\DTO\ContratacaoOutput;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoFornecedorRepositoryPort;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoRepositoryPort;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoNaoEncontradaException;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoTransicaoInvalidaException;
use App\Modules\Contratacao\Domain\FornecedorParticipacaoStatus;
use App\Modules\Contratacao\Domain\Policies\ContratacaoElegivelParaAprovarVendorList;
use App\Modules\Contratacao\Domain\Policies\ContratacaoElegivelParaVendorList;
use App\Modules\Contratacao\Domain\Policies\ContratacaoPertenceAoTenant;
use App\Modules\Contratacao\Domain\Policies\UsuarioPodeEditarVendorList;
use Illuminate\Support\Facades\DB;

final class ContratacaoCotacaoService
{
    public function __construct(
        private ContratacaoRepositoryPort $contratacaoRepository,
        private ContratacaoFornecedorRepositoryPort $fornecedorRepository,
    ) {}

    /**
     * @param array<string, mixed> $dados
     * @return array<string, mixed>
     */
    public function salvarProposta(UsuarioCliente $usuario, string $uuid, string $fornecedorUuid, array $dados): array
    {
        $contratacao = $this->loadVendorListEditavel($usuario, $uuid);
        $fornecedor = $this->loadFornecedor($contratacao, $fornecedorUuid);

        $atualizado = $this->fornecedorRepository->updateProposta($fornecedor, $dados);

        return ContratacaoFornecedorOutput::fromModel($atualizado);
    }

    /**
     * @return array<string, mixed>
     */
    public function definirVencedor(UsuarioCliente $usuario, string $uuid, string $fornecedorUuid): array
    {
        $contratacao = $this->loadVendorListEditavel($usuario, $uuid);
        $fornecedor = $this->loadFornecedor($contratacao, $fornecedorUuid);

        $vencedor = $this->fornecedorRepository->definirVencedor($contratacao, $fornecedor);

        return ContratacaoFornecedorOutput::fromModel($vencedor);
    }

    /**
     * @return array<string, mixed>
     */
    public function aprovarVendorList(UsuarioCliente $usuario, string $uuid): array
    {
        $contratacao = $this->loadVendorListEditavel($usuario, $uuid);
        $fornecedores = $this->fornecedorRepository->listByContratacao($contratacao);
        $avaliacao = ContratacaoAvaliacaoTecnica::query()
            ->where('contratacao_id', $contratacao->id)
            ->first();

        if (! ContratacaoElegivelParaAprovarVendorList::check($contratacao, $fornecedores, $avaliacao)) {
            throw new ContratacaoTransicaoInvalidaException(
                ContratacaoElegivelParaAprovarVendorList::motivoFalha($contratacao, $fornecedores, $avaliacao),
            );
        }

        /** @var \App\Models\ContratacaoFornecedor $vencedor */
        $vencedor = $fornecedores->firstWhere('vencedor', true);

        DB::transaction(function () use ($contratacao, $fornecedores, $vencedor): void {
            foreach ($fornecedores as $fornecedor) {
                if ($fornecedor->id === $vencedor->id) {
                    $fornecedor->status_participacao = FornecedorParticipacaoStatus::VENCEDOR;
                    $fornecedor->vencedor = true;
                } else {
                    $fornecedor->status_participacao = FornecedorParticipacaoStatus::DESQUALIFICADA;
                    $fornecedor->vencedor = false;
                }
                $fornecedor->save();
            }

            $this->contratacaoRepository->marcarVencedorDefinido($contratacao);
        });

        $atualizada = $this->contratacaoRepository->findByUuidForTenant($uuid, $usuario->tenant_id);
        $detalhe = ContratacaoOutput::fromModel($atualizada ?? $contratacao);
        $detalhe['fornecedores'] = $this->fornecedorRepository
            ->listByContratacao($atualizada ?? $contratacao)
            ->map(fn ($f) => ContratacaoFornecedorOutput::fromModel($f))
            ->values()
            ->all();
        $detalhe['fornecedor_vencedor_uuid'] = $vencedor->uuid;

        return $detalhe;
    }

    private function loadVendorListEditavel(UsuarioCliente $usuario, string $uuid): Contratacao
    {
        $contratacao = $this->contratacaoRepository->findByUuidForTenant($uuid, $usuario->tenant_id);

        if ($contratacao === null || ! ContratacaoPertenceAoTenant::check($contratacao, $usuario->tenant_id)) {
            throw new ContratacaoNaoEncontradaException;
        }

        if (! ContratacaoElegivelParaVendorList::check($contratacao)) {
            throw new ContratacaoTransicaoInvalidaException('Contratação não está em análise de fornecedores.');
        }

        if (! UsuarioPodeEditarVendorList::check($usuario, $contratacao)) {
            throw new ContratacaoTransicaoInvalidaException('Sem permissão para editar a lista de fornecedores.');
        }

        return $contratacao;
    }

    private function loadFornecedor(Contratacao $contratacao, string $fornecedorUuid): \App\Models\ContratacaoFornecedor
    {
        $fornecedor = $this->fornecedorRepository->findByUuidForContratacao($contratacao, $fornecedorUuid);

        if ($fornecedor === null) {
            throw new ContratacaoNaoEncontradaException;
        }

        return $fornecedor;
    }
}

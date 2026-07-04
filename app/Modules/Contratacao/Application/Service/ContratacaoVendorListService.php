<?php

namespace App\Modules\Contratacao\Application\Service;

use App\Models\Contratacao;
use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Application\DTO\ContratacaoFornecedorOutput;
use App\Modules\Contratacao\Application\DTO\ContratacaoOutput;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoFornecedorRepositoryPort;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoRepositoryPort;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoNaoEncontradaException;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoTransicaoInvalidaException;
use App\Modules\Contratacao\Domain\Policies\ContratacaoElegivelParaVendorList;
use App\Modules\Contratacao\Domain\Policies\ContratacaoPertenceAoTenant;
use App\Modules\Contratacao\Domain\Policies\FornecedorCnpjUnicoNaContratacao;
use App\Modules\Contratacao\Domain\Policies\UsuarioPodeEditarVendorList;

final class ContratacaoVendorListService
{
    public function __construct(
        private ContratacaoRepositoryPort $contratacaoRepository,
        private ContratacaoFornecedorRepositoryPort $fornecedorRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function obterDetalhe(UsuarioCliente $usuario, string $uuid): array
    {
        $contratacao = $this->loadOrFail($uuid, $usuario->tenant_id);

        if (! ContratacaoElegivelParaVendorList::check($contratacao)) {
            throw new ContratacaoTransicaoInvalidaException('Contratação não está em análise de fornecedores.');
        }

        $detalhe = ContratacaoOutput::fromModel($contratacao);
        $detalhe['fornecedores'] = $this->serializarFornecedores($contratacao);

        return $detalhe;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarFornecedores(UsuarioCliente $usuario, string $uuid): array
    {
        $contratacao = $this->loadOrFail($uuid, $usuario->tenant_id);

        if (! ContratacaoElegivelParaVendorList::check($contratacao)) {
            throw new ContratacaoTransicaoInvalidaException('Contratação não está em análise de fornecedores.');
        }

        return $this->serializarFornecedores($contratacao);
    }

    /**
     * @param array<string, mixed> $dados
     * @return array<string, mixed>
     */
    public function cadastrarFornecedor(UsuarioCliente $usuario, string $uuid, array $dados): array
    {
        $contratacao = $this->loadOrFail($uuid, $usuario->tenant_id);

        if (! ContratacaoElegivelParaVendorList::check($contratacao)) {
            throw new ContratacaoTransicaoInvalidaException('Contratação não está em análise de fornecedores.');
        }

        if (! UsuarioPodeEditarVendorList::check($usuario, $contratacao)) {
            throw new ContratacaoTransicaoInvalidaException('Sem permissão para editar a lista de fornecedores.');
        }

        $cnpjsExistentes = $this->fornecedorRepository
            ->listByContratacao($contratacao)
            ->pluck('cnpj')
            ->all();

        if (! FornecedorCnpjUnicoNaContratacao::check((string) $dados['cnpj'], $cnpjsExistentes)) {
            throw new ContratacaoTransicaoInvalidaException('Já existe um fornecedor com este CNPJ nesta contratação.');
        }

        $fornecedor = $this->fornecedorRepository->create($contratacao, $usuario->tenant_id, $dados);

        return ContratacaoFornecedorOutput::fromModel($fornecedor);
    }

    public function removerFornecedor(UsuarioCliente $usuario, string $uuid, string $fornecedorUuid): void
    {
        $contratacao = $this->loadOrFail($uuid, $usuario->tenant_id);

        if (! ContratacaoElegivelParaVendorList::check($contratacao)) {
            throw new ContratacaoTransicaoInvalidaException('Contratação não está em análise de fornecedores.');
        }

        if (! UsuarioPodeEditarVendorList::check($usuario, $contratacao)) {
            throw new ContratacaoTransicaoInvalidaException('Sem permissão para editar a lista de fornecedores.');
        }

        $fornecedor = $this->fornecedorRepository->findByUuidForContratacao($contratacao, $fornecedorUuid);

        if ($fornecedor === null) {
            throw new ContratacaoNaoEncontradaException;
        }

        $this->fornecedorRepository->delete($fornecedor);
    }

    private function loadOrFail(string $uuid, string $tenantId): Contratacao
    {
        $contratacao = $this->contratacaoRepository->findByUuidForTenant($uuid, $tenantId);

        if ($contratacao === null || ! ContratacaoPertenceAoTenant::check($contratacao, $tenantId)) {
            throw new ContratacaoNaoEncontradaException;
        }

        return $contratacao;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function serializarFornecedores(Contratacao $contratacao): array
    {
        return $this->fornecedorRepository
            ->listByContratacao($contratacao)
            ->map(fn ($f) => ContratacaoFornecedorOutput::fromModel($f))
            ->values()
            ->all();
    }
}

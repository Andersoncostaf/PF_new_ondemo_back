<?php

namespace App\Modules\Contratacao\Application\Service;

use App\Models\Contratacao;
use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Application\DTO\ContratacaoFornecedorOutput;
use App\Modules\Contratacao\Application\DTO\ContratacaoOutput;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoFornecedorRepositoryPort;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoRepositoryPort;
use App\Modules\Contratacao\Application\Port\Out\FornecedorCatalogoRepositoryPort;
use App\Modules\Contratacao\Application\Port\Out\FornecedorHistoricoRepositoryPort;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoNaoEncontradaException;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoTransicaoInvalidaException;
use App\Modules\Contratacao\Domain\Policies\ContratacaoElegivelParaVendorList;
use App\Modules\Contratacao\Domain\Policies\ContratacaoPertenceAoTenant;
use App\Modules\Contratacao\Domain\Policies\FornecedorCnpjUnicoNaContratacao;
use App\Modules\Contratacao\Domain\Policies\UsuarioPodeEditarVendorList;
use App\Modules\Contratacao\Infrastructure\External\BrasilApiCnpjClient;

final class ContratacaoVendorListService
{
    public function __construct(
        private ContratacaoRepositoryPort $contratacaoRepository,
        private ContratacaoFornecedorRepositoryPort $fornecedorRepository,
        private FornecedorCatalogoRepositoryPort $catalogoRepository,
        private FornecedorHistoricoRepositoryPort $historicoRepository,
        private BrasilApiCnpjClient $brasilApi,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function obterDetalhe(UsuarioCliente $usuario, string $uuid): array
    {
        $contratacao = $this->loadOrFail($uuid, $usuario->tenant_id);

        if (! ContratacaoElegivelParaVendorList::checkConsulta($contratacao)) {
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

        if (! ContratacaoElegivelParaVendorList::checkConsulta($contratacao)) {
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

    /**
     * @return array<string, mixed>
     */
    public function buscarFornecedorPorCnpj(UsuarioCliente $usuario, string $uuid, string $cnpj): array
    {
        $contratacao = $this->loadOrFail($uuid, $usuario->tenant_id);

        if (! ContratacaoElegivelParaVendorList::check($contratacao)) {
            throw new ContratacaoTransicaoInvalidaException('Contratação não está em análise de fornecedores.');
        }

        $cnpjNorm = FornecedorCnpjUnicoNaContratacao::normalizarCnpj($cnpj);
        if (! FornecedorCnpjUnicoNaContratacao::cnpjValido($cnpjNorm)) {
            return ['encontrado' => false];
        }

        $catalogo = $this->catalogoRepository->findAtivoByCnpj($usuario->tenant_id, $cnpjNorm);
        if ($catalogo !== null) {
            return ['encontrado' => true, ...$catalogo];
        }

        $historico = $this->historicoRepository->findByCnpj($contratacao, $usuario->tenant_id, $cnpjNorm);
        if ($historico !== null) {
            return ['encontrado' => true, ...$historico];
        }

        $brasil = $this->brasilApi->consultar($cnpjNorm);
        if ($brasil !== null) {
            return [
                'encontrado' => true,
                'origem' => 'brasil_api',
                'cnpj' => $brasil['cnpj'] ?? $cnpjNorm,
                'razao_social' => $brasil['razao_social'] ?? null,
                'telefone' => $brasil['telefone'] ?? null,
                'email' => $brasil['email'] ?? null,
                'vendedor' => $brasil['vendedor'] ?? null,
                'cidade' => $brasil['cidade'] ?? null,
                'uf' => $brasil['uf'] ?? null,
            ];
        }

        return ['encontrado' => false];
    }

    /**
     * @return array<string, mixed>
     */
    public function registrarAceiteParticipacao(UsuarioCliente $usuario, string $uuid, string $fornecedorUuid): array
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

        if ($fornecedor->aceite) {
            return ContratacaoFornecedorOutput::fromModel($fornecedor);
        }

        $atualizado = $this->fornecedorRepository->marcarAceiteParticipacao($fornecedor);

        return ContratacaoFornecedorOutput::fromModel($atualizado);
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

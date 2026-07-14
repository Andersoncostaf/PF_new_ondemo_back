<?php

namespace App\Modules\Contratacao\Application\Service;

use App\Models\Contratacao;
use App\Models\ContratacaoFornecedor;
use App\Models\ContratacaoPropostaApontamento;
use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoFornecedorRepositoryPort;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoRepositoryPort;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoNaoEncontradaException;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoTransicaoInvalidaException;
use App\Modules\Contratacao\Domain\Policies\ContratacaoElegivelParaVendorList;
use App\Modules\Contratacao\Domain\Policies\ContratacaoPertenceAoTenant;
use App\Modules\Contratacao\Domain\Policies\UsuarioPodeEditarVendorList;
use Illuminate\Support\Str;

final class PropostaApontamentoService
{
    public function __construct(
        private ContratacaoRepositoryPort $contratacaoRepository,
        private ContratacaoFornecedorRepositoryPort $fornecedorRepository,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listar(UsuarioCliente $usuario, string $uuid, string $fornecedorUuid): array
    {
        [, $fornecedor] = $this->loadContexto($usuario, $uuid, $fornecedorUuid, false);

        return $fornecedor->propostaApontamentos()
            ->orderBy('created_at')
            ->get()
            ->map(fn (ContratacaoPropostaApontamento $a) => $this->serializar($a))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function abrir(UsuarioCliente $usuario, string $uuid, string $fornecedorUuid, string $descricao): array
    {
        [, $fornecedor] = $this->loadContexto($usuario, $uuid, $fornecedorUuid, true);

        $apontamento = ContratacaoPropostaApontamento::query()->create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $usuario->tenant_id,
            'contratacao_fornecedor_id' => $fornecedor->id,
            'descricao' => $descricao,
            'status' => 'aberto',
            'autor_origem' => 'COMPRAS',
        ]);

        return $this->serializar($apontamento);
    }

    /**
     * @return array<string, mixed>
     */
    public function responder(
        UsuarioCliente $usuario,
        string $uuid,
        string $fornecedorUuid,
        string $apontamentoUuid,
        string $resposta,
    ): array {
        [, $fornecedor] = $this->loadContexto($usuario, $uuid, $fornecedorUuid, true);
        $apontamento = $this->loadApontamento($fornecedor, $apontamentoUuid);

        if ($apontamento->status === 'encerrado') {
            throw new ContratacaoTransicaoInvalidaException('Apontamento já está encerrado.');
        }

        $apontamento->resposta = $resposta;
        $apontamento->status = 'respondido';
        $apontamento->save();

        return $this->serializar($apontamento);
    }

    /**
     * @return array<string, mixed>
     */
    public function encerrar(
        UsuarioCliente $usuario,
        string $uuid,
        string $fornecedorUuid,
        string $apontamentoUuid,
    ): array {
        [, $fornecedor] = $this->loadContexto($usuario, $uuid, $fornecedorUuid, true);
        $apontamento = $this->loadApontamento($fornecedor, $apontamentoUuid);

        $apontamento->status = 'encerrado';
        $apontamento->save();

        return $this->serializar($apontamento);
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

        if (! ContratacaoElegivelParaVendorList::checkConsulta($contratacao)) {
            throw new ContratacaoTransicaoInvalidaException('Contratação não está em análise de fornecedores.');
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

    private function loadApontamento(ContratacaoFornecedor $fornecedor, string $apontamentoUuid): ContratacaoPropostaApontamento
    {
        $apontamento = $fornecedor->propostaApontamentos()
            ->where(function ($q) use ($apontamentoUuid) {
                $q->where('uuid', $apontamentoUuid)->orWhere('id', $apontamentoUuid);
            })
            ->first();

        if ($apontamento === null) {
            throw new ContratacaoNaoEncontradaException;
        }

        return $apontamento;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializar(ContratacaoPropostaApontamento $apontamento): array
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

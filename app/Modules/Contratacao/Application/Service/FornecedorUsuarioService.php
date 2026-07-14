<?php

namespace App\Modules\Contratacao\Application\Service;

use App\Models\Contratacao;
use App\Models\ContratacaoFornecedor;
use App\Models\ContratacaoFornecedorUsuario;
use App\Models\UsuarioCliente;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoFornecedorRepositoryPort;
use App\Modules\Contratacao\Application\Port\Out\ContratacaoRepositoryPort;
use App\Modules\Contratacao\Domain\ContratacaoStatus;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoNaoEncontradaException;
use App\Modules\Contratacao\Domain\Exceptions\ContratacaoTransicaoInvalidaException;
use App\Modules\Contratacao\Domain\FornecedorUsuarioPerfil;
use App\Modules\Contratacao\Domain\Policies\ContratacaoPertenceAoTenant;
use App\Modules\Contratacao\Domain\Policies\UsuarioPodeEditarVendorList;
use Illuminate\Support\Str;

final class FornecedorUsuarioService
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

        return $fornecedor->usuarios()
            ->orderBy('created_at')
            ->get()
            ->map(fn (ContratacaoFornecedorUsuario $u) => $this->serializar($u))
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $dados
     * @return array<string, mixed>
     */
    public function cadastrar(UsuarioCliente $usuario, string $uuid, string $fornecedorUuid, array $dados): array
    {
        [, $fornecedor] = $this->loadContexto($usuario, $uuid, $fornecedorUuid, true);

        $ativos = $fornecedor->usuarios()->where('ativo', true)->count();
        if ($ativos >= FornecedorUsuarioPerfil::LIMITE_USUARIOS_ATIVOS) {
            throw new ContratacaoTransicaoInvalidaException(
                'Limite de '.FornecedorUsuarioPerfil::LIMITE_USUARIOS_ATIVOS.' usuários ativos atingido para este fornecedor.',
            );
        }

        $perfil = FornecedorUsuarioPerfil::normalizar($dados['perfil'] ?? FornecedorUsuarioPerfil::PADRAO);

        $registro = ContratacaoFornecedorUsuario::query()->create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $usuario->tenant_id,
            'contratacao_fornecedor_id' => $fornecedor->id,
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'telefone' => $dados['telefone'] ?? null,
            'perfil' => $perfil,
            'ativo' => true,
        ]);

        return $this->serializar($registro);
    }

    /**
     * @param array<string, mixed> $dados
     * @return array<string, mixed>
     */
    public function atualizarPerfil(UsuarioCliente $usuario, string $uuid, string $fornecedorUuid, string $usuarioUuid, array $dados): array
    {
        [, $fornecedor] = $this->loadContexto($usuario, $uuid, $fornecedorUuid, true);
        $registro = $this->loadUsuario($fornecedor, $usuarioUuid);

        $novoPerfil = array_key_exists('perfil', $dados)
            ? FornecedorUsuarioPerfil::normalizar($dados['perfil'])
            : $registro->perfil;

        if (FornecedorUsuarioPerfil::isAdmin($registro->perfil)
            && ! FornecedorUsuarioPerfil::isAdmin($novoPerfil)
            && $this->contarAdminsAtivos($fornecedor, $registro->id) < 1) {
            throw new ContratacaoTransicaoInvalidaException('Não é possível remover o último usuário ADMIN ativo do fornecedor.');
        }

        if (array_key_exists('nome', $dados)) {
            $registro->nome = $dados['nome'];
        }
        if (array_key_exists('email', $dados)) {
            $registro->email = $dados['email'];
        }
        if (array_key_exists('telefone', $dados)) {
            $registro->telefone = $dados['telefone'];
        }
        if (array_key_exists('perfil', $dados)) {
            $registro->perfil = $novoPerfil;
        }

        $registro->save();

        return $this->serializar($registro);
    }

    /**
     * @return array<string, mixed>
     */
    public function inativar(UsuarioCliente $usuario, string $uuid, string $fornecedorUuid, string $usuarioUuid): array
    {
        [, $fornecedor] = $this->loadContexto($usuario, $uuid, $fornecedorUuid, true);
        $registro = $this->loadUsuario($fornecedor, $usuarioUuid);

        if (! $registro->ativo) {
            return $this->serializar($registro);
        }

        if (FornecedorUsuarioPerfil::isAdmin($registro->perfil)
            && $this->contarAdminsAtivos($fornecedor, $registro->id) < 1) {
            throw new ContratacaoTransicaoInvalidaException('Não é possível inativar o último usuário ADMIN ativo do fornecedor.');
        }

        $registro->ativo = false;
        $registro->save();

        return $this->serializar($registro);
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
            throw new ContratacaoTransicaoInvalidaException('Contratação não está elegível para gestão de usuários do fornecedor.');
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

    private function loadUsuario(ContratacaoFornecedor $fornecedor, string $usuarioUuid): ContratacaoFornecedorUsuario
    {
        $registro = $fornecedor->usuarios()
            ->where(function ($q) use ($usuarioUuid) {
                $q->where('uuid', $usuarioUuid)->orWhere('id', $usuarioUuid);
            })
            ->first();

        if ($registro === null) {
            throw new ContratacaoNaoEncontradaException;
        }

        return $registro;
    }

    private function contarAdminsAtivos(ContratacaoFornecedor $fornecedor, ?string $excetoId = null): int
    {
        $query = $fornecedor->usuarios()
            ->where('ativo', true)
            ->where('perfil', FornecedorUsuarioPerfil::ADMIN);

        if ($excetoId !== null) {
            $query->where('id', '!=', $excetoId);
        }

        return $query->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializar(ContratacaoFornecedorUsuario $usuario): array
    {
        return [
            'uuid' => $usuario->uuid,
            'nome' => $usuario->nome,
            'email' => $usuario->email,
            'telefone' => $usuario->telefone,
            'perfil' => $usuario->perfil,
            'ativo' => (bool) $usuario->ativo,
            'created_at' => $usuario->created_at?->toIso8601String(),
        ];
    }
}

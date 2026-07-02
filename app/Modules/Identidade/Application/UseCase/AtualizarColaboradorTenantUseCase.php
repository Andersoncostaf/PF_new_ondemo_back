<?php

namespace App\Modules\Identidade\Application\UseCase;

use App\Models\UsuarioCliente;
use App\Modules\Identidade\Application\Port\Out\UsuarioClienteRepositoryPort;
use App\Modules\Identidade\Domain\Exceptions\ColaboradorNaoEncontradoException;
use App\Modules\Identidade\Domain\Exceptions\PerfilOperacionalInvalidoException;
use App\Modules\Identidade\Domain\Policies\PerfilOperacionalColaborador;

final class AtualizarColaboradorTenantUseCase
{
    public function __construct(
        private UsuarioClienteRepositoryPort $repository,
    ) {}

    /**
     * @param  array{nome?: string, cargo?: string|null, perfil?: string, status?: string}  $dados
     * @return array<string, mixed>
     */
    public function executar(UsuarioCliente $admin, string $colaboradorUuid, array $dados): array
    {
        $colaborador = $this->repository->findByUuidForTenant($colaboradorUuid, $admin->tenant_id);

        if ($colaborador === null || $colaborador->id === $admin->id) {
            throw new ColaboradorNaoEncontradoException;
        }

        $attributes = [];

        if (isset($dados['nome'])) {
            $attributes['nome'] = $dados['nome'];
        }

        if (array_key_exists('cargo', $dados)) {
            $attributes['cargo'] = $dados['cargo'];
        }

        if (isset($dados['status']) && in_array($dados['status'], ['ativo', 'inativo'], true)) {
            $attributes['status'] = $dados['status'];
        }

        if (isset($dados['perfil'])) {
            if (! PerfilOperacionalColaborador::isValid($dados['perfil'])) {
                throw new PerfilOperacionalInvalidoException;
            }
            $attributes['perfil'] = $dados['perfil'];
        }

        $colaborador = $this->repository->update($colaborador, $attributes);

        return [
            'id' => $colaborador->id,
            'nome' => $colaborador->nome,
            'email' => $colaborador->email,
            'cargo' => $colaborador->cargo,
            'perfil' => $colaborador->perfil,
            'status' => $colaborador->status,
        ];
    }
}

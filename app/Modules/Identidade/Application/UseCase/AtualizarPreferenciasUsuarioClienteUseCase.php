<?php

namespace App\Modules\Identidade\Application\UseCase;

use App\Models\UsuarioCliente;
use App\Modules\Identidade\Application\Port\Out\UsuarioClienteRepositoryPort;

final class AtualizarPreferenciasUsuarioClienteUseCase
{
    public function __construct(
        private UsuarioClienteRepositoryPort $repository,
    ) {}

    /**
     * @param  array{theme: string}  $input
     * @return array<string, mixed>
     */
    public function executar(UsuarioCliente $usuario, array $input): array
    {
        $preferencias = is_array($usuario->preferencias) ? $usuario->preferencias : [];
        $preferencias['theme'] = $input['theme'];

        $usuario = $this->repository->update($usuario, [
            'preferencias' => $preferencias,
        ]);

        return [
            'preferencias' => $usuario->preferencias ?? [],
        ];
    }
}

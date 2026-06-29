<?php

namespace App\Modules\Identidade\Application\UseCase;

use App\Models\UsuarioCliente;
use App\Modules\Identidade\Application\DTO\ModuloItem;
use App\Modules\Identidade\Domain\Policies\UsuarioClienteElegivelParaContratacao;

final class ObterModulosUsuarioClienteUseCase
{
    /**
     * @return list<ModuloItem>
     */
    public function executar(UsuarioCliente $usuario): array
    {
        $modulos = [];

        if (UsuarioClienteElegivelParaContratacao::check($usuario)) {
            $modulos[] = new ModuloItem(
                codigo: 'contratacao',
                label: 'Contratação de Serviços',
                rota: '/contratacao',
            );
        }

        return $modulos;
    }
}

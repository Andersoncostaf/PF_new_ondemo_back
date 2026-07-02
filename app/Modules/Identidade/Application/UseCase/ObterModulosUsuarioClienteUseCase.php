<?php

namespace App\Modules\Identidade\Application\UseCase;

use App\Models\UsuarioCliente;
use App\Modules\Identidade\Application\DTO\ModuloItem;
use App\Modules\Identidade\Domain\Policies\UsuarioClienteComTenantElegivel;
use App\Modules\Identidade\Domain\Policies\UsuarioClienteEAdminTenant;
use App\Modules\Identidade\Domain\Policies\UsuarioClienteElegivelParaContratacaoAprovacao;
use App\Modules\Identidade\Domain\Policies\UsuarioClienteElegivelParaContratacaoWizard;

final class ObterModulosUsuarioClienteUseCase
{
    /**
     * @return list<ModuloItem>
     */
    public function executar(UsuarioCliente $usuario): array
    {
        if (! UsuarioClienteComTenantElegivel::check($usuario)) {
            return [];
        }

        $modulos = [];

        if (UsuarioClienteElegivelParaContratacaoWizard::check($usuario)) {
            $modulos[] = new ModuloItem(
                codigo: 'contratacao',
                label: 'Contratação de Serviços',
                rota: '/contratacao',
                grupo: 'Contratação',
            );
        }

        if (UsuarioClienteElegivelParaContratacaoAprovacao::check($usuario)) {
            $modulos[] = new ModuloItem(
                codigo: 'contratacao_aprovacao',
                label: 'Aprovar solicitações',
                rota: '/contratacao/aprovacao',
                grupo: 'Compras',
            );
        }

        if (UsuarioClienteEAdminTenant::check($usuario)) {
            $modulos[] = new ModuloItem(
                codigo: 'admin_usuarios',
                label: 'Usuários da empresa',
                rota: '/admin/usuarios',
                grupo: 'Administração',
            );
        }

        if (in_array($usuario->perfil, ['fiscal', 'admin_tenant'], true)) {
            $modulos[] = new ModuloItem(
                codigo: 'nota_fiscal',
                label: 'Nota de Serviço',
                rota: '/nota-fiscal',
                grupo: 'Fiscal',
            );
        }

        if (in_array($usuario->perfil, ['auditoria', 'admin_tenant'], true)) {
            $modulos[] = new ModuloItem(
                codigo: 'auditoria',
                label: 'Auditoria',
                rota: '/admin/auditoria',
                grupo: 'Auditoria',
            );
        }

        return $modulos;
    }
}

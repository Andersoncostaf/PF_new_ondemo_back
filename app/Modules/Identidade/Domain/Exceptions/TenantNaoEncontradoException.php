<?php

namespace App\Modules\Identidade\Domain\Exceptions;

final class TenantNaoEncontradoException extends IdentidadeDomainException
{
    public function __construct()
    {
        parent::__construct('Tenant não encontrado para este host.');
    }

    public function statusCode(): int
    {
        return 404;
    }
}

<?php

namespace App\Modules\Identidade\Domain\Exceptions;

final class EmailJaCadastradoNoTenantException extends IdentidadeDomainException
{
    public function __construct()
    {
        parent::__construct('E-mail já cadastrado neste tenant.');
    }

    public function statusCode(): int
    {
        return 422;
    }
}

<?php

namespace App\Modules\Identidade\Domain\Exceptions;

final class CredenciaisInvalidasException extends IdentidadeDomainException
{
    public function __construct()
    {
        parent::__construct('Credenciais inválidas.');
    }

    public function statusCode(): int
    {
        return 401;
    }
}

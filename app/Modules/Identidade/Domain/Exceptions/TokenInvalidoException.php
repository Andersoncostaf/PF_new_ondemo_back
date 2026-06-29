<?php

namespace App\Modules\Identidade\Domain\Exceptions;

final class TokenInvalidoException extends IdentidadeDomainException
{
    public function __construct(string $message = 'Token inválido ou expirado.')
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return 401;
    }
}

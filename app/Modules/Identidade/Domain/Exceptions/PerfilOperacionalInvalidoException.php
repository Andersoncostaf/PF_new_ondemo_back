<?php

namespace App\Modules\Identidade\Domain\Exceptions;

final class PerfilOperacionalInvalidoException extends IdentidadeDomainException
{
    public function __construct()
    {
        parent::__construct('Perfil operacional inválido.');
    }

    public function statusCode(): int
    {
        return 422;
    }
}

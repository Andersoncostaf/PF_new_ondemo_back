<?php

namespace App\Modules\Identidade\Domain\Exceptions;

final class SlugReservadoException extends IdentidadeDomainException
{
    public function __construct()
    {
        parent::__construct('Este endereço não está disponível.');
    }

    public function statusCode(): int
    {
        return 422;
    }
}

<?php

namespace App\Modules\Identidade\Domain\Exceptions;

final class SlugDuplicadoException extends IdentidadeDomainException
{
    public function __construct()
    {
        parent::__construct('Slug já está em uso.');
    }

    public function statusCode(): int
    {
        return 422;
    }
}

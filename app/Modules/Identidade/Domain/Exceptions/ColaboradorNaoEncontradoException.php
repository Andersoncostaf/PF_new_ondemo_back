<?php

namespace App\Modules\Identidade\Domain\Exceptions;

final class ColaboradorNaoEncontradoException extends IdentidadeDomainException
{
    public function __construct()
    {
        parent::__construct('Colaborador não encontrado.');
    }

    public function statusCode(): int
    {
        return 404;
    }
}

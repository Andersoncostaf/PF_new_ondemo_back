<?php

namespace App\Modules\Identidade\Domain\Exceptions;

final class CnpjDuplicadoException extends IdentidadeDomainException
{
    public function __construct()
    {
        parent::__construct('CNPJ já cadastrado.');
    }

    public function statusCode(): int
    {
        return 422;
    }
}

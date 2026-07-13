<?php

namespace App\Modules\Contratacao\Domain\Exceptions;

final class ApontamentoNaoEncontradoException extends ContratacaoDomainException
{
    public function __construct()
    {
        parent::__construct('Apontamento não encontrado.');
    }

    public function statusCode(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'APONTAMENTO_NAO_ENCONTRADO';
    }
}

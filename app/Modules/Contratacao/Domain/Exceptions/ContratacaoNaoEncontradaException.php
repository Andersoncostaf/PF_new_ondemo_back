<?php

namespace App\Modules\Contratacao\Domain\Exceptions;

final class ContratacaoNaoEncontradaException extends ContratacaoDomainException
{
    public function __construct()
    {
        parent::__construct('Contratação não encontrada.');
    }

    public function statusCode(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'CONTRATACAO_NAO_ENCONTRADA';
    }
}

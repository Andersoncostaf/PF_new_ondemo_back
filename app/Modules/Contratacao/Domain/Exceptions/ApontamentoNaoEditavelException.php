<?php

namespace App\Modules\Contratacao\Domain\Exceptions;

final class ApontamentoNaoEditavelException extends ContratacaoDomainException
{
    public function __construct(string $message = 'Apontamento não pode ser alterado.')
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'APONTAMENTO_NAO_EDITAVEL';
    }
}

<?php

namespace App\Modules\Contratacao\Domain\Exceptions;

final class ContratacaoTransicaoInvalidaException extends ContratacaoDomainException
{
    public function __construct(string $message = 'Transição de status não permitida.')
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'CONTRATACAO_TRANSICAO_INVALIDA';
    }
}

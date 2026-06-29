<?php

namespace App\Modules\Contratacao\Domain\Exceptions;

final class ContratacaoIncompletaException extends ContratacaoDomainException
{
    public function __construct(string $detail = 'Preencha todos os campos obrigatórios e ao menos um item QQP.')
    {
        parent::__construct($detail);
    }

    public function statusCode(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'CONTRATACAO_INCOMPLETA';
    }
}

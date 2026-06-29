<?php

namespace App\Modules\Contratacao\Domain\Exceptions;

final class ContratacaoNaoEditavelException extends ContratacaoDomainException
{
    public function __construct()
    {
        parent::__construct('Contratação não pode ser editada neste status.');
    }

    public function statusCode(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'CONTRATACAO_NAO_EDITAVEL';
    }
}

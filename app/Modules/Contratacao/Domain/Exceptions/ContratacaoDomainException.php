<?php

namespace App\Modules\Contratacao\Domain\Exceptions;

use Exception;

abstract class ContratacaoDomainException extends Exception
{
    abstract public function statusCode(): int;

    abstract public function errorCode(): string;

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
        ];
    }
}

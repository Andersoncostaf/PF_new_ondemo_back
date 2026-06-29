<?php

namespace App\Modules\Identidade\Domain\Exceptions;

use Exception;

abstract class IdentidadeDomainException extends Exception
{
    abstract public function statusCode(): int;

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return ['message' => $this->getMessage()];
    }
}

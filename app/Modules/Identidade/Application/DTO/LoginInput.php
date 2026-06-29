<?php

namespace App\Modules\Identidade\Application\DTO;

final class LoginInput
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {}
}

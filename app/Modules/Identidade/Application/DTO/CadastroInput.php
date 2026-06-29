<?php

namespace App\Modules\Identidade\Application\DTO;

final class CadastroInput
{
    public function __construct(
        public readonly string $razaoSocial,
        public readonly string $cnpj,
        public readonly ?string $slug,
        public readonly string $nome,
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $cargo,
    ) {}
}

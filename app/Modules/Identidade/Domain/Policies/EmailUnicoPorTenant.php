<?php

namespace App\Modules\Identidade\Domain\Policies;

final class EmailUnicoPorTenant
{
    public static function check(bool $emailJaCadastradoNoTenant): bool
    {
        return ! $emailJaCadastradoNoTenant;
    }
}

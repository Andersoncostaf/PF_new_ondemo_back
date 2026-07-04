<?php

namespace App\Modules\Contratacao\Domain\Policies;

final class FornecedorCnpjUnicoNaContratacao
{
    public static function normalizarCnpj(string $cnpj): string
    {
        return preg_replace('/\D+/', '', $cnpj) ?? '';
    }

    public static function cnpjValido(string $cnpj): bool
    {
        $digits = self::normalizarCnpj($cnpj);

        return strlen($digits) === 14;
    }

    /**
     * @param iterable<string> $cnpjsExistentes
     */
    public static function check(string $cnpj, iterable $cnpjsExistentes): bool
    {
        $normalizado = self::normalizarCnpj($cnpj);

        foreach ($cnpjsExistentes as $existente) {
            if (self::normalizarCnpj($existente) === $normalizado) {
                return false;
            }
        }

        return true;
    }
}

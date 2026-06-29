<?php

namespace App\Modules\Identidade\Domain\Services;

final class CnpjValidator
{
    public static function isValid(string $cnpj): bool
    {
        $digits = preg_replace('/\D/', '', $cnpj) ?? '';

        if (strlen($digits) !== 14) {
            return false;
        }

        if (preg_match('/^(\d)\1{13}$/', $digits)) {
            return false;
        }

        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $base = substr($digits, 0, 12);
        $sum = 0;

        foreach ($weights1 as $i => $weight) {
            $sum += (int) $base[$i] * $weight;
        }

        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;

        if ((int) $digits[12] !== $digit1) {
            return false;
        }

        $sum = 0;
        $baseWithDigit1 = $base.$digit1;

        foreach ($weights2 as $i => $weight) {
            $sum += (int) $baseWithDigit1[$i] * $weight;
        }

        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;

        return (int) $digits[13] === $digit2;
    }

    public static function normalize(string $cnpj): string
    {
        return preg_replace('/\D/', '', $cnpj) ?? '';
    }
}

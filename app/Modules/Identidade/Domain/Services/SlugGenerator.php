<?php

namespace App\Modules\Identidade\Domain\Services;

use Illuminate\Support\Str;

final class SlugGenerator
{
    public static function fromRazaoSocial(string $razaoSocial): string
    {
        $slug = Str::slug($razaoSocial, '-', 'pt');

        if ($slug === '') {
            $slug = 'tenant';
        }

        return substr($slug, 0, 64);
    }
}

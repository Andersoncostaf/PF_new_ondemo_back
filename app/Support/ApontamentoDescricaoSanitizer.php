<?php

declare(strict_types=1);

namespace App\Support;

final class ApontamentoDescricaoSanitizer
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><ol><ul><li><a><img><span><h1><h2><h3><h4><blockquote>';

    public static function maxBytes(): int
    {
        $n = (int) env('AJUSTE_DESCRICAO_MAX_BYTES', 524288);

        return $n >= 10240 ? $n : 524288;
    }

    public static function sanitize(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $html = preg_replace('/<\s*script\b[^>]*>.*?<\s*\/\s*script\s*>/is', '', $html) ?? '';
        $html = preg_replace('/\bon\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace('/javascript\s*:/i', '', $html) ?? '';

        return strip_tags($html, self::ALLOWED_TAGS);
    }

    public static function temConteudoGravavel(?string $html): bool
    {
        if ($html === null || trim($html) === '') {
            return false;
        }

        $semTags = preg_replace('/<[^>]*>/', '', $html) ?? '';
        $semTags = str_replace([' ', "\t", "\n", "\r", '&nbsp;'], '', $semTags);
        if ($semTags !== '') {
            return true;
        }

        return (bool) preg_match('/<img\b/i', $html);
    }
}

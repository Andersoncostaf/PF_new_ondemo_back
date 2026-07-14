<?php

namespace App\Modules\Contratacao\Infrastructure\External;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Busca pública na web para completar CNPJ, contatos e redes sociais de um fornecedor.
 */
final class WebEnriquecerFornecedorClient
{
    /**
     * @param array{cnpj?: string, razao_social?: string, cidade?: ?string, uf?: ?string} $entrada
     * @return array<string, mixed>
     */
    public function enriquecer(array $entrada): array
    {
        if (! config('contratacao.enrichment.web_search.enabled', true)) {
            return [];
        }

        $nome = trim((string) ($entrada['razao_social'] ?? ''));
        $cnpj = preg_replace('/\D+/', '', (string) ($entrada['cnpj'] ?? '')) ?? '';
        if ($nome === '' && strlen($cnpj) !== 14) {
            return [];
        }

        $query = $this->montarQuery($nome, $cnpj, $entrada);
        if ($query === '') {
            return [];
        }

        $timeout = (int) config('contratacao.enrichment.web_search.timeout_seconds', 20);

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'User-Agent' => 'PortalFornecedorOnDemand/1.0 (+enriquecer-fornecedor)',
                    'Accept-Language' => 'pt-BR,pt;q=0.9',
                ])
                ->asForm()
                ->post('https://html.duckduckgo.com/html/', [
                    'q' => $query,
                    'kl' => 'br-pt',
                ]);

            if (! $response->successful()) {
                Log::warning('web enriquecer fornecedor falhou', ['status' => $response->status()]);

                return [];
            }

            return $this->extrair($response->body(), $nome, $cnpj);
        } catch (\Throwable $e) {
            Log::warning('web enriquecer fornecedor exception', ['message' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @param array<string, mixed> $entrada
     */
    private function montarQuery(string $nome, string $cnpj, array $entrada): string
    {
        $local = trim(implode(' ', array_filter([
            (string) ($entrada['cidade'] ?? ''),
            (string) ($entrada['uf'] ?? ''),
        ])));

        if (strlen($cnpj) === 14) {
            return trim("CNPJ {$cnpj} telefone email contato {$local}");
        }

        return trim("\"{$nome}\" CNPJ telefone email {$local} Brasil");
    }

    /**
     * @return array<string, mixed>
     */
    private function extrair(string $html, string $nomePreferido, string $cnpjPreferido): array
    {
        $urls = $this->extrairUrls($html);
        $textos = $this->extrairTextos($html);
        $blob = implode(' ', $textos).' '.implode(' ', $urls);

        $cnpj = strlen($cnpjPreferido) === 14 ? $cnpjPreferido : ($this->extrairCnpj($blob) ?? '');
        $telefone = $this->extrairTelefone($blob);
        $email = $this->extrairEmail($blob);
        $redes = $this->classificarUrls($urls);

        $resultado = array_filter([
            'cnpj' => $cnpj !== '' ? $cnpj : null,
            'razao_social' => $nomePreferido !== '' ? $nomePreferido : null,
            'telefone' => $telefone,
            'email' => $email,
            'site' => $redes['site'] ?? null,
            'instagram' => $redes['instagram'] ?? null,
            'linkedin' => $redes['linkedin'] ?? null,
            'facebook' => $redes['facebook'] ?? null,
            'fonte' => 'web',
        ], fn ($v) => $v !== null && $v !== '');

        return $resultado;
    }

    /**
     * @return list<string>
     */
    private function extrairUrls(string $html): array
    {
        $urls = [];

        if (preg_match_all('/uddg=([^&"]+)/i', $html, $matches)) {
            foreach ($matches[1] as $encoded) {
                $url = urldecode($encoded);
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    $urls[] = $url;
                }
            }
        }

        if (preg_match_all('/href="(https?:\/\/[^"]+)"/i', $html, $hrefs)) {
            foreach ($hrefs[1] as $url) {
                if (str_contains($url, 'duckduckgo.com')) {
                    continue;
                }
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    $urls[] = $url;
                }
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * @return list<string>
     */
    private function extrairTextos(string $html): array
    {
        $textos = [];

        foreach ([
            '/class="[^"]*result__a[^"]*"[^>]*>(.*?)<\/a>/is',
            '/class="[^"]*result__snippet[^"]*"[^>]*>(.*?)<\/(?:a|td|div)>/is',
        ] as $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                foreach ($matches[1] as $raw) {
                    $text = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
                    if ($text !== '') {
                        $textos[] = $text;
                    }
                }
            }
        }

        return $textos;
    }

    /**
     * @param list<string> $urls
     * @return array<string, string>
     */
    private function classificarUrls(array $urls): array
    {
        $out = [];

        foreach ($urls as $url) {
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            $host = preg_replace('/^www\./', '', $host) ?? $host;

            if (str_contains($host, 'instagram.com') && ! isset($out['instagram'])) {
                $out['instagram'] = $url;
                continue;
            }
            if (str_contains($host, 'linkedin.com') && ! isset($out['linkedin'])) {
                $out['linkedin'] = $url;
                continue;
            }
            if ((str_contains($host, 'facebook.com') || str_contains($host, 'fb.com')) && ! isset($out['facebook'])) {
                $out['facebook'] = $url;
                continue;
            }

            $bloqueados = [
                'duckduckgo.com', 'google.', 'youtube.', 'wikipedia.', 'gov.br',
                'receita', 'jusbrasil', 'cnpj.biz', 'econodata', 'casadosdados',
                'empresascnpj', 'cnpja', 'brasilapi',
            ];
            $bloqueado = false;
            foreach ($bloqueados as $b) {
                if (str_contains($host, $b)) {
                    $bloqueado = true;
                    break;
                }
            }

            if (! $bloqueado && ! isset($out['site']) && preg_match('/\.(com|com\.br|net|org|br)$/i', $host)) {
                $out['site'] = $url;
            }
        }

        return $out;
    }

    private function extrairCnpj(string $texto): ?string
    {
        if (preg_match('/\b(\d{2}\.?\d{3}\.?\d{3}\/?\d{4}-?\d{2})\b/', $texto, $m)) {
            $digits = preg_replace('/\D+/', '', $m[1]) ?? '';

            return strlen($digits) === 14 ? $digits : null;
        }

        return null;
    }

    private function extrairTelefone(string $texto): ?string
    {
        if (preg_match('/\(?\d{2}\)?\s?\d{4,5}-?\d{4}/', $texto, $m)) {
            $digits = preg_replace('/\D+/', '', $m[0]) ?? '';

            return (strlen($digits) === 10 || strlen($digits) === 11) ? $digits : null;
        }

        return null;
    }

    private function extrairEmail(string $texto): ?string
    {
        if (preg_match('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $texto, $m)) {
            return strtolower($m[0]);
        }

        return null;
    }
}

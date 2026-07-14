<?php

namespace App\Modules\Contratacao\Infrastructure\N8n;

use App\Modules\Contratacao\Application\Port\Out\N8nSugestaoFornecedorPort;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Busca pública na web (DuckDuckGo) para sugerir fornecedores sem depender de n8n/LLM.
 */
final class WebSearchSugestaoFornecedorClient implements N8nSugestaoFornecedorPort
{
    public function solicitarSugestoes(
        string $tenantId,
        string $contratacaoUuid,
        array $brief,
    ): array {
        if (! config('contratacao.sugestao.web_search.enabled', true)) {
            return [];
        }

        $slots = max(1, min(5, (int) ($brief['slots_restantes'] ?? 3)));
        $query = $this->montarQuery($brief);
        if ($query === '') {
            return [];
        }

        $timeout = (int) config('contratacao.sugestao.web_search.timeout_seconds', 20);

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'User-Agent' => 'PortalFornecedorOnDemand/1.0 (+sugestao-fornecedores)',
                    'Accept-Language' => 'pt-BR,pt;q=0.9',
                ])
                ->asForm()
                ->post('https://html.duckduckgo.com/html/', [
                    'q' => $query,
                    'kl' => 'br-pt',
                ]);

            if (! $response->successful()) {
                Log::warning('websearch sugestao fornecedor falhou', [
                    'status' => $response->status(),
                    'contratacao_uuid' => $contratacaoUuid,
                ]);

                return [];
            }

            return $this->extrairSugestoes($response->body(), $brief, $slots);
        } catch (\Throwable $e) {
            Log::warning('websearch sugestao fornecedor exception', [
                'message' => $e->getMessage(),
                'contratacao_uuid' => $contratacaoUuid,
            ]);

            return [];
        }
    }

    /**
     * @param array<string, mixed> $brief
     */
    private function montarQuery(array $brief): string
    {
        $categoria = trim((string) ($brief['categoria_servico'] ?? ''));
        $titulo = trim((string) ($brief['titulo'] ?? ''));
        $local = trim((string) ($brief['local'] ?? ''));
        $endereco = trim((string) ($brief['empresa_endereco'] ?? ''));
        $onde = $local !== '' ? $local : $endereco;
        $servico = $categoria !== '' ? $categoria : $titulo;

        if ($servico === '' && $onde === '') {
            return '';
        }

        $partes = array_filter([
            $servico,
            'fornecedor empresa',
            $onde,
            'Brasil',
        ], fn ($p) => is_string($p) && trim($p) !== '');

        return implode(' ', $partes);
    }

    /**
     * @param array<string, mixed> $brief
     * @return list<array<string, mixed>>
     */
    private function extrairSugestoes(string $html, array $brief, int $slots): array
    {
        $excluir = array_map(
            fn ($c) => preg_replace('/\D+/', '', (string) $c) ?? '',
            $brief['cnpjs_excluir'] ?? [],
        );
        $excluir = array_flip(array_filter($excluir));

        $titulos = [];
        if (preg_match_all(
            '/class="[^"]*result__a[^"]*"[^>]*>(.*?)<\/a>/is',
            $html,
            $matches,
        )) {
            foreach ($matches[1] as $raw) {
                $titulo = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $titulo = trim(preg_replace('/\s+/', ' ', $titulo) ?? '');
                if ($titulo !== '') {
                    $titulos[] = $titulo;
                }
            }
        }

        // Fallback: âncoras genéricas de resultado
        if ($titulos === [] && preg_match_all('/<a[^>]+class="[^"]*result__url[^"]*"[^>]*>/i', $html) === 0) {
            if (preg_match_all('/uddg=([^&"]+)/i', $html, $uddg)) {
                foreach ($uddg[1] as $encoded) {
                    $url = urldecode($encoded);
                    $host = parse_url($url, PHP_URL_HOST);
                    if (is_string($host) && $host !== '') {
                        $titulos[] = $host;
                    }
                }
            }
        }

        $snippets = [];
        if (preg_match_all(
            '/class="[^"]*result__snippet[^"]*"[^>]*>(.*?)<\/(?:a|td|div)>/is',
            $html,
            $snipMatches,
        )) {
            foreach ($snipMatches[1] as $raw) {
                $snippets[] = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        $resultado = [];
        $vistos = [];
        $urls = $this->extrairUrls($html);

        foreach ($titulos as $index => $titulo) {
            $nome = $this->normalizarNomeEmpresa($titulo);
            if ($nome === null) {
                continue;
            }

            $chave = mb_strtolower($nome);
            if (isset($vistos[$chave])) {
                continue;
            }
            $vistos[$chave] = true;

            $snippet = $snippets[$index] ?? '';
            $cnpj = $this->extrairCnpj($titulo.' '.$snippet);
            if ($cnpj !== '' && isset($excluir[$cnpj])) {
                continue;
            }

            [$cidade, $uf] = $this->inferirLocalidade($brief, $snippet);
            $url = $urls[$index] ?? null;
            $redes = $url ? $this->classificarUrl($url) : [];

            $resultado[] = [
                'cnpj' => $cnpj,
                'razao_social' => $nome,
                'telefone' => $this->extrairTelefone($snippet),
                'email' => $this->extrairEmail($snippet),
                'cidade' => $cidade,
                'uf' => $uf,
                'site' => $redes['site'] ?? null,
                'instagram' => $redes['instagram'] ?? null,
                'linkedin' => $redes['linkedin'] ?? null,
                'facebook' => $redes['facebook'] ?? null,
                'motivo' => sprintf(
                    'Encontrado na busca web para “%s” próximo a %s. Valide CNPJ e contatos antes de cadastrar.',
                    trim((string) ($brief['categoria_servico'] ?? $brief['titulo'] ?? 'serviço')),
                    trim((string) ($brief['local'] ?? $brief['empresa_endereco'] ?? 'o local informado')) ?: 'o local informado',
                ),
            ];

            if (count($resultado) >= $slots) {
                break;
            }
        }

        return $resultado;
    }

    /**
     * @return list<string>
     */
    private function extrairUrls(string $html): array
    {
        $urls = [];
        if (preg_match_all('/uddg=([^&"]+)/i', $html, $uddg)) {
            foreach ($uddg[1] as $encoded) {
                $url = urldecode($encoded);
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    $urls[] = $url;
                }
            }
        }

        return $urls;
    }

    /**
     * @return array<string, string>
     */
    private function classificarUrl(string $url): array
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host) ?? $host;

        if (str_contains($host, 'instagram.com')) {
            return ['instagram' => $url];
        }
        if (str_contains($host, 'linkedin.com')) {
            return ['linkedin' => $url];
        }
        if (str_contains($host, 'facebook.com') || str_contains($host, 'fb.com')) {
            return ['facebook' => $url];
        }

        return ['site' => $url];
    }

    private function normalizarNomeEmpresa(string $titulo): ?string
    {
        $titulo = trim($titulo);
        if ($titulo === '' || mb_strlen($titulo) < 3) {
            return null;
        }

        // Descarta resultados genéricos de busca/diretórios sem empresa clara
        $bloqueados = [
            'duckduckgo',
            'google',
            'youtube',
            'wikipedia',
            'facebook',
            'instagram',
            'linkedin',
            'mercado livre',
            'olx',
            'gov.br',
            'receita federal',
            'listas amarelas',
            'páginas amarelas',
            'paginas amarelas',
            'guia mais',
            'apontador',
            'telelistas',
        ];
        $lower = mb_strtolower($titulo);
        foreach ($bloqueados as $b) {
            if (str_contains($lower, $b)) {
                return null;
            }
        }

        // Títulos genéricos de categoria/localidade (sem nome de empresa)
        if (preg_match('/^(reparo|reparação|manutenção|serviços?|fornecedores?)\b/iu', $titulo)
            && ! preg_match('/\b(ltda|me|eireli|s\.?a\.?|comercio|comércio|engenharia|elétrica|eletrica|construtora|servicos|serviços)\b/iu', $titulo)
        ) {
            return null;
        }

        // Remove sufixos de site (" - Site oficial", " | Contato")
        $nome = preg_replace('/\s*[\|\-–—:].*$/u', '', $titulo) ?? $titulo;
        $nome = trim($nome);
        if ($nome === '' || mb_strlen($nome) < 3) {
            return null;
        }

        // Hostnames → nome legível
        if (str_contains($nome, '.') && ! str_contains($nome, ' ')) {
            $nome = Str::of($nome)
                ->replaceFirst('www.', '')
                ->before('.')
                ->replace(['-', '_'], ' ')
                ->title()
                ->toString();
        }

        return mb_substr($nome, 0, 180);
    }

    private function extrairCnpj(string $texto): string
    {
        if (preg_match('/\b(\d{2}\.?\d{3}\.?\d{3}\/?\d{4}-?\d{2})\b/', $texto, $m)) {
            $digits = preg_replace('/\D+/', '', $m[1]) ?? '';

            return strlen($digits) === 14 ? $digits : '';
        }

        return '';
    }

    private function extrairTelefone(string $texto): ?string
    {
        if (preg_match('/\(?\d{2}\)?\s?\d{4,5}-?\d{4}/', $texto, $m)) {
            return preg_replace('/\D+/', '', $m[0]);
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

    /**
     * @param array<string, mixed> $brief
     * @return array{0: ?string, 1: ?string}
     */
    private function inferirLocalidade(array $brief, string $snippet): array
    {
        $fonte = trim((string) ($brief['local'] ?? '').' '.(string) ($brief['empresa_endereco'] ?? '').' '.$snippet);
        $uf = null;
        $cidade = null;

        if (preg_match('/\b([A-Z]{2})\b/', $fonte, $m)) {
            $uf = strtoupper($m[1]);
        }

        if (preg_match('/([A-Za-zÀ-ÿ\s]{3,})\s*-\s*([A-Z]{2})\b/u', $fonte, $m)) {
            $cidade = trim($m[1]);
            $uf = strtoupper($m[2]);
        }

        return [$cidade, $uf];
    }
}

<?php

namespace App\Modules\Contratacao\Infrastructure\Persistence;

use App\Models\Contratacao;
use App\Models\ContratacaoSugestaoCache;
use App\Modules\Contratacao\Application\Port\Out\SugestaoCacheRepositoryPort;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final class EloquentSugestaoCacheRepository implements SugestaoCacheRepositoryPort
{
    public function buscarValido(Contratacao $contratacao, string $contextoHash): ?array
    {
        $cache = ContratacaoSugestaoCache::query()
            ->where('contratacao_id', $contratacao->id)
            ->where('contexto_hash', $contextoHash)
            ->where('expira_em', '>', Carbon::now())
            ->first();

        if ($cache === null) {
            return null;
        }

        $payload = $cache->payload_json;
        if (! is_array($payload)) {
            return null;
        }

        $payload['meta'] = array_merge($payload['meta'] ?? [], ['cache_hit' => true]);

        return $payload;
    }

    public function salvar(Contratacao $contratacao, string $tenantId, string $contextoHash, array $payload): void
    {
        $ttlHours = (int) config('contratacao.sugestao.cache_ttl_hours', 24);

        $cache = ContratacaoSugestaoCache::query()
            ->where('contratacao_id', $contratacao->id)
            ->first();

        if ($cache === null) {
            ContratacaoSugestaoCache::query()->create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'contratacao_id' => $contratacao->id,
                'payload_json' => $payload,
                'contexto_hash' => $contextoHash,
                'expira_em' => Carbon::now()->addHours($ttlHours),
                'created_at' => Carbon::now(),
            ]);

            return;
        }

        $cache->update([
            'tenant_id' => $tenantId,
            'payload_json' => $payload,
            'contexto_hash' => $contextoHash,
            'expira_em' => Carbon::now()->addHours($ttlHours),
            'created_at' => Carbon::now(),
        ]);
    }

    public function invalidar(Contratacao $contratacao): void
    {
        ContratacaoSugestaoCache::query()
            ->where('contratacao_id', $contratacao->id)
            ->delete();
    }
}

<?php

namespace App\Modules\Identidade\Application\UseCase;

use App\Modules\Identidade\Application\Port\Out\TenantRepositoryPort;
use App\Modules\Identidade\Domain\Policies\ReservedSlugPolicy;
use Illuminate\Support\Str;

final class VerificarSlugDisponivelUseCase
{
    public function __construct(
        private TenantRepositoryPort $tenantRepository,
    ) {}

    /**
     * @return array{disponivel: bool, slug: string, sugestao: string|null}
     */
    public function executar(string $rawSlug): array
    {
        $slug = Str::slug(strtolower(trim($rawSlug)), '-', 'pt');

        if ($slug === '' || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return [
                'disponivel' => false,
                'slug' => $slug,
                'sugestao' => null,
            ];
        }

        if (ReservedSlugPolicy::isReserved($slug)) {
            return [
                'disponivel' => false,
                'slug' => $slug,
                'sugestao' => $this->suggestAlternative($slug),
            ];
        }

        if ($this->tenantRepository->existsBySlug($slug)) {
            return [
                'disponivel' => false,
                'slug' => $slug,
                'sugestao' => $this->suggestAlternative($slug),
            ];
        }

        return [
            'disponivel' => true,
            'slug' => $slug,
            'sugestao' => null,
        ];
    }

    private function suggestAlternative(string $baseSlug): string
    {
        for ($suffix = 2; $suffix <= 99; $suffix++) {
            $candidate = "{$baseSlug}-{$suffix}";

            if (
                ! ReservedSlugPolicy::isReserved($candidate)
                && ! $this->tenantRepository->existsBySlug($candidate)
            ) {
                return $candidate;
            }
        }

        return "{$baseSlug}-".uniqid();
    }
}

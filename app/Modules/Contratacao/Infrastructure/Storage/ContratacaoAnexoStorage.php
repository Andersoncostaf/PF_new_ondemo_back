<?php

namespace App\Modules\Contratacao\Infrastructure\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ContratacaoAnexoStorage
{
    public function diskName(): string
    {
        $configured = config('contratacao.anexos_disk', 'local');

        if ($configured === 's3' && blank(config('filesystems.disks.s3.bucket'))) {
            return 'local';
        }

        return $configured;
    }

    public function store(string $tenantId, string $contratacaoUuid, UploadedFile $file): string
    {
        $anexoId = (string) Str::uuid();
        $extension = $file->getClientOriginalExtension();
        $filename = $extension !== '' ? "{$anexoId}.{$extension}" : $anexoId;
        $path = "{$tenantId}/contratacao/{$contratacaoUuid}/{$filename}";

        Storage::disk($this->diskName())->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }

    public function storeApontamento(string $tenantId, string $contratacaoUuid, string $apontamentoUuid, UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $filename = $extension !== '' ? "{$apontamentoUuid}.{$extension}" : $apontamentoUuid;
        $path = "{$tenantId}/contratacao/{$contratacaoUuid}/apontamentos/{$filename}";

        Storage::disk($this->diskName())->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }

    public function get(string $storagePath): ?string
    {
        if (! Storage::disk($this->diskName())->exists($storagePath)) {
            return null;
        }

        return Storage::disk($this->diskName())->get($storagePath);
    }

    public function delete(string $storagePath): void
    {
        Storage::disk($this->diskName())->delete($storagePath);
    }
}

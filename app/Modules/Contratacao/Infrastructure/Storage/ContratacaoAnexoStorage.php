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

    public function delete(string $storagePath): void
    {
        Storage::disk($this->diskName())->delete($storagePath);
    }
}

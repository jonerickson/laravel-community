<?php

declare(strict_types=1);

namespace App\Services\Migration;

use App\Services\Migration\Contracts\EntityImporter;
use App\Services\Migration\Contracts\MigrationSource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

abstract class AbstractImporter implements EntityImporter
{
    protected const int CACHE_TTL = 60 * 60 * 24 * 7;

    public function __construct(
        protected MigrationSource $source,
    ) {
        //
    }

    protected function downloadAndStoreFile(string $baseUrl, string $sourcePath, string $storagePath, ?string $disk = 'public'): ?string
    {
        try {
            $sourcePath = ltrim(rtrim($sourcePath, '/'), '/');
            $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);

            $blob = file_get_contents("$baseUrl/$sourcePath");

            $name = Str::random(40);
            $fullStoragePath = "$storagePath/$name.$extension";
            $result = Storage::disk($disk)->put($fullStoragePath, $blob);

            if ($result) {
                return $fullStoragePath;
            }
        } catch (Throwable $e) {
            Log::error('Failed to download file', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return null;
    }
}

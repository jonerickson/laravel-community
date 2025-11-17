<?php

declare(strict_types=1);

namespace App\Services\Migration;

use App\Services\Migration\Contracts\EntityImporter;
use App\Services\Migration\Contracts\MigrationSource;
use Closure;
use DOMDocument;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

abstract class AbstractImporter implements EntityImporter
{
    protected const int CACHE_TTL = 60 * 60 * 24 * 7;

    protected ?MigrationConfig $config = null;

    public function __construct(
        protected MigrationSource $source,
    ) {
        //
    }

    public function setConfig(MigrationConfig $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function getConfig(): ?MigrationConfig
    {
        return $this->config;
    }

    protected function parseAndReplaceImagesInHtml(string $html, Closure $downloadCallback): string
    {
        if ($html === '' || $html === '0') {
            return $html;
        }

        libxml_use_internal_errors(true);

        $doc = new DOMDocument;
        $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

        $images = $doc->getElementsByTagName('img');

        foreach ($images as $img) {
            $originalSrc = $img->getAttribute('src');

            $newUrl = value($downloadCallback, $originalSrc);

            if ($newUrl) {
                $img->setAttribute('src', $newUrl);
            }
        }

        $body = $doc->getElementsByTagName('body')->item(0);
        $newHtml = '';
        foreach ($body->childNodes as $child) {
            $newHtml .= $doc->saveHTML($child);
        }

        libxml_clear_errors();

        return $newHtml;
    }

    protected function downloadAndStoreFile(string $baseUrl, string $sourcePath, string $storagePath): ?string
    {
        try {
            $sourcePath = ltrim(rtrim($sourcePath, '/'), '/');
            $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);

            $blob = file_get_contents(sprintf('%s/%s', $baseUrl, $sourcePath));

            $name = Str::random(40);
            $fullStoragePath = sprintf('%s/%s.%s', $storagePath, $name, $extension);
            $result = Storage::put($fullStoragePath, $blob, 'public');

            if ($result) {
                return $fullStoragePath;
            }
        } catch (Throwable $throwable) {
            Log::error('Failed to download file', [
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);
        }

        return null;
    }
}

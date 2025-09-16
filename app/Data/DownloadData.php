<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class DownloadData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public ?string $file_size,
        public ?string $file_type,
        public string $download_url,
        public ?string $product_name,
        public string $created_at,
    ) {
        //
    }
}

<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class DownloadData extends Data
{
    public string $id;

    public string $name;

    public ?string $description = null;

    public ?string $fileSize = null;

    public ?string $fileType = null;

    public string $downloadUrl;

    public ?string $productName = null;

    public string $createdAt;
}

<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class ImageData extends Data
{
    public int $id;

    public string $imageableType;

    public int $imageableId;

    public string $path;

    public string $url;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;
}

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
class PageData extends Data
{
    public int $id;

    public string $title;

    public string $slug;

    public ?string $description = null;

    public string $htmlContent;

    public ?string $cssContent = null;

    public ?string $jsContent = null;

    public bool $isPublished;

    public ?CarbonImmutable $publishedAt = null;

    public bool $showInNavigation;

    public ?string $navigationLabel = null;

    public int $navigationOrder;

    public UserData $author;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;
}

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
class SupportTicketCategoryData extends Data
{
    public int $id;

    public string $name;

    public string $slug;

    public ?string $description = null;

    public ?string $color = null;

    public int $order;

    public bool $isActive;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;
}

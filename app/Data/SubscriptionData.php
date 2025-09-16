<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Database\Eloquent\Collection;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class SubscriptionData extends Data
{
    public int $id;

    public string $name;

    public string $description;

    public string $slug;

    public ?string $featuredImageUrl;

    public bool $current = false;

    #[LiteralTypeScriptType('Array<string, unknown> | null')]
    public array $metadata = [];

    /** @var PriceData[] */
    public Collection $activePrices;

    /** @var ProductCategoryData[] */
    public Collection $categories;

    /** @var PolicyData[] */
    public Collection $policies;
}

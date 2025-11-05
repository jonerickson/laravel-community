<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\PriceType;
use App\Enums\SubscriptionInterval;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class PriceData extends Data
{
    public int $id;

    public string $name;

    public float $amount;

    public ?PriceType $type = null;

    public string $currency;

    public ?SubscriptionInterval $interval = null;

    public bool $isDefault;

    public bool $isActive;

    public ?string $externalPriceId = null;
}

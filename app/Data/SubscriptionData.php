<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Normalizers\Cashier\SubscriptionNormalizer;
use App\Enums\SubscriptionStatus;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class SubscriptionData extends Data
{
    public string $name;

    public ?UserData $user = null;

    public ?SubscriptionStatus $status = null;

    public ?CarbonImmutable $trialEndsAt = null;

    public ?CarbonImmutable $endsAt = null;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;

    public ?ProductData $product = null;

    public ?PriceData $price = null;

    public ?string $externalSubscriptionId = null;

    public ?string $externalProductId = null;

    public ?string $externalPriceId = null;

    public bool $doesNotExpire;

    public ?int $quantity = null;

    public static function normalizers(): array
    {
        return [
            SubscriptionNormalizer::class,
            ...config('data.normalizers'),
        ];
    }
}

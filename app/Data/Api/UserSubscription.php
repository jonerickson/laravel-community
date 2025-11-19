<?php

declare(strict_types=1);

namespace App\Data\Api;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use App\Enums\SubscriptionStatus;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(uriTemplate: 'users/{userId}/subscription/{id}', openapi: false),
    ],
    normalizationContext: [
        AbstractItemNormalizer::GROUPS => ['user'],
    ],
)]
class UserSubscription extends Data
{
    #[ApiProperty(readable: false, property: 'id', serialize: new Groups(['user']))]
    public ?int $id = null;

    #[ApiProperty(property: 'name', serialize: new Groups(['user']))]
    public ?string $name = null;

    #[ApiProperty(property: 'status', serialize: new Groups(['user']))]
    public SubscriptionStatus $status;

    #[ApiProperty(property: 'productReferenceId', serialize: new Groups(['user']))]
    public ?string $product_reference_id = null;

    #[ApiProperty(property: 'createdAt', serialize: new Groups(['user']))]
    public ?CarbonImmutable $created_at = null;

    #[ApiProperty(property: 'updatedAt', serialize: new Groups(['user']))]
    public ?CarbonImmutable $updated_at = null;

    #[ApiProperty(property: 'endsAt', serialize: new Groups(['user']))]
    public ?CarbonImmutable $ends_at = null;

    #[ApiProperty(property: 'endsAt', serialize: new Groups(['user']))]
    public ?CarbonImmutable $trial_ends_at = null;
}

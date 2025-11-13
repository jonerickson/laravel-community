<?php

declare(strict_types=1);

namespace App\Data\Api;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use Spatie\LaravelData\Data;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(uriTemplate: 'orders/{orderId}/items/{id}', openapi: false),
    ],
    normalizationContext: [
        AbstractItemNormalizer::GROUPS => ['user'],
    ],
)]
class UserOrderItemData extends Data
{
    #[ApiProperty(readable: false, property: 'id', serialize: new Groups(['user']))]
    public int $id;

    #[ApiProperty(property: 'name', serialize: new Groups(['user']))]
    public ?string $name = null;

    #[ApiProperty(property: 'description', serialize: new Groups(['user']))]
    public ?string $description = null;

    #[ApiProperty(property: 'amount', serialize: new Groups(['user']))]
    public ?float $amount = null;

    #[ApiProperty(property: 'quantity', serialize: new Groups(['user']))]
    public ?int $quantity = null;
}

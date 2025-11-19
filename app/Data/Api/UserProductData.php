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
        new Get(uriTemplate: 'products/{id}', openapi: false),
    ],
    normalizationContext: [
        AbstractItemNormalizer::GROUPS => ['user'],
    ],
)]
class UserProductData extends Data
{
    #[ApiProperty(readable: false, property: 'id', serialize: new Groups(['user']))]
    public int $id;

    #[ApiProperty(property: 'referenceId', serialize: new Groups(['user']))]
    public string $reference_id;

    #[ApiProperty(property: 'name', serialize: new Groups(['user']))]
    public ?string $name = null;
}

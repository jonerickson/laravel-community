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
        new Get(uriTemplate: 'integrations/{id}', openapi: false),
    ],
    normalizationContext: [
        AbstractItemNormalizer::GROUPS => ['user'],
    ],
)]
class UserIntegrationData extends Data
{
    #[ApiProperty(readable: false, property: 'id', serialize: new Groups(['user']))]
    public int $id;

    #[ApiProperty(property: 'provider', serialize: new Groups(['user']))]
    public ?string $provider = null;

    #[ApiProperty(property: 'providerId', serialize: new Groups(['user']))]
    public ?string $provider_id = null;

    #[ApiProperty(property: 'providerName', serialize: new Groups(['user']))]
    public ?string $provider_name = null;

    #[ApiProperty(property: 'providerEmail', serialize: new Groups(['user']))]
    public ?string $provider_email = null;

    #[ApiProperty(property: 'providerAvatar', serialize: new Groups(['user']))]
    public ?string $provider_avatar = null;
}

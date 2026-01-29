<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class IntercomData extends Data
{
    public ?string $appId = null;

    public ?string $userName = null;

    public ?string $userEmail = null;

    public ?int $userId = null;

    public ?int $createdAt = null;

    public ?string $userJwt = null;
}

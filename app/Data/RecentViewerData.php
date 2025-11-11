<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class RecentViewerData extends Data
{
    public RecentViewerUserData $user;

    public string $viewedAt;
}

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class RecentViewerUserData extends Data
{
    public int $id;

    public string $referenceId;

    public string $name;

    public ?string $avatarUrl = null;
}

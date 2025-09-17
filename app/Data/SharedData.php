<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class SharedData extends Data
{
    public AuthData $auth;

    public string $name;

    public ?int $cartCount;

    public ?FlashData $flash;

    public bool $sidebarOpen;

    #[LiteralTypeScriptType('Config & { location: string }')]
    public mixed $ziggy;
}

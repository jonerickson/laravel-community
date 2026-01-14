<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class AuthData extends Data
{
    public ?UserData $user = null;

    public bool $isAdmin;

    public bool $isImpersonating;

    public bool $mustVerifyEmail;
}

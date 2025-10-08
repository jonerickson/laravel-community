<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class CustomerData extends Data
{
    public string $id;

    public string $email;

    public ?string $name = null;

    public ?string $phone = null;

    public ?string $currency = null;

    public ?array $metadata = null;
}

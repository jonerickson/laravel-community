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
class ApiData extends Data
{
    public bool $success;

    public ?string $message = null;

    #[LiteralTypeScriptType('unknown')]
    public mixed $data;

    public ApiMetaData $meta;

    /** @var array<string, string[]>|null */
    public ?array $errors = null;
}

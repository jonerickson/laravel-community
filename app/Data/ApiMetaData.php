<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class ApiMetaData extends Data
{
    public ?CarbonImmutable $timestamp = null;

    public string $version;

    #[LiteralTypeScriptType('Array<unknown>')]
    /** @var mixed[] */
    public array $additional = [];
}

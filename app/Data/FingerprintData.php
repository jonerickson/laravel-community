<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class FingerprintData extends Data
{
    public string $fingerprintId;

    public ?CarbonImmutable $firstSeen = null;

    public ?CarbonImmutable $lastSeen = null;
}

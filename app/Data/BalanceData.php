<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Normalizers\Stripe\BalanceNormalizer;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class BalanceData extends Data
{
    public float $available;

    public float $pending;

    public string $currency;

    #[LiteralTypeScriptType('Array<string, unknown> | null')]
    public ?array $breakdown = null;

    public static function normalizers(): array
    {
        return [
            BalanceNormalizer::class,
            ...config('data.normalizers'),
        ];
    }
}

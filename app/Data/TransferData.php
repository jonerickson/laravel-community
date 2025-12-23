<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Normalizers\Stripe\TransferNormalizer;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class TransferData extends Data
{
    public string $id;

    public float $amount;

    public string $currency;

    public string $destination;

    public ?string $sourceTransaction = null;

    #[LiteralTypeScriptType('Array<string, unknown> | null')]
    public ?array $metadata = null;

    public bool $reversed;

    public ?CarbonImmutable $createdAt = null;

    public static function normalizers(): array
    {
        return [
            TransferNormalizer::class,
            ...config('data.normalizers'),
        ];
    }
}

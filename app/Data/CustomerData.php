<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Normalizers\Stripe\CustomerNormalizer;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
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

    #[LiteralTypeScriptType('Array<string, unknown> | null')]
    public ?array $metadata = null;

    public static function normalizers(): array
    {
        return [
            CustomerNormalizer::class,
            ...config('data.normalizers'),
        ];
    }
}

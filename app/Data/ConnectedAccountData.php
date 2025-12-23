<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Normalizers\Stripe\AccountNormalizer;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class ConnectedAccountData extends Data
{
    public string $id;

    public string $email;

    public ?string $businessName = null;

    public bool $chargesEnabled;

    public bool $payoutsEnabled;

    public bool $detailsSubmitted;

    #[LiteralTypeScriptType('Array<string, unknown> | null')]
    public ?array $capabilities = null;

    #[LiteralTypeScriptType('Array<string, unknown> | null')]
    public ?array $requirements = null;

    public ?string $country = null;

    public ?string $defaultCurrency = null;

    public static function normalizers(): array
    {
        return [
            AccountNormalizer::class,
            ...config('data.normalizers'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Normalizers\Stripe\PayoutNormalizer;
use App\Enums\PayoutDriver;
use App\Enums\PayoutStatus;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class PayoutData extends Data
{
    public int $id;

    public int $userId;

    public float $amount;

    public PayoutStatus $status;

    public ?PayoutDriver $paymentMethod = null;

    public ?string $externalPayoutId = null;

    public ?string $failureReason = null;

    public ?string $notes = null;

    public ?CarbonImmutable $processedAt = null;

    public ?int $processedBy = null;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;

    public static function normalizers(): array
    {
        return [
            PayoutNormalizer::class,
            ...config('data.normalizers'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class DiscountData extends Data
{
    public int $id;

    public string $code;

    public DiscountType $type;

    public DiscountValueType $discountType;

    public float $value;

    public ?int $currentBalance = null;

    public ?int $maxUses = null;

    public int $timesUsed = 0;

    public ?int $minOrderAmount = null;

    public ?CarbonImmutable $expiresAt = null;

    public ?CarbonImmutable $activatedAt = null;

    public bool $isExpired = false;

    public bool $isValid = false;

    public bool $hasBalance = false;

    public ?int $amountApplied = null;

    public ?int $balanceBefore = null;

    public ?int $balanceAfter = null;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;
}

<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\OrderStatus;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class OrderData extends Data
{
    public int $id;

    public int $userId;

    public OrderStatus $status;

    public ?string $refundReason = null;

    public ?string $refundNotes = null;

    public ?float $amount = null;

    public ?float $amountSubtotal = null;

    public ?float $amountDue = null;

    public ?float $amountPaid = null;

    public bool $isOneTime;

    public bool $isRecurring;

    public ?string $checkoutUrl = null;

    public ?string $invoiceUrl = null;

    public ?string $referenceId = null;

    public ?string $invoiceNumber = null;

    public ?string $externalCheckoutId = null;

    public ?string $externalOrderId = null;

    public ?string $externalPaymentId = null;

    public ?string $externalInvoiceId = null;

    /** @var OrderItemData[] */
    public array $items;

    /** @var DiscountData[] */
    public array $discounts = [];

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;
}

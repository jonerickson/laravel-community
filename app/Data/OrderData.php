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

    public ?int $amount;

    public ?string $invoiceUrl;

    public ?string $referenceId;

    public ?string $invoiceNumber;

    public ?string $externalCheckoutId;

    public ?string $externalOrderId;

    public ?string $externalPaymentId;

    public ?string $externalInvoiceId;

    /** @var OrderItemData[] */
    public array $items;

    public ?CarbonImmutable $createdAt;

    public ?CarbonImmutable $updatedAt;
}

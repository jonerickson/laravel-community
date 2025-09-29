<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class InvoiceData extends Data
{
    public string $externalInvoiceId;

    public int $amount;

    public ?string $invoiceUrl = null;

    public ?string $invoicePdfUrl = null;

    public ?string $externalPaymentId = null;
}

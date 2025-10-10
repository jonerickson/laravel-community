<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class PaymentSetupIntentData extends Data
{
    public string $id;

    public string $clientSecret;

    public string $status;

    public ?string $customer = null;

    /** @var string[] */
    public array $paymentMethodTypes;

    public string $usage;
}

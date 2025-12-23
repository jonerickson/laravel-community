<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Normalizers\Cashier\PaymentMethodNormalizer as CashierPaymentMethodNormalizer;
use App\Data\Normalizers\Stripe\PaymentMethodNormalizer as StripePaymentMethodNormalizer;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class PaymentMethodData extends Data
{
    public string $id;

    public string $type;

    public ?string $brand = null;

    public ?string $last4 = null;

    public ?string $expMonth = null;

    public ?string $expYear = null;

    public ?string $holderName = null;

    public ?string $holderEmail = null;

    public bool $isDefault = false;

    public function __construct()
    {
        $this->brand ??= 'Unknown';
        $this->last4 ??= '0000';
        $this->expMonth ??= '0';
        $this->expYear ??= '0';
    }

    public static function normalizers(): array
    {
        return [
            CashierPaymentMethodNormalizer::class,
            StripePaymentMethodNormalizer::class,
            ...config('data.normalizers'),
        ];
    }
}

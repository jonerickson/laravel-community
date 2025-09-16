<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[MapInputName(SnakeCaseMapper::class)]
#[TypeScript]
class PaymentMethodData extends Data
{
    public function __construct(
        public string|int $id,
        public string $type,
        public ?string $brand,
        public ?string $last4,
        public string|int|null $expMonth,
        public string|int|null $expYear,
        public ?string $holderName,
        public ?string $holderEmail,
        public bool $isDefault = false,
    ) {
        //
    }
}

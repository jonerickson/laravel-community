<?php

declare(strict_types=1);

namespace App\Data;

readonly class PaymentErrorData
{
    public function __construct(
        public string $method,
        public string $message,
        public string $exceptionClass,
        public ?string $code = null,
    ) {}
}

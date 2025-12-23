<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum PayoutDriver: string implements HasLabel
{
    case Stripe = 'stripe';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Stripe => 'Stripe Connect',
        };
    }
}

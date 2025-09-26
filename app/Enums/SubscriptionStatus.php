<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum SubscriptionStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Pending = 'pending';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function getLabel(): string
    {
        return Str::title($this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            SubscriptionStatus::Active => 'success',
            SubscriptionStatus::Pending => 'warning',
            SubscriptionStatus::Cancelled => 'danger',
            SubscriptionStatus::Refunded => 'info',
        };
    }
}

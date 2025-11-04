<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

enum OrderStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Cancelled = 'canceled';
    case Expired = 'expired';
    case Processing = 'processing';
    case RequiresAction = 'requires_action';
    case RequiresCapture = 'requires_capture';
    case RequiresConfirmation = 'requires_confirmation';
    case RequiresPaymentMethod = 'requires_payment_method';
    case Succeeded = 'succeeded';
    case Refunded = 'refunded';

    public function getLabel(): string|Htmlable|null
    {
        return Str::of($this->value)
            ->replace('_', ' ')
            ->title()
            ->__toString();
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            OrderStatus::Succeeded => 'success',
            OrderStatus::Cancelled, OrderStatus::RequiresAction, OrderStatus::Expired => 'danger',
            OrderStatus::Processing, OrderStatus::RequiresCapture => 'warning',
            default => 'info',
        };
    }

    public function canCheckout(): bool
    {
        return match ($this) {
            OrderStatus::Pending => true,
            default => false,
        };
    }

    public function canRefund(): bool
    {
        return match ($this) {
            OrderStatus::Succeeded => true,
            default => false,
        };
    }

    public function canCancel(): bool
    {
        return match ($this) {
            OrderStatus::Cancelled, OrderStatus::Refunded => false,
            default => true,
        };
    }
}

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
    case Processing = 'processing';
    case RequiresAction = 'requires_action';
    case RequiresCapture = 'requires_capture';
    case RequiresConfirmation = 'requires_confirmation';
    case RequiresPaymentMethod = 'requires_payment_method';
    case Succeeded = 'succeeded';

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
            OrderStatus::Cancelled, OrderStatus::RequiresAction => 'danger',
            ORderStatus::Processing, OrderStatus::RequiresCapture => 'warning',
            default => 'info',
        };
    }
}

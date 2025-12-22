<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

enum CommissionStatus: string implements HasColor, HasLabel
{
    case Paid = 'paid';
    case Pending = 'pending';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Returned = 'returned';

    public function getLabel(): string|Htmlable|null
    {
        return Str::of($this->value)
            ->title()
            ->toString();
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            CommissionStatus::Pending => 'info',
            CommissionStatus::Rejected, CommissionStatus::Cancelled => 'danger',
            CommissionStatus::Returned => 'warning',
            CommissionStatus::Paid => 'success',
        };
    }
}

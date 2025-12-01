<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum InventoryReservationStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Active = 'active';
    case Fulfilled = 'fulfilled';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Fulfilled => 'Fulfilled',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'warning',
            self::Fulfilled => 'success',
            self::Cancelled => 'danger',
            self::Expired => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Active => 'heroicon-o-clock',
            self::Fulfilled => 'heroicon-o-check-circle',
            self::Cancelled => 'heroicon-o-x-circle',
            self::Expired => 'heroicon-o-ban',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Active => 'Stock currently reserved',
            self::Fulfilled => 'Reservation completed',
            self::Cancelled => 'Reservation cancelled',
            self::Expired => 'Reservation expired',
        };
    }
}

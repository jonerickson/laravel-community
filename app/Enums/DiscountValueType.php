<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DiscountValueType: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';

    public function getLabel(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed Amount',
            self::Percentage => 'Percentage',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Fixed => 'success',
            self::Percentage => 'warning',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Fixed => 'heroicon-o-currency-dollar',
            self::Percentage => 'heroicon-o-percent-badge',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Fixed => 'A fixed dollar amount discount.',
            self::Percentage => 'A percentage-based discount.',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum InventoryAlertType: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case LowStock = 'low_stock';
    case OutOfStock = 'out_of_stock';
    case Reorder = 'reorder';

    public function getLabel(): string
    {
        return match ($this) {
            self::LowStock => 'Low stock',
            self::OutOfStock => 'Out of stock',
            self::Reorder => 'Reorder',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::LowStock => 'warning',
            self::OutOfStock => 'danger',
            self::Reorder => 'info',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::LowStock => 'heroicon-o-exclamation-circle',
            self::OutOfStock => 'heroicon-o-x-circle',
            self::Reorder => 'heroicon-o-arrow-path',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::LowStock => 'Quantity below reorder point',
            self::OutOfStock => 'Quantity at zero',
            self::Reorder => 'Triggered reorder notification',
        };
    }
}

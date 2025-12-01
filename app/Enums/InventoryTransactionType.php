<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum InventoryTransactionType: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Adjustment = 'adjustment';
    case Sale = 'sale';
    case Return = 'return';
    case Damage = 'damage';
    case Restock = 'restock';
    case Reserved = 'reserved';
    case Released = 'released';

    public function getLabel(): string
    {
        return match ($this) {
            self::Adjustment => 'Adjustment',
            self::Sale => 'Sale',
            self::Return => 'Return',
            self::Damage => 'Damage',
            self::Restock => 'Restock',
            self::Reserved => 'Reserved',
            self::Released => 'Released',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Adjustment => 'warning',
            self::Sale => 'success',
            self::Return => 'info',
            self::Damage => 'danger',
            self::Restock => 'primary',
            self::Reserved => 'gray',
            self::Released => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Adjustment => 'heroicon-o-adjustments-horizontal',
            self::Sale => 'heroicon-o-shopping-cart',
            self::Return => 'heroicon-o-arrow-uturn-left',
            self::Damage => 'heroicon-o-exclamation-triangle',
            self::Restock => 'heroicon-o-plus-circle',
            self::Reserved => 'heroicon-o-lock-closed',
            self::Released => 'heroicon-o-lock-open',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Adjustment => 'Manual inventory adjustment',
            self::Sale => 'Stock sold via order',
            self::Return => 'Stock returned to inventory',
            self::Damage => 'Stock marked as damaged',
            self::Restock => 'New stock added',
            self::Reserved => 'Stock reserved for pending order',
            self::Released => 'Stock reservation released',
        };
    }
}

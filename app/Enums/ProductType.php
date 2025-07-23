<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ProductType: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Product = 'product';
    case Subscription = 'subscription';

    public function getLabel(): string
    {
        return match ($this) {
            self::Product => 'Product',
            self::Subscription => 'Subscription',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Product => 'success',
            self::Subscription => 'info',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Product => 'heroicon-o-shopping-bag',
            self::Subscription => 'heroicon-o-arrow-path',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Product => 'A one-time purchased product.',
            self::Subscription => 'A product that will be billed on a recurring schedule.',
        };
    }
}

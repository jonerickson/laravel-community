<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum PriceType: string implements HasDescription, HasLabel
{
    case OneTime = 'one_time';
    case Recurring = 'recurring';

    public function getLabel(): string
    {
        return Str::of($this->value)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function getDescription(): string
    {
        return match ($this) {
            PriceType::Recurring => 'Charge the customer on a recurring schedule.',
            PriceType::OneTime => 'Charge a one-time fee.'
        };
    }
}

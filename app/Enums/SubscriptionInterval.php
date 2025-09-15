<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

enum SubscriptionInterval: string implements HasLabel
{
    case Daily = 'day';
    case Weekly = 'week';
    case Monthly = 'month';
    case Yearly = 'year';

    public function getLabel(): string|Htmlable|null
    {
        return Str::title($this->value);
    }
}

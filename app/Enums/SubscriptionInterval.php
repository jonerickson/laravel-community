<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum SubscriptionInterval: string implements HasLabel
{
    case Monthly = 'month';
    case Yearly = 'year';

    public function getLabel(): string|Htmlable|null
    {
        return Str::of($this->value)
            ->title()
            ->append('ly')
            ->toString();
    }
}

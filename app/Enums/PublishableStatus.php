<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum PublishableStatus: string implements HasColor, HasLabel
{
    case Published = 'published';
    case Draft = 'draft';

    public function getLabel(): string
    {
        return Str::title($this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Published => 'success',
            self::Draft => 'danger',
        };
    }
}

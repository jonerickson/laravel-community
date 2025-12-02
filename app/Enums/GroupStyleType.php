<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum GroupStyleType: string implements HasLabel
{
    case Solid = 'solid';
    case Gradient = 'gradient';
    case Holographic = 'holographic';

    public function getLabel(): string
    {
        return match ($this) {
            self::Solid => 'Solid',
            self::Gradient => 'Gradient',
            self::Holographic => 'Holographic',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SupportTicketPriority: string implements HasColor, HasLabel
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public static function fromNumeric(int $value): self
    {
        return match ($value) {
            1 => self::Low,
            2 => self::Medium,
            3 => self::High,
            4 => self::Critical,
            default => self::Medium,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
            self::Critical => 'Critical',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Low => 'success',
            self::Medium => 'info',
            self::High => 'warning',
            self::Critical => 'danger',
        };
    }

    public function numericValue(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
            self::Critical => 4,
        };
    }
}

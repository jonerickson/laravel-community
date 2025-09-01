<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SupportTicketStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'danger',
            self::Open => 'info',
            self::InProgress => 'warning',
            self::Resolved, self::Closed => 'success',
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::New => in_array($status, [self::Open, self::InProgress, self::Closed, self::Resolved]),
            self::Open => in_array($status, [self::InProgress, self::Resolved, self::Closed]),
            self::InProgress => in_array($status, [self::Open, self::Resolved, self::Closed]),
            self::Resolved => in_array($status, [self::Open, self::Closed]),
            self::Closed => $status === self::Open,
        };
    }

    public function isActive(): bool
    {
        return ! in_array($this, [self::Resolved, self::Closed]);
    }
}

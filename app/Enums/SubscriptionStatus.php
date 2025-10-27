<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum SubscriptionStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Pending = 'pending';
    case Cancelled = 'canceled';
    case Refunded = 'refunded';
    case GradePeriod = 'grade_period';
    case Trialing = 'trialing';
    case PastDue = 'past_due';
    case Unpaid = 'unpaid';
    case Incomplete = 'incomplete';
    case IncompleteExpired = 'incomplete_expired';

    public function getLabel(): string
    {
        return Str::of($this->value)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function getColor(): string
    {
        return match ($this) {
            SubscriptionStatus::Active => 'success',
            SubscriptionStatus::Pending, SubscriptionStatus::Unpaid => 'warning',
            SubscriptionStatus::Cancelled, SubscriptionStatus::PastDue, SubscriptionStatus::Incomplete, SubscriptionStatus::IncompleteExpired => 'danger',
            SubscriptionStatus::Refunded, SubscriptionStatus::GradePeriod, SubscriptionStatus::Trialing => 'info',
        };
    }

    public function canCancel(): bool
    {
        return match ($this) {
            SubscriptionStatus::Cancelled, SubscriptionStatus::Refunded => false,
            default => true,
        };
    }

    public function canContinue(): bool
    {
        return match ($this) {
            SubscriptionStatus::Trialing, SubscriptionStatus::Active => true,
            default => false,
        };
    }
}

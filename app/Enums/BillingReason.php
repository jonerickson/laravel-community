<?php

declare(strict_types=1);

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

enum BillingReason: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Manual = 'manual';
    case SubscriptionCreate = 'subscription_create';
    case SubscriptionCycle = 'subscription_cycle';
    case SubscriptionThreshold = 'subscription_threshold';

    case SubscriptionUpdate = 'subscription_update';

    public function getLabel(): string|Htmlable|null
    {
        return Str::of($this->value)
            ->replace('_', ' ')
            ->title()
            ->__toString();
    }

    public function getDescription(): string
    {
        return match ($this) {
            BillingReason::Manual => 'Unrelated to a subscription, for example, created via the invoice editor.',
            BillingReason::SubscriptionCreate => 'A new subscription was created.',
            BillingReason::SubscriptionCycle => 'A subscription advanced into a new period.',
            BillingReason::SubscriptionThreshold => 'A subscription reached a billing threshold.',
            BillingReason::SubscriptionUpdate => 'A subscription was updated.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            BillingReason::Manual => 'gray',
            BillingReason::SubscriptionCreate => 'success',
            BillingReason::SubscriptionUpdate, BillingReason::SubscriptionCycle => 'info',
            BillingReason::SubscriptionThreshold => 'warning',
        };
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            BillingReason::Manual => Heroicon::OutlinedCreditCard,
            BillingReason::SubscriptionCreate => Heroicon::OutlinedCalendarDays,
            BillingReason::SubscriptionCycle => Heroicon::OutlinedArrowPath,
            BillingReason::SubscriptionThreshold => Heroicon::OutlinedExclamationTriangle,
            BillingReason::SubscriptionUpdate => Heroicon::OutlinedArrowsRightLeft,
        };
    }
}

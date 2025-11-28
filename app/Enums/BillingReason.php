<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;

enum BillingReason: string implements HasDescription
{
    case Manual = 'manual';
    case SubscriptionCreate = 'subscription_create';
    case SubscriptionCycle = 'subscription_cycle';
    case SubscriptionThreshold = 'subscription_threshold';

    case SubscriptionUpdate = 'subscription_update';

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
}

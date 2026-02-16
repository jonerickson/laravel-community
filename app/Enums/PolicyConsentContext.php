<?php

declare(strict_types=1);

namespace App\Enums;

enum PolicyConsentContext: string
{
    case Onboarding = 'onboarding';
    case Checkout = 'checkout';
    case Subscription = 'subscription';
    case Acceptance = 'acceptance';

    public function label(): string
    {
        return match ($this) {
            self::Onboarding => 'Onboarding',
            self::Checkout => 'Checkout',
            self::Subscription => 'Subscription',
            self::Acceptance => 'Acceptance',
        };
    }
}

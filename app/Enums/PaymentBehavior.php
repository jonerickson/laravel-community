<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

enum PaymentBehavior: string implements HasDescription, HasLabel
{
    case AllowIncomplete = 'allow_incomplete';
    case DefaultIncomplete = 'default_incomplete';
    case ErrorIfIncomplete = 'error_if_incomplete';
    case PendingIfIncomplete = 'pending_if_incomplete';

    public function getLabel(): string
    {
        return Str::of($this->value)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            PaymentBehavior::AllowIncomplete => 'The payment processor will attempt to pay the invoice immediately using the default payment method. If payment fails, the subscription remains active and moves to incomplete.',
            PaymentBehavior::DefaultIncomplete => 'The payment processor creates the invoice but doesn’t try to pay it. Subscription becomes incomplete until payment is confirmed manually. If the invoice is not paid within 23 hours, the subscription will transition to incomplete expired.',
            PaymentBehavior::ErrorIfIncomplete => 'If the payment processor can’t immediately pay the invoice, the request fails (no subscription or change is saved).',
            PaymentBehavior::PendingIfIncomplete => 'Similar to Default Incomplete, but used when confirming payment asynchronously.',
        };
    }
}

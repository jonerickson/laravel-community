<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

enum DisputeReason: string implements HasLabel
{
    case BankCannotProcess = 'bank_cannot_process';
    case CreditNotProcessed = 'credit_not_processed';
    case CustomerInitiated = 'customer_initiated';
    case DebitNotAuthorized = 'debit_not_authorized';
    case Duplicate = 'duplicate';
    case Fraudulent = 'fraudulent';
    case General = 'general';
    case IncorrectAccountDetails = 'incorrect_account_details';
    case InsufficientFunds = 'insufficient_funds';
    case ProductNotReceived = 'product_not_received';
    case ProductUnacceptable = 'product_unacceptable';
    case SubscriptionCanceled = 'subscription_canceled';
    case Unrecognized = 'unrecognized';

    public function getLabel(): string|Htmlable|null
    {
        return Str::of($this->value)
            ->replace('_', ' ')
            ->title()
            ->__toString();
    }
}

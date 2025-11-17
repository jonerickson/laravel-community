<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum ProrationBehavior: string implements HasDescription, HasLabel
{
    case CreateProrations = 'create_prorations';
    case AlwaysInvoice = 'always_invoice';
    case None = 'none';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            ProrationBehavior::CreateProrations => 'Create Prorations',
            ProrationBehavior::AlwaysInvoice => 'Invoice Immediately with Prorations',
            ProrationBehavior::None => 'Do Not Prorate',
        };
    }

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            ProrationBehavior::CreateProrations => "Update the user's subscription to the new price and wait until the next billing cycle to invoice the customer with the proration adjustments.",
            ProrationBehavior::AlwaysInvoice => "Update the user's subscription to the new price, automatically invoice the customer for the proration adjustments, and attempt to collect payment.",
            ProrationBehavior::None => 'The next billing cycle will contain the new price without any proration adjustments.',
        };
    }
}

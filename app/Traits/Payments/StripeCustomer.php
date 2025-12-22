<?php

declare(strict_types=1);

namespace App\Traits\Payments;

use App\Models\User;
use Laravel\Cashier\Billable;

/**
 * @mixin User
 */
trait StripeCustomer
{
    use Billable;

    public function stripeName(): string
    {
        return $this->name;
    }

    public function stripeEmail(): string
    {
        return $this->email;
    }

    public function stripeAddress(): array
    {
        if (blank($this->billing_address)) {
            return [];
        }

        return [
            'city' => $this->billing_city,
            'country' => $this->billing_country,
            'line1' => $this->billing_address,
            'line2' => $this->billing_address_line_2,
            'postal_code' => $this->billing_postal_code,
            'state' => $this->billing_state,
        ];
    }
}

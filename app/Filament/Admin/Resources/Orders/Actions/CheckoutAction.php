<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Actions;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class CheckoutAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Checkout');
        $this->color('gray');
        $this->icon(Heroicon::OutlinedShoppingCart);
        $this->visible(fn (Order $record) => $record->is_one_time && $record->status->canCheckout());
        $this->url(fn (Order $record) => $record->checkout_url, shouldOpenInNewTab: true);
    }

    public static function getDefaultName(): ?string
    {
        return 'checkout';
    }
}

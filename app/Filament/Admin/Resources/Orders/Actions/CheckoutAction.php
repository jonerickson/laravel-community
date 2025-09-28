<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Actions;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Override;

class CheckoutAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Checkout');
        $this->color('gray');
        $this->icon(Heroicon::OutlinedShoppingCart);
        $this->visible(fn (Order $record): bool => $record->is_one_time && $record->status->canCheckout());
        $this->url(fn (Order $record) => $record->checkout_url, shouldOpenInNewTab: true);
    }

    public static function getDefaultName(): ?string
    {
        return 'checkout';
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Prices\Actions;

use App\Managers\PaymentManager;
use App\Models\Product;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Override;

class SyncExternalPriceAction extends Action
{
    protected Closure|Product|null $product = null;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->color('gray');
        $this->label('Sync prices');
        $this->requiresConfirmation();
        $this->visible(fn (): bool => filled($this->getProduct()->external_product_id) && config('payment.default'));
        $this->modalHeading('Sync Product Prices');
        $this->modalIcon(Heroicon::OutlinedArrowPath);
        $this->modalDescription('This will remove any existing prices for this product locally and pull in the latest product prices from your payment processor.');
        $this->modalSubmitActionLabel('Sync');
        $this->successNotificationTitle('The prices have been successfully synced.');
        $this->failureNotificationTitle('There was an error syncing the prices. Please try again.');
        $this->schema([
            Checkbox::make('import_nonactive')
                ->label('Import Archived Prices')
                ->default(false)
                ->helperText('Select to also import prices that have been archived and are not currently active.'),
        ]);
        $this->action(function (Action $action, array $data): void {
            $product = $this->getProduct();

            $result = DB::transaction(function () use ($product, $data): true {
                $paymentManager = app(PaymentManager::class);

                $filters = [
                    'active' => ! data_get($data, 'import_nonactive', false),
                ];

                if ($data['import_nonactive'] ?? false) {
                    $filters = [];
                }

                if ($prices = $paymentManager->listPrices($product, $filters)) {
                    $product->prices()->delete();
                    $product->prices()->saveMany($prices);
                }

                return true;
            });

            if ($result) {
                $action->success();
            } else {
                $action->failure();
            }
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'sync_external_price';
    }

    public function product(Closure|Product|null $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getProduct(): Closure|Product|null
    {
        return $this->evaluate($this->product);
    }
}

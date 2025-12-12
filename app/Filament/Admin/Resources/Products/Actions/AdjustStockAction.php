<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Products\Actions;

use App\Enums\InventoryTransactionType;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Override;

class AdjustStockAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Adjust stock');
        $this->color('warning');
        $this->icon(Heroicon::OutlinedAdjustmentsHorizontal);
        $this->visible(fn (Product|InventoryItem|null $record): bool => $record instanceof InventoryItem);
        $this->successNotificationTitle('The stock has been successfully adjusted.');
        $this->modalDescription('Adjust the current stock on hand.');
        $this->schema([
            TextInput::make('quantity')
                ->required()
                ->numeric()
                ->helperText('Use negative numbers to decrease stock.'),
            Textarea::make('reason')
                ->required()
                ->rows(3),
        ]);
        $this->action(function (InventoryItem $record, array $data, Action $action): void {
            $service = app(InventoryService::class);
            $service->adjustStock(
                $record,
                (int) $data['quantity'],
                InventoryTransactionType::Adjustment,
                $data['reason'],
            );
            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'adjust';
    }
}

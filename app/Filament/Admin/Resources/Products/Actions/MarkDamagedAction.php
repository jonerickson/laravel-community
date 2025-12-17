<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Products\Actions;

use App\Models\InventoryItem;
use App\Models\Product;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Override;

class MarkDamagedAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Mark damaged');
        $this->color('danger');
        $this->icon(Heroicon::OutlinedExclamationTriangle);
        $this->visible(fn (Product|InventoryItem|null $record): bool => $record instanceof InventoryItem);
        $this->successNotificationTitle('The inventory was marked as damaged.');
        $this->modalDescription('Mark a quantity of product as damaged. This will update the quantity on hand.');
        $this->schema([
            TextInput::make('quantity')
                ->required()
                ->numeric()
                ->minValue(1)
                ->maxValue(fn (InventoryItem $record) => $record->quantity_available ?? 0),
            Textarea::make('reason')
                ->required()
                ->rows(3),
        ]);
        $this->action(function (InventoryItem $record, array $data, Action $action): void {
            $service = app(InventoryService::class);
            $service->markDamaged($record, (int) $data['quantity'], $data['reason']);

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'mark_damaged';
    }
}

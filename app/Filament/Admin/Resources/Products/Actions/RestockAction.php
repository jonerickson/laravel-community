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

class RestockAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Restock');
        $this->color('success');
        $this->icon(Heroicon::OutlinedPlusCircle);
        $this->visible(fn (Product|InventoryItem|null $record): bool => $record instanceof InventoryItem);
        $this->successNotificationTitle('The inventory was successfully restocked.');
        $this->modalDescription('Restock and add additional quantity available for the product.');
        $this->schema([
            TextInput::make('quantity')
                ->required()
                ->numeric()
                ->minValue(1)
                ->default(fn (InventoryItem $record) => $record->reorder_quantity),
            Textarea::make('notes')
                ->rows(3),
        ]);
        $this->action(function (InventoryItem $record, array $data, Action $action): void {
            $service = app(InventoryService::class);
            $service->restock($record, (int) $data['quantity'], $data['notes'] ?? null);

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'restock';
    }
}

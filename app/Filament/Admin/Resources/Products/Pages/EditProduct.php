<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('link')
                ->color('gray')
                ->label('Link external ID')
                ->visible(fn (Product $record): bool => blank($record->external_product_id))
                ->modalDescription('Provide an external product ID links this product with a product you have already created in your payment processor.')
                ->schema([
                    TextInput::make('external_product_id')
                        ->label('External Product ID')
                        ->helperText('Provide the external product ID.')
                        ->required()
                        ->maxLength(255),
                ])
                ->successNotificationTitle('The product has been successfully linked.')
                ->action(function (array $data, Product $record, Action $action): void {
                    $record->forceFill($data)->save();
                    $action->success();
                }),
            Action::make('unlink')
                ->label('Unlink external ID')
                ->color('gray')
                ->visible(fn (Product $record): bool => filled($record->external_product_id))
                ->requiresConfirmation()
                ->modalSubmitActionLabel('Unlink')
                ->modalHeading('Unlink External Product ID')
                ->modalDescription('Are you sure you want to unlink this product?')
                ->successNotificationTitle('The product has been successfully unlinked.')
                ->action(function (array $data, Product $record, Action $action): void {
                    $record->forceFill(['external_product_id' => null])->save();
                    $action->success();
                }),
            DeleteAction::make(),

        ];
    }
}

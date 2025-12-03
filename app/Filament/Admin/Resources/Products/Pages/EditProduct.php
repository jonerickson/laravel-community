<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\Actions\CreateExternalProductAction;
use App\Filament\Admin\Resources\Products\Actions\DeleteExternalProductAction;
use App\Filament\Admin\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Override;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    #[Override]
    public function getSubheading(): string|Htmlable|null
    {
        return Str::of($this->record?->description)->stripTags()->limit()->toString();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('link')
                ->color('gray')
                ->label('Link existing external product')
                ->visible(fn (Product $record): bool => blank($record->external_product_id) && config('payment.default'))
                ->modalDescription('Provide an external product ID to link this product with a product you have already created in your payment processor.')
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
                ->visible(fn (Product $record): bool => filled($record->external_product_id) && config('payment.default'))
                ->requiresConfirmation()
                ->modalSubmitActionLabel('Unlink')
                ->modalHeading('Unlink External Product ID')
                ->modalDescription('Are you sure you want to unlink this product?')
                ->successNotificationTitle('The product has been successfully unlinked.')
                ->action(function (array $data, Product $record, Action $action): void {
                    $record->forceFill(['external_product_id' => null])->save();
                    $action->success();
                }),
            CreateExternalProductAction::make(),
            DeleteExternalProductAction::make(),
            DeleteAction::make(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Enums\Role;
use App\Filament\Admin\Resources\Orders\Actions\CancelAction;
use App\Filament\Admin\Resources\Orders\Actions\CheckoutAction;
use App\Filament\Admin\Resources\Orders\Actions\RefundAction;
use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CheckoutAction::make(),
            RefundAction::make(),
            Action::make('generateDisputePackage')
                ->label('Generate dispute package')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('warning')
                ->url(fn (Order $record): string => route('admin.dispute-evidence.download', $record))
                ->openUrlInNewTab()
                ->visible(fn (): bool => Auth::user()->hasRole(Role::Administrator)),
            EditAction::make(),
            CancelAction::make(),
            DeleteAction::make(),
        ];
    }
}

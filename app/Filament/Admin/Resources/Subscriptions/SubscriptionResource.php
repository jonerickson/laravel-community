<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Subscriptions;

use App\Filament\Admin\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Filament\Admin\Resources\Subscriptions\Tables\SubscriptionsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;

class SubscriptionResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?string $recordTitleAttribute = 'type';

    #[Override]
    public static function table(Table $table): Table
    {
        return SubscriptionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptions::route('/'),
        ];
    }
}

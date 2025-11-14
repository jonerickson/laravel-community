<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Discounts;

use App\Filament\Admin\Resources\Discounts\Pages\CreateDiscount;
use App\Filament\Admin\Resources\Discounts\Pages\EditDiscount;
use App\Filament\Admin\Resources\Discounts\Pages\ListDiscounts;
use App\Filament\Admin\Resources\Discounts\Schemas\DiscountForm;
use App\Filament\Admin\Resources\Discounts\Tables\DiscountsTable;
use App\Models\Discount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Override;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return DiscountForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return DiscountsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiscounts::route('/'),
            'create' => CreateDiscount::route('/create'),
            'edit' => EditDiscount::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $count = static::getNavigationBadge();

        return match (true) {
            $count > 50 => 'warning',
            $count > 0 => 'success',
            default => 'gray',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Marketplace\Resources\Products;

use App\Enums\ProductApprovalStatus;
use App\Filament\Marketplace\Resources\Products\Pages\ManageProducts;
use App\Filament\Marketplace\Resources\Products\Schemas\ProductForm;
use App\Filament\Marketplace\Resources\Products\Schemas\ProductInfolist;
use App\Filament\Marketplace\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Override;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $navigationLabel = 'My Products';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    #[Override]
    public static function infolist(Schema $schema): Schema
    {
        return ProductInfolist::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('seller_id', Auth::id());
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProducts::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return Number::format(static::getEloquentQuery()->count());
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $pendingCount = static::getEloquentQuery()
            ->where('approval_status', ProductApprovalStatus::Pending)
            ->count();

        return match (true) {
            $pendingCount > 0 => 'warning',
            default => 'success',
        };
    }
}

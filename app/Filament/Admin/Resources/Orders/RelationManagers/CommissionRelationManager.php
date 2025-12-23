<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\RelationManagers;

use App\Enums\CommissionStatus;
use App\Models\Order;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Override;

class CommissionRelationManager extends RelationManager
{
    protected static string $relationship = 'commissions';

    protected static ?string $title = 'Commissions';

    protected static ?string $badgeColor = 'gray';

    #[Override]
    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        /** @var Order $ownerRecord */
        return (string) $ownerRecord->commissions->count();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Select::make('seller_id')
                    ->helperText('The seller must have payouts enabled to be selected.')
                    ->relationship('seller', 'name', modifyQueryUsing: fn (Builder|User $query): Builder => $query->where('payouts_enabled', true))
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->prefix('$')
                    ->suffix('USD')
                    ->step(0.01)
                    ->minValue(0),
                Select::make('status')
                    ->required()
                    ->options(CommissionStatus::class)
                    ->default(CommissionStatus::Pending)
                    ->helperText('A commission must be in the pending status to payout.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->heading('Commissions')
            ->description('The commissions that were earned on this order.')
            ->emptyStateHeading('No commissions')
            ->emptyStateDescription('No commissions were earned on this order.')
            ->columns([
                TextColumn::make('seller.name')
                    ->sortable()
                    ->label('Seller')
                    ->searchable(),
                TextColumn::make('amount')
                    ->sortable()
                    ->money()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Commission Status')
                    ->sortable()
                    ->badge(),
                TextColumn::make('payout.status')
                    ->label('Payout Status')
                    ->placeholder('No Payout Initialized')
                    ->sortable()
                    ->badge(),
                TextColumn::make('payout.created_at')
                    ->label('Payout Processed')
                    ->placeholder('No Payout Initialized')
                    ->since()
                    ->sortable()
                    ->dateTimeTooltip(),
                TextColumn::make('payout.author.name')
                    ->label('Payout Processed By')
                    ->placeholder('No Payout Initialized')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->modalDescription('Add a new commission entry to this order.'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalDescription('Edit a commission entry on this order.'),
                DeleteAction::make(),
            ]);
    }
}

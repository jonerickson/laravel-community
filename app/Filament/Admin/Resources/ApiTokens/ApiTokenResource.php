<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApiTokens;

use App\Enums\Role;
use App\Filament\Admin\Resources\ApiTokens\Pages\ListApiTokens;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Passport\Token;
use Override;
use UnitEnum;

class ApiTokenResource extends Resource
{
    protected static ?string $model = Token::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|UnitEnum|null $navigationGroup = 'Integrations';

    protected static ?int $navigationSort = -4;

    protected static ?string $label = 'API Key';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('tokenable_id')
                    ->visibleOn('create')
                    ->disabledOn('edit')
                    ->options(User::query()->role(Role::Administrator)->orderBy('name')->pluck('name', 'id'))
                    ->preload()
                    ->label('User')
                    ->searchable()
                    ->required(),
                DateTimePicker::make('expires_at')
                    ->visibleOn('create')
                    ->label('Expires')
                    ->helperText('Leave empty to automatically set expiration date to 1 year.'),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateDescription('There are no API keys available yet. Create your first one to get started.')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->placeholder('Does Not Expire')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state): string => $state && $state->isPast() ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state) => $state ? $state->format('M j, Y H:i') : 'Never'),
                IconColumn::make('revoked')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tokenable_id')
                    ->label('User')
                    ->relationship('tokenable', 'name')
                    ->searchable(),
                TernaryFilter::make('expired')
                    ->label('Token Status')
                    ->trueLabel('Expired tokens only')
                    ->falseLabel('Active tokens only')
                    ->queries(
                        true: fn (Builder $query) => $query->where('expires_at', '<', now()),
                        false: fn (Builder $query) => $query->where(function ($q): void {
                            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                        }),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->label('Revoke')
                    ->modalHeading('Revoke API Token')
                    ->modalDescription('Are you sure you want to revoke this API token? This action cannot be undone.')
                    ->modalSubmitActionLabel('Revoke Token'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Revoke Selected')
                        ->modalHeading('Revoke Selected API Tokens')
                        ->modalDescription('Are you sure you want to revoke the selected API tokens? This action cannot be undone.')
                        ->modalSubmitActionLabel('Revoke Tokens'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApiTokens::route('/'),
        ];
    }
}

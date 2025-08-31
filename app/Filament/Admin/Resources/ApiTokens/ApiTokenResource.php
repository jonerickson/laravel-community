<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApiTokens;

use App\Filament\Admin\Resources\ApiTokens\Pages\CreateApiToken;
use App\Filament\Admin\Resources\ApiTokens\Pages\EditApiToken;
use App\Filament\Admin\Resources\ApiTokens\Pages\ListApiTokens;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Sanctum\PersonalAccessToken;
use UnitEnum;

class ApiTokenResource extends Resource
{
    protected static ?string $model = PersonalAccessToken::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $label = 'API Key';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('API Key Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('tokenable_id')
                            ->label('User')
                            ->relationship('tokenable', 'name')
                            ->searchable()
                            ->required(),
                        Textarea::make('abilities')
                            ->label('Abilities (JSON)')
                            ->helperText('Enter abilities as JSON array, e.g., ["*"] for all abilities')
                            ->default('["*"]')
                            ->required(),
                        DateTimePicker::make('expires_at')
                            ->label('Expires At')
                            ->helperText('Leave empty for tokens that never expire'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateDescription('There are no API keys available yet. Create your first one to get started.')
            ->columns([
                TextColumn::make('tokenable.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tokenable.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('token')
                    ->label('Token (First 10 chars)')
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 10).'...')
                    ->copyable()
                    ->copyMessage('Token prefix copied')
                    ->toggleable(),
                TextColumn::make('abilities')
                    ->formatStateUsing(fn (array $state): string => implode(', ', $state))
                    ->limit(30)
                    ->tooltip(fn (array $state): string => implode(', ', $state)),
                TextColumn::make('last_used_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state): string => $state && $state->isPast() ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state) => $state ? $state->format('M j, Y H:i') : 'Never'),
                TextColumn::make('created_at')
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
            'create' => CreateApiToken::route('/create'),
            'edit' => EditApiToken::route('/{record}/edit'),
        ];
    }
}

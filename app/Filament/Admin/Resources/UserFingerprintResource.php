<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserFingerprintResource\Pages;
use App\Models\UserFingerprint;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UserFingerprintResource extends Resource
{
    protected static ?string $model = UserFingerprint::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationGroup = 'Users';

    protected static ?string $label = 'Fingerprints';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('fingerprint_id')
                    ->label('Fingerprint ID')
                    ->required()
                    ->disabled(),
                Forms\Components\Select::make('user_id')
                    ->label('Associated User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled(),
                Forms\Components\TextInput::make('ip_address')
                    ->label('IP Address')
                    ->disabled(),
                Forms\Components\TextInput::make('user_agent')
                    ->label('User Agent')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('first_seen_at')
                    ->label('First Seen')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('last_seen_at')
                    ->label('Last Seen')
                    ->disabled(),
                Forms\Components\Toggle::make('is_banned')
                    ->label('Banned')
                    ->reactive(),
                Forms\Components\DateTimePicker::make('banned_at')
                    ->label('Banned At')
                    ->visible(fn (Forms\Get $get) => $get('is_banned'))
                    ->disabled(),
                Forms\Components\Textarea::make('ban_reason')
                    ->label('Ban Reason')
                    ->visible(fn (Forms\Get $get) => $get('is_banned'))
                    ->maxLength(1000),
                Forms\Components\Select::make('banned_by')
                    ->label('Banned By')
                    ->relationship('bannedBy', 'name')
                    ->visible(fn (Forms\Get $get) => $get('is_banned'))
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fingerprint_id')
                    ->label('Fingerprint ID')
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->fingerprint_id)
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Guest'),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\IconColumn::make('is_banned')
                    ->label('Banned')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),
                Tables\Columns\TextColumn::make('banned_at')
                    ->label('Banned At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ban_reason')
                    ->label('Ban Reason')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->ban_reason)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('bannedBy.name')
                    ->label('Banned By')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('first_seen_at')
                    ->label('First Seen')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('last_seen_at')
                    ->label('Last Seen')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->user_agent)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_banned')
                    ->label('Ban Status')
                    ->trueLabel('Banned devices only')
                    ->falseLabel('Active devices only')
                    ->native(false),
                Tables\Filters\Filter::make('has_user')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('user_id'))
                    ->label('Associated with user'),
                Tables\Filters\Filter::make('recent_activity')
                    ->query(fn (Builder $query): Builder => $query->where('last_seen_at', '>=', now()->subDays(7)))
                    ->label('Active in last 7 days'),
            ])
            ->actions([
                Tables\Actions\Action::make('ban')
                    ->label('Ban Device')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (UserFingerprint $record): bool => ! $record->is_banned)
                    ->form([
                        Forms\Components\Textarea::make('ban_reason')
                            ->label('Ban Reason')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (UserFingerprint $record, array $data): void {
                        $record->banFingerprint($data['ban_reason'], Auth::user());
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Ban Device')
                    ->modalDescription('Are you sure you want to ban this device? All users accessing from this device will be blocked.')
                    ->modalSubmitActionLabel('Ban Device'),
                Tables\Actions\Action::make('unban')
                    ->label('Unban Device')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (UserFingerprint $record): bool => $record->is_banned)
                    ->action(fn (UserFingerprint $record) => $record->unbanFingerprint())
                    ->requiresConfirmation()
                    ->modalHeading('Unban Device')
                    ->modalDescription('Are you sure you want to unban this device?')
                    ->modalSubmitActionLabel('Unban Device'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulk_ban')
                        ->label('Ban Selected Devices')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('ban_reason')
                                ->label('Ban Reason')
                                ->required()
                                ->maxLength(1000),
                        ])
                        ->action(function (array $data, $records): void {
                            foreach ($records as $record) {
                                if (! $record->is_banned) {
                                    $record->banFingerprint($data['ban_reason'], Auth::user());
                                }
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Ban Selected Devices')
                        ->modalDescription('Are you sure you want to ban the selected devices?'),
                ]),
            ])
            ->defaultSort('last_seen_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserFingerprints::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }
}

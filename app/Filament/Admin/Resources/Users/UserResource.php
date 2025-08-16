<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\RelationManagers\FingerprintsRelationManager;
use App\Models\User;
use App\Models\UserFingerprint;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'Users';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->columns()
                    ->schema([
                        TextInput::make('name')
                            ->columnSpanFull()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        DateTimePicker::make('email_verified_at')
                            ->label('Email Verified'),
                        Select::make('groups')
                            ->relationship('groups', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('groups.name')
                    ->badge(),
                IconColumn::make('is_banned')
                    ->label('Banned')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),
                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                TextColumn::make('banned_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ban_reason')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('bannedBy.name')
                    ->label('Banned By')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fingerprints_count')
                    ->label('Devices')
                    ->counts('fingerprints')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_banned')
                    ->label('Banned Status')
                    ->trueLabel('Banned users only')
                    ->falseLabel('Active users only')
                    ->native(false),
                SelectFilter::make('groups')
                    ->relationship('groups', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ])
            ->groups(['groups.name'])
            ->recordActions([
                EditAction::make(),
                Action::make('ban')
                    ->label('Ban User')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (User $record): bool => ! $record->is_banned && $record->fingerprints->count())
                    ->schema([
                        Textarea::make('ban_reason')
                            ->label('Ban Reason')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(fn (User $record, array $data) => $record->fingerprints()->each(fn (UserFingerprint $fingerprint) => $fingerprint->banFingerprint($data['ban_reason'])))
                    ->requiresConfirmation()
                    ->modalHeading('Ban User')
                    ->modalDescription('Are you sure you want to ban this user? They will be immediately logged out and unable to access the site.')
                    ->modalSubmitActionLabel('Ban User'),
                Action::make('unban')
                    ->label('Unban User')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->is_banned && $record->fingerprints->count())
                    ->action(fn (User $record) => $record->fingerprints()->each(fn (UserFingerprint $fingerprint) => $fingerprint->unbanFingerprint()))
                    ->requiresConfirmation()
                    ->modalHeading('Unban User')
                    ->modalDescription('Are you sure you want to unban this user?')
                    ->modalSubmitActionLabel('Unban User'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('bulk_ban')
                        ->label('Ban Selected Users')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Textarea::make('ban_reason')
                                ->label('Ban Reason')
                                ->required()
                                ->maxLength(1000),
                        ])
                        ->action(function (array $data, $records): void {
                            foreach ($records as $record) {
                                if (! $record->is_banned) {
                                    $record->banUser($data['ban_reason'], Auth::user());
                                }
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Ban Selected Users')
                        ->modalDescription('Are you sure you want to ban the selected users?'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            FingerprintsRelationManager::make(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}

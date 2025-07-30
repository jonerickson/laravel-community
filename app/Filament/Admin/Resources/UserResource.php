<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\Toggle::make('is_banned')
                    ->label('Banned')
                    ->reactive(),
                Forms\Components\DateTimePicker::make('banned_at')
                    ->label('Banned At')
                    ->visible(fn (Forms\Get $get) => $get('is_banned')),
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
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\IconColumn::make('is_banned')
                    ->label('Banned')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),
                Tables\Columns\TextColumn::make('banned_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ban_reason')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('bannedBy.name')
                    ->label('Banned By')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('fingerprints_count')
                    ->label('Devices')
                    ->counts('fingerprints')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_banned')
                    ->label('Banned Status')
                    ->trueLabel('Banned users only')
                    ->falseLabel('Active users only')
                    ->native(false),
                Tables\Filters\Filter::make('email_verified')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at'))
                    ->label('Email verified only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('ban')
                    ->label('Ban User')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (User $record): bool => ! $record->is_banned)
                    ->form([
                        Forms\Components\Textarea::make('ban_reason')
                            ->label('Ban Reason')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->banUser($data['ban_reason'], Auth::user());
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Ban User')
                    ->modalDescription('Are you sure you want to ban this user? They will be immediately logged out and unable to access the site.')
                    ->modalSubmitActionLabel('Ban User'),
                Tables\Actions\Action::make('unban')
                    ->label('Unban User')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->is_banned)
                    ->action(fn (User $record) => $record->unbanUser())
                    ->requiresConfirmation()
                    ->modalHeading('Unban User')
                    ->modalDescription('Are you sure you want to unban this user?')
                    ->modalSubmitActionLabel('Unban User'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulk_ban')
                        ->label('Ban Selected Users')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\RelationManagers\FingerprintsRelationManager;
use App\Filament\Admin\Resources\Users\RelationManagers\OrdersRelationManager;
use App\Models\Fingerprint;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(2)
                    ->components([
                        Section::make('User Information')
                            ->columns(1)
                            ->schema([
                                Flex::make([
                                    Section::make()
                                        ->contained(false)
                                        ->columns(1)
                                        ->grow(false)
                                        ->components([
                                            FileUpload::make('avatar')
                                                ->alignCenter()
                                                ->hiddenLabel()
                                                ->avatar()
                                                ->image()
                                                ->imageEditor()
                                                ->imageEditorAspectRatios([
                                                    '1:1',
                                                    '4:3',
                                                    '16:9',
                                                ])
                                                ->imageCropAspectRatio('1:1')
                                                ->visibility('public')
                                                ->disk('public')
                                                ->directory('avatars')
                                                ->openable()
                                                ->downloadable(),
                                        ]),
                                    Section::make()
                                        ->columns(2)
                                        ->contained(false)
                                        ->components([
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
                                        ]),
                                ])->verticallyAlignCenter(),
                                Select::make('groups')
                                    ->helperText('The groups the user is assigned to.')
                                    ->relationship('groups', 'name')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Profile')
                            ->collapsible()
                            ->persistCollapsed()
                            ->columns(1)
                            ->components([
                                RichEditor::make('signature')
                                    ->nullable(),
                            ]),
                        Section::make('Integrations')
                            ->columnSpanFull()
                            ->collapsible()
                            ->persistCollapsed()
                            ->components([
                                RepeatableEntry::make('socials')
                                    ->hiddenLabel()
                                    ->columns(3)
                                    ->contained(false)
                                    ->components([
                                        TextEntry::make('provider')
                                            ->formatStateUsing(fn ($state) => Str::ucfirst($state)),
                                        TextEntry::make('provider_name')
                                            ->copyable()
                                            ->label('Provider Name'),
                                        TextEntry::make('provider_id')
                                            ->hintAction(
                                                Action::make('delete')
                                                    ->color('danger')
                                                    ->size(Size::ExtraSmall)
                                                    ->requiresConfirmation()
                                                    ->successNotificationTitle('The integration has been removed.')
                                                    ->modalSubmitActionLabel('Delete')
                                                    ->modalHeading('Remove Integration')
                                                    ->action(function (Action $action, User $record) {
                                                        // $record->delete();
                                                        $action->success();
                                                    })
                                            )
                                            ->label('Provider ID')
                                            ->copyable(),
                                    ]),
                            ]),
                        Section::make('Subscription')
                            ->collapsible()
                            ->persistCollapsed()
                            ->schema([

                            ]),
                    ]),
                Group::make()
                    ->components([
                        Section::make('Details')
                            ->collapsible()
                            ->persistCollapsed()
                            ->components([
                                TextEntry::make('created_at')
                                    ->dateTime()
                                    ->since()
                                    ->dateTimeTooltip(),
                                TextEntry::make('updated_at')
                                    ->dateTime()
                                    ->since()
                                    ->dateTimeTooltip(),
                            ]),
                        Section::make('Permissions')
                            ->collapsible()
                            ->persistCollapsed()
                            ->components([
                                Select::make('roles')
                                    ->relationship('roles', 'name')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->getOptionLabelUsing(fn (Role $role) => Str::of($role->name)->replace('_', ' ')->title()->toString())
                                    ->helperText('The roles that are assigned to the user.'),
                                Select::make('permissions')
                                    ->relationship('permissions', 'name')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->getOptionLabelUsing(fn (Permission $permission) => Str::of($permission->name)->replace('_', ' ')->title()->toString())
                                    ->helperText('The permissions that are assigned to the user. These are in addition to the permissions already inherited by any assigned roles.'),
                            ]),
                        Section::make('Activity')
                            ->collapsible()
                            ->persistCollapsed()
                            ->components([]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')
                    ->circular()
                    ->label('')
                    ->width(1)
                    ->grow(false),
                TextColumn::make('name')
                    ->weight(FontWeight::Bold)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('groups.name')
                    ->badge(),
                TextColumn::make('roles.name')
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
                    ->action(fn (User $record, array $data) => $record->fingerprints()->each(fn (Fingerprint $fingerprint) => $fingerprint->banFingerprint($data['ban_reason'], Filament::auth()->user())))
                    ->requiresConfirmation()
                    ->modalHeading('Ban User')
                    ->modalDescription('Are you sure you want to ban this user? They will be immediately logged out and unable to access the site.')
                    ->modalSubmitActionLabel('Ban User'),
                Action::make('unban')
                    ->label('Unban User')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->is_banned && $record->fingerprints->count())
                    ->action(fn (User $record) => $record->fingerprints()->each(fn (Fingerprint $fingerprint) => $fingerprint->unbanFingerprint()))
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
            OrdersRelationManager::make(),
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

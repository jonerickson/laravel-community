<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Groups;

use App\Filament\Admin\Resources\Groups\Pages\CreateGroup;
use App\Filament\Admin\Resources\Groups\Pages\EditGroup;
use App\Filament\Admin\Resources\Groups\Pages\ListGroups;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Integrations\DiscordService;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group as GroupSchema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Override;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                GroupSchema::make()
                    ->columnSpan(2)
                    ->components([
                        Section::make('Group Information')
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->maxLength(65535)
                                    ->nullable(),
                                ColorPicker::make('color')
                                    ->required(),
                                FileUpload::make('image')
                                    ->nullable()
                                    ->directory('groups')
                                    ->visibility('public')
                                    ->image()
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        '16:9',
                                        '4:3',
                                        '1:1',
                                    ]),
                                Checkbox::make('is_default_guest')
                                    ->label('Default Guest Group')
                                    ->helperText('This group will apply to all guests within the platform. Without a guest group, a guest will have no permissions to perform any action on the website. Checking this box will disable any previously checked default guest group.')
                                    ->inline()
                                    ->default(false),
                                Checkbox::make('is_default_member')
                                    ->label('Default Member Group')
                                    ->helperText('All new members will be assigned to this group upon successful registration. Checking this box will disable any previously checked default member group.')
                                    ->inline()
                                    ->default(false),
                            ]),
                    ]),
                GroupSchema::make()
                    ->components([
                        Section::make('Details')
                            ->components([
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->since()
                                    ->dateTimeTooltip(),
                                TextEntry::make('updated_at')
                                    ->label('Updated')
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
                                    ->helperText('The roles that are assigned to the group.'),
                                Select::make('permissions')
                                    ->relationship('permissions', 'name')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->getOptionLabelUsing(fn (Permission $permission) => Str::of($permission->name)->replace('_', ' ')->title()->toString())
                                    ->helperText('The permissions that are assigned to the group. These are in addition to the permissions already inherited by any assigned roles.'),
                            ]),
                        Section::make('Discord')
                            ->collapsible()
                            ->persistCollapsed()
                            ->visible(fn (): bool => config('services.discord.enabled') && config('services.discord.guild_id'))
                            ->components([
                                Repeater::make('discord_role_id')
                                    ->relationship('discordRoles')
                                    ->helperText('Link this group with a Discord role. When a member is add/removed from a group, they will be added/removed from the associated Discord role.')
                                    ->label('Role(s)')
                                    ->default([])
                                    ->addActionLabel('Add role')
                                    ->simple(Select::make('discord_role_id')
                                        ->searchable()
                                        ->required()
                                        ->hiddenLabel()
                                        ->options(function () {
                                            $discordApi = app(DiscordService::class);

                                            return $discordApi->listRoles()->pluck('name', 'id');
                                        })
                                    ),
                            ]),
                    ]),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->grow(false)
                    ->alignCenter()
                    ->label(''),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->placeholder('No Description')
                    ->searchable()
                    ->limit(),
                TextColumn::make('roles.name')
                    ->placeholder('No Roles')
                    ->badge(),
                TextColumn::make('discordRoles.discord_role_id')
                    ->label('Discord Roles')
                    ->visible(fn (): bool => config('services.discord.enabled') && config('services.discord.guild_id'))
                    ->formatStateUsing(function ($state) {
                        $discordApi = app(DiscordService::class);

                        return $discordApi->getCachedGuildRoles()[$state] ?? 'Unknown';
                    })
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                ColorColumn::make('color'),
                ToggleColumn::make('is_active')
                    ->label('Active'),
                IconColumn::make('is_default_guest')
                    ->sortable()
                    ->boolean()
                    ->label('Default Guest Group'),
                IconColumn::make('is_default_member')
                    ->sortable()
                    ->boolean()
                    ->label('Default Member Group'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable()
            ->defaultSort('order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGroups::route('/'),
            'create' => CreateGroup::route('/create'),
            'edit' => EditGroup::route('/{record}/edit'),
        ];
    }
}

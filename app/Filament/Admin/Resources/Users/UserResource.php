<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users;

use App\Enums\ProductType;
use App\Enums\SubscriptionStatus;
use App\Filament\Admin\Resources\Users\Actions\BlacklistAction;
use App\Filament\Admin\Resources\Users\Actions\BulkBlacklistUsersAction;
use App\Filament\Admin\Resources\Users\Actions\BulkSwapSubscriptionsAction;
use App\Filament\Admin\Resources\Users\Actions\BulkSyncGroupsAction;
use App\Filament\Admin\Resources\Users\Actions\BulkUnblacklistUsersAction;
use App\Filament\Admin\Resources\Users\Actions\ChangePasswordAction;
use App\Filament\Admin\Resources\Users\Actions\ImpersonateAction;
use App\Filament\Admin\Resources\Users\Actions\UnblacklistAction;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\RelationManagers\FieldsRelationManager;
use App\Filament\Admin\Resources\Users\RelationManagers\FingerprintsRelationManager;
use App\Filament\Admin\Resources\Users\RelationManagers\IntegrationsRelationManager;
use App\Filament\Admin\Resources\Users\RelationManagers\OrdersRelationManager;
use App\Filament\Admin\Resources\Users\RelationManagers\PayoutsRelationManager;
use App\Filament\Admin\Resources\Users\Widgets\RegistrationsTable;
use App\Filament\Admin\Resources\Users\Widgets\UserStatsOverview;
use App\Filament\Exports\UserExporter;
use App\Jobs\Discord\SyncRoles;
use App\Livewire\PaymentMethods\ListPaymentMethods;
use App\Livewire\Subscriptions\ListSubscriptions;
use App\Models\Price;
use App\Models\Product;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Integrations\DiscordService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Override;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->persistTabInQueryString()
                    ->contained(false)
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Profile')
                            ->icon('heroicon-o-user')
                            ->columns(3)
                            ->schema([
                                Group::make()
                                    ->columnSpan(2)
                                    ->components([
                                        Section::make('User Information')
                                            ->description("The user's profile information.")
                                            ->columns(1)
                                            ->headerActions([
                                                ChangePasswordAction::make()
                                                    ->user(fn (User $record): User => $record),
                                            ])
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
                                                                ->directory('avatars')
                                                                ->openable()
                                                                ->downloadable(),
                                                        ]),
                                                    Section::make()
                                                        ->columns()
                                                        ->contained(false)
                                                        ->components([
                                                            TextInput::make('name')
                                                                ->columnSpanFull()
                                                                ->required()
                                                                ->maxLength(255),
                                                            TextInput::make('email')
                                                                ->unique()
                                                                ->email()
                                                                ->required()
                                                                ->maxLength(255),
                                                            DateTimePicker::make('email_verified_at')
                                                                ->label('Email Verified'),
                                                        ]),
                                                ])->verticallyAlignCenter(),
                                                Select::make('groups')
                                                    ->helperText("The groups the user is assigned to. Some of these may be auto-assigned based on products they've purchased or through other platform features. You may manually sync a user's groups with the actions above.")
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
                                    ]),
                                Group::make()
                                    ->components([
                                        Section::make('Details')
                                            ->visibleOn('edit')
                                            ->components([
                                                TextEntry::make('created_at')
                                                    ->label('Created')
                                                    ->since()
                                                    ->dateTimeTooltip(),
                                                TextEntry::make('updated_at')
                                                    ->label('Updated')
                                                    ->since()
                                                    ->dateTimeTooltip(),
                                                TextEntry::make('reference_id')
                                                    ->label('Reference ID')
                                                    ->copyable(),
                                                TextEntry::make('url')
                                                    ->label('URL')
                                                    ->getStateUsing(fn (User $record): string => route('users.show', $record->reference_id))
                                                    ->copyable()
                                                    ->suffixAction(fn (User $record): Action => Action::make('open')
                                                        ->url(route('users.show', $record->reference_id), shouldOpenInNewTab: true)
                                                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                                                    ),
                                            ]),
                                        Section::make('Permissions')
                                            ->visible(fn () => Auth::user()->hasRole(\App\Enums\Role::Administrator))
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
                                            ]),
                                        Section::make('Activity')
                                            ->collapsible()
                                            ->persistCollapsed()
                                            ->components([]),
                                    ]),
                            ]),
                        Tabs\Tab::make('Billing')
                            ->icon(Heroicon::OutlinedCurrencyDollar)
                            ->schema([
                                Section::make('Billing Information')
                                    ->description("The user's billing information.")
                                    ->columns()
                                    ->schema([
                                        TextInput::make('billing_address')
                                            ->maxLength(255)
                                            ->label('Address')
                                            ->nullable(),
                                        TextInput::make('billing_address_line_2')
                                            ->maxLength(255)
                                            ->label('Address Line 2')
                                            ->nullable(),
                                        Grid::make()
                                            ->columns(4)
                                            ->columnSpanFull()
                                            ->schema([
                                                TextInput::make('billing_city')
                                                    ->maxLength(255)
                                                    ->label('City')
                                                    ->nullable(),
                                                TextInput::make('billing_state')
                                                    ->maxLength(255)
                                                    ->label('State')
                                                    ->nullable(),
                                                TextInput::make('billing_postal_code')
                                                    ->maxLength(25)
                                                    ->label('Postal Code')
                                                    ->nullable(),
                                                TextInput::make('billing_country')
                                                    ->maxLength(2)
                                                    ->label('Country')
                                                    ->nullable(),
                                            ]),
                                        Textarea::make('extra_billing_information')
                                            ->columnSpanFull()
                                            ->maxLength(65535)
                                            ->label('Extra Billing information')
                                            ->nullable(),
                                        TextInput::make('vat_id')
                                            ->columnSpanFull()
                                            ->maxLength(50)
                                            ->label('VAT ID')
                                            ->nullable(),
                                    ]),
                            ]),
                        Tabs\Tab::make('Integrations')
                            ->icon(Heroicon::OutlinedLink)
                            ->visibleOn('edit')
                            ->schema([
                                Section::make('Discord Roles')
                                    ->description('Current Discord server roles assigned to this user.')
                                    ->collapsible()
                                    ->persistCollapsed()
                                    ->visible(fn (): bool => config('services.discord.enabled') && config('services.discord.guild_id'))
                                    ->headerActions([
                                        Action::make('sync_discord_roles')
                                            ->label('Sync')
                                            ->color('gray')
                                            ->successNotificationTitle("A background job has been dispatched to update the user's Discord roles.")
                                            ->requiresConfirmation(false)
                                            ->action(function (User $record): void {
                                                SyncRoles::dispatch($record->id);
                                            }),
                                        Action::make('refresh_discord_roles')
                                            ->label('Refresh')
                                            ->color('gray')
                                            ->successNotificationTitle('Discord roles cache successfully cleared.')
                                            ->requiresConfirmation(false)
                                            ->action(function (User $record): void {
                                                $discordIntegration = $record->integrations()->where('provider', 'discord')->first();

                                                if ($discordIntegration?->provider_id) {
                                                    Cache::forget('discord_user_roles.'.$discordIntegration->provider_id);
                                                }

                                                Cache::forget('discord_guild_roles');
                                            }),
                                    ])
                                    ->schema([
                                        TextEntry::make('discord_roles')
                                            ->label('Assigned Roles')
                                            ->badge()
                                            ->placeholder('No Discord Roles Assigned')
                                            ->state(function (User $record): array {
                                                $discordIntegration = $record->integrations()->where('provider', 'discord')->first();

                                                if (! $discordIntegration?->provider_id) {
                                                    return [];
                                                }

                                                $discordApi = app(DiscordService::class);
                                                $roleIds = $discordApi->getCachedUserRoleIds($discordIntegration->provider_id);
                                                $guildRoles = $discordApi->getCachedGuildRoles();

                                                return $roleIds->map(fn (string $roleId): string => $guildRoles->get($roleId, $roleId))->toArray();
                                            }),
                                    ]),
                                Livewire::make(IntegrationsRelationManager::class, fn (?User $record): array => [
                                    'ownerRecord' => $record,
                                    'pageClass' => EditUser::class,
                                ]),
                            ]),
                        Tabs\Tab::make('Orders')
                            ->icon(Heroicon::OutlinedShoppingCart)
                            ->visibleOn('edit')
                            ->schema([
                                Livewire::make(OrdersRelationManager::class, fn (?User $record): array => [
                                    'ownerRecord' => $record,
                                    'pageClass' => EditUser::class,
                                ]),
                            ]),
                        Tabs\Tab::make('Payment Methods')
                            ->icon(Heroicon::OutlinedCreditCard)
                            ->visibleOn('edit')
                            ->schema([
                                Livewire::make(ListPaymentMethods::class, fn (?User $record): array => [
                                    'record' => $record,
                                ]),
                            ]),
                        Tabs\Tab::make('Payouts')
                            ->icon(Heroicon::OutlinedBanknotes)
                            ->visibleOn('edit')
                            ->schema([
                                Livewire::make(PayoutsRelationManager::class, fn (?User $record): array => [
                                    'ownerRecord' => $record,
                                    'pageClass' => EditUser::class,
                                ]),
                            ]),
                        Tabs\Tab::make('Subscriptions')
                            ->icon(Heroicon::OutlinedArrowPath)
                            ->visibleOn('edit')
                            ->schema([
                                Livewire::make(ListSubscriptions::class, fn (?User $record): array => [
                                    'record' => $record,
                                ]),
                            ]),
                        Tabs\Tab::make('Warnings')
                            ->icon('heroicon-o-exclamation-triangle')
                            ->visibleOn('edit')
                            ->schema([
                                Section::make('Warning Information')
                                    ->description('View and manage user warning points and consequences.')
                                    ->columns()
                                    ->schema([
                                        TextEntry::make('warning_points')
                                            ->label('Current Warning Points')
                                            ->badge()
                                            ->color(fn (int $state, User $record): string => $record->active_consequence_type?->getColor() ?? 'success'),
                                        TextEntry::make('active_consequence.type')
                                            ->label('Current Consequence')
                                            ->badge()
                                            ->formatStateUsing(fn ($state) => $state?->getLabel() ?? 'None')
                                            ->color(fn ($state) => $state?->getColor() ?? 'success')
                                            ->icon(fn ($state) => $state?->getIcon() ?? 'heroicon-o-check-circle'),
                                    ]),
                                Livewire::make(RelationManagers\UserWarningsRelationManager::class, fn (User $record): array => [
                                    'ownerRecord' => $record,
                                    'pageClass' => EditUser::class,
                                ]),
                            ]),
                    ]),
            ]);
    }

    #[Override]
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
                    ->sortable()
                    ->copyable()
                    ->searchable(),
                TextColumn::make('groups.name')
                    ->sortable()
                    ->placeholder('No Groups')
                    ->badge(),
                TextColumn::make('roles.name')
                    ->sortable()
                    ->placeholder('No Roles')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subscriptions.name')
                    ->label('Subscription')
                    ->sortable()
                    ->placeholder('No Subscription')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('products.name')
                    ->sortable()
                    ->placeholder('No Products')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                TextColumn::make('fingerprints_count')
                    ->label('Devices')
                    ->counts('fingerprints')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('groups')
                    ->relationship('groups', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Filter::make('subscription')
                    ->schema([
                        Select::make('subscription')
                            ->label('Subscription Packages')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(Price::query()
                                ->active()
                                ->with('product')
                                ->whereRelation('product', 'type', ProductType::Subscription)
                                ->whereHas('product', fn (Builder|Product $query) => $query->active())
                                ->get()
                                ->mapWithKeys(fn (Price $price): array => [$price->external_price_id => sprintf('%s: %s', $price->product->getLabel(), $price->getLabel())])),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            filled($data['subscription'] ?? null),
                            fn (Builder|User $query): Builder => $query->whereRelation('subscriptions', 'stripe_price', $data['subscription']),
                        )
                    ),
                Filter::make('subscription_status')
                    ->schema([
                        Select::make('subscription_status')
                            ->label('Subscription Status')
                            ->searchable()
                            ->preload()
                            ->options(SubscriptionStatus::class),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['subscription_status'],
                            fn (Builder $query, $date): Builder => $query->whereRelation('subscriptions', 'stripe_status', $data['subscription_status']),
                        )
                    ),
                Filter::make('has_no_subscription')
                    ->label('Has no active subscription')
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['isActive'],
                            fn (Builder $query, $date): Builder => $query->whereDoesntHave('subscriptions', fn (Builder|Subscription $query) => $query->active()),
                        )),
            ])
            ->groups(['groups.name'])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                ActionGroup::make([
                    ImpersonateAction::make(),
                    BlacklistAction::make(),
                    UnblacklistAction::make(),
                ]),
            ])
            ->toolbarActions([
                ExportBulkAction::make()
                    ->exporter(UserExporter::class),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkBlacklistUsersAction::make(),
                    BulkUnblacklistUsersAction::make(),
                    BulkSyncGroupsAction::make(),
                    BulkSwapSubscriptionsAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            FingerprintsRelationManager::make(),
            FieldsRelationManager::make(),
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

    #[Override]
    public static function getWidgets(): array
    {
        return [
            UserStatsOverview::make(),
            RegistrationsTable::make(),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference_id', 'name', 'email'];
    }

    public static function getGlobalSearchResultDetails(User|Model $record): array
    {
        return [
            'Email' => $record->email,
        ];
    }
}

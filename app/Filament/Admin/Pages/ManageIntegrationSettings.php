<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\DiscordNameSyncDirection;
use App\Enums\Role;
use App\Filament\Admin\Clusters\Settings\SettingsCluster;
use App\Settings\IntegrationSettings;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Override;
use UnitEnum;

class ManageIntegrationSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static string $settings = IntegrationSettings::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Integrations';

    protected ?string $subheading = 'Manage third-party integrations.';

    public static function canAccess(): bool
    {
        return Auth::user()->hasRole(Role::Administrator);
    }

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Integrations')
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        $this->getIntercomTab(),
                        $this->getDiscordTab(),
                    ]),
            ]);
    }

    protected function getIntercomTab(): Tab
    {
        $envAppId = config('services.intercom.app_id');
        $envSecretKey = config('services.intercom.secret_key');

        return Tab::make('Intercom')
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->schema([
                Toggle::make('intercom_enabled')
                    ->label('Enable Intercom Integration')
                    ->helperText('Show the Intercom chat widget on your site.')
                    ->disabled(filled($envAppId))
                    ->formatStateUsing(fn ($state) => $envAppId ? true : $state),
                TextInput::make('intercom_app_id')
                    ->label('App ID')
                    ->helperText($envAppId ? 'Set via INTERCOM_APP_ID environment variable.' : 'Your Intercom app ID (found in Intercom settings).')
                    ->placeholder('abc123xy')
                    ->disabled(filled($envAppId))
                    ->formatStateUsing(fn ($state) => $envAppId ?: $state),
                Toggle::make('intercom_auth_required')
                    ->default(true)
                    ->label('Require Authentication')
                    ->helperText('Only show the chat widget to logged-in users.'),
                TextInput::make('intercom_secret_key')
                    ->label('Identity verification secret')
                    ->helperText($envSecretKey ? 'Set via INTERCOM_SECRET_KEY environment variable.' : 'Your Intercom identity verification secret key for secure user authentication. Found in Intercom Settings > Security.')
                    ->password()
                    ->revealable()
                    ->placeholder('Enter secret key')
                    ->disabled(filled($envSecretKey))
                    ->formatStateUsing(fn ($state) => $envSecretKey ?: $state),
            ]);
    }

    protected function getDiscordTab(): Tab
    {
        $envEnabled = config('services.discord.enabled');
        $envGuildId = config('services.discord.guild_id');
        $envBotToken = config('services.discord.bot_token');

        return Tab::make('Discord')
            ->icon(Heroicon::OutlinedUserGroup)
            ->schema([
                Toggle::make('discord_enabled')
                    ->label('Enable Discord integration')
                    ->helperText('Enable Discord bot features for your community.')
                    ->disabled(filled($envEnabled))
                    ->formatStateUsing(fn ($state) => $envEnabled ? (bool) $envEnabled : $state)
                    ->live(),
                TextInput::make('discord_guild_id')
                    ->label('Guild ID')
                    ->helperText($envGuildId ? 'Set via DISCORD_GUILD_ID environment variable.' : 'Your Discord server (guild) ID.')
                    ->placeholder('123456789012345678')
                    ->disabled(filled($envGuildId))
                    ->formatStateUsing(fn ($state) => $envGuildId ?: $state),
                TextInput::make('discord_bot_token')
                    ->label('Bot Token')
                    ->helperText($envBotToken ? 'Set via DISCORD_BOT_TOKEN environment variable.' : 'Your Discord bot token from the Discord Developer Portal.')
                    ->password()
                    ->revealable()
                    ->placeholder('Enter bot token')
                    ->disabled(filled($envBotToken))
                    ->formatStateUsing(fn ($state) => $envBotToken ?: $state),
                Toggle::make('discord_name_sync_enabled')
                    ->label('Enable Name Synchronization')
                    ->helperText('Automatically sync usernames between your app and Discord.')
                    ->live(),
                Select::make('discord_name_sync_direction')
                    ->label('Sync Direction')
                    ->helperText('Choose which direction to sync usernames.')
                    ->options(DiscordNameSyncDirection::class)
                    ->visible(fn ($get): bool => (bool) $get('discord_name_sync_enabled')),
                Toggle::make('discord_name_sync_enforced')
                    ->label('Enforce Name Sync')
                    ->helperText('When enabled, users cannot change their name independently - it will always be synced from the source.')
                    ->visible(fn ($get): bool => (bool) $get('discord_name_sync_enabled')),
            ]);
    }
}

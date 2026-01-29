<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\Role;
use App\Filament\Admin\Clusters\Settings\SettingsCluster;
use App\Settings\IntegrationSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
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
        $envAppId = config('services.intercom.app_id');
        $envSecretKey = config('services.intercom.secret_key');

        return $schema
            ->components([
                Section::make('Intercom')
                    ->description('Configure Intercom live chat widget. Note: If INTERCOM_APP_ID is set in your environment, it will override these settings and the widget will always be enabled.')
                    ->columnSpanFull()
                    ->schema([
                        Toggle::make('intercom_enabled')
                            ->label('Enable Intercom')
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
                            ->label('Identity Verification Secret')
                            ->helperText($envSecretKey ? 'Set via INTERCOM_SECRET_KEY environment variable.' : 'Your Intercom identity verification secret key for secure user authentication. Found in Intercom Settings > Security.')
                            ->password()
                            ->revealable()
                            ->placeholder('Enter secret key')
                            ->disabled(filled($envSecretKey))
                            ->formatStateUsing(fn ($state) => $envSecretKey ?: $state),
                    ]),
            ]);
    }
}

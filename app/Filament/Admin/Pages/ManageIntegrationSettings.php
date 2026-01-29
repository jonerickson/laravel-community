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
        return $schema
            ->components([
                Section::make('Intercom')
                    ->description('Configure Intercom live chat widget. Note: If INTERCOM_APP_ID is set in your environment, it will override these settings and the widget will always be enabled.')
                    ->columnSpanFull()
                    ->schema([
                        Toggle::make('intercom_enabled')
                            ->label('Enable Intercom')
                            ->helperText('Show the Intercom chat widget on your site.'),
                        TextInput::make('intercom_app_id')
                            ->label('App ID')
                            ->helperText('Your Intercom app ID (found in Intercom settings).')
                            ->placeholder('abc123xy'),
                    ]),
            ]);
    }
}

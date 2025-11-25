<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\Role;
use App\Filament\Admin\Clusters\Settings\SettingsCluster;
use App\Settings\EmailSettings;
use BackedEnum;
use Filament\Forms\Components\RichEditor;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Override;
use UnitEnum;

class ManageEmailSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string $settings = EmailSettings::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Emails';

    protected static ?int $navigationSort = -1;

    protected static ?string $title = 'Email Settings';

    protected ?string $subheading = 'Manage your platform email settings.';

    public static function canAccess(): bool
    {
        return Auth::user()->hasRole(Role::Administrator);
    }

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Templates')
                    ->columnSpanFull()
                    ->schema([
                        RichEditor::make('welcome_email')
                            ->label('Welcome Email')
                            ->nullable()
                            ->maxLength(65535)
                            ->helperText('The main email sent to new members when they create a new account for the first time.'),
                    ]),
            ]);
    }
}

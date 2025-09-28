<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ManageGeneralSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string $settings = GeneralSettings::class;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'General';

    protected static ?string $title = 'General Settings';

    protected ?string $subheading = 'Manage your main platform settings.';

    public static function canAccess(): bool
    {
        return Auth::user()->hasRole('super-admin');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Site Settings')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('site_name')
                            ->label('Site Name')
                            ->required()
                            ->maxValue(255)
                            ->helperText('The main name of your site.'),
                    ]),
            ]);
    }
}

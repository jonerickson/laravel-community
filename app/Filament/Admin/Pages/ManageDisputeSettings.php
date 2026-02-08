<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\DisputeAction;
use App\Enums\Role;
use App\Filament\Admin\Clusters\Settings\SettingsCluster;
use App\Settings\DisputeSettings;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Override;
use UnitEnum;

class ManageDisputeSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static string $settings = DisputeSettings::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|UnitEnum|null $navigationGroup = 'Payments';

    protected static ?string $navigationLabel = 'Disputes';

    protected static ?string $title = 'Dispute Settings';

    protected ?string $subheading = 'Configure automated actions when a payment dispute is received.';

    public static function canAccess(): bool
    {
        return Auth::user()->hasRole(Role::Administrator);
    }

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Automated Actions')
                    ->description('Select which actions should be automatically performed when a new dispute is received.')
                    ->columnSpanFull()
                    ->schema([
                        CheckboxList::make('dispute_actions')
                            ->hiddenLabel()
                            ->helperText('Select which actions should be automatically performed when a new dispute is received.')
                            ->options(DisputeAction::class)
                            ->columns(),
                    ]),
            ]);
    }
}

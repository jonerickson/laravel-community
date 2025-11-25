<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\Role;
use App\Filament\Admin\Clusters\Settings\SettingsCluster;
use App\Models\Policy;
use App\Settings\RegistrationSettings;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Override;
use UnitEnum;

class ManageRegistrationSettings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static string $settings = RegistrationSettings::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|UnitEnum|null $navigationGroup = 'Members';

    protected static ?int $navigationSort = -2;

    protected static ?string $navigationLabel = 'Registration';

    protected ?string $subheading = 'Manage your platform registration settings.';

    public static function canAccess(): bool
    {
        return Auth::user()->hasRole(Role::Administrator);
    }

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Compliance')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('required_policy_ids')
                            ->label('Required Policies')
                            ->helperText('The policies a member must agree to to register a new account.')
                            ->default([])
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(Policy::pluck('title', 'id')),
                    ]),
            ]);
    }
}

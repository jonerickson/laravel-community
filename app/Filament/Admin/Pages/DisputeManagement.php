<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\Role;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Override;
use UnitEnum;

class DisputeManagement extends Page
{
    public ?string $search = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static string|UnitEnum|null $navigationGroup = 'Support';

    protected static ?string $title = 'Dispute management';

    protected static ?string $navigationLabel = 'Dispute Management';

    protected string $view = 'filament.admin.pages.dispute-management';

    public static function canAccess(): bool
    {
        return Auth::user()->hasRole(Role::Administrator);
    }

    #[Override]
    public function getSubheading(): ?string
    {
        return 'Search transactions and generate dispute evidence packages.';
    }

    public function searchForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('search')
                    ->label('Transaction ID')
                    ->placeholder('Search by reference ID or payment ID...')
                    ->live(debounce: 500),
            ]);
    }
}

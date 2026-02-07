<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\Role;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Override;
use UnitEnum;

class DisputeManagement extends Page implements HasTable
{
    use InteractsWithTable;

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

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Order::query()
                ->with('user')
                ->when($this->search, fn (Builder $query, string $search): Builder => $query->where(function (Builder $query) use ($search): void {
                    $query->where('reference_id', 'like', "%{$search}%")
                        ->orWhere('external_payment_id', 'like', "%{$search}%");
                }))
                ->when(! $this->search, fn (Builder $query): Builder => $query->whereRaw('1 = 0'))
            )
            ->columns([
                TextColumn::make('reference_id')
                    ->label('Reference ID')
                    ->searchable(false),
                TextColumn::make('external_payment_id')
                    ->label('Stripe Payment ID')
                    ->searchable(false),
                TextColumn::make('user.email')
                    ->label('User Email'),
                TextColumn::make('amount_paid')
                    ->label('Amount')
                    ->formatStateUsing(fn (?int $state): string => $state ? Number::currency($state / 100) : '$0.00'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime(),
            ])
            ->recordActions([
                Action::make('downloadDisputeEvidence')
                    ->label('Download dispute evidence')
                    ->icon(Heroicon::OutlinedDocumentArrowDown)
                    ->color('primary')
                    ->url(fn (Order $record): string => route('admin.dispute-evidence.download', $record))
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('No results found')
            ->emptyStateDescription('Try searching with a different transaction ID.')
            ->paginated(false);
    }
}

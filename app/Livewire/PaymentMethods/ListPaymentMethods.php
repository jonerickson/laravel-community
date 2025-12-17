<?php

declare(strict_types=1);

namespace App\Livewire\PaymentMethods;

use App\Filament\Admin\Resources\PaymentMethods\Actions\NewAction;
use App\Managers\PaymentManager;
use App\Models\User;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Component;

class ListPaymentMethods extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public ?User $user = null;

    public array $records = [];

    public function mount(?User $record = null): void
    {
        $this->user = $record;

        if ($this->user instanceof User) {
            $this->records = app(PaymentManager::class)->listPaymentMethods($this->user)->toArray() ?? [];
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Payment Methods')
            ->description("The user's saved payment methods.")
            ->emptyStateHeading('No payment methods')
            ->emptyStateDescription('This user has no payment methods on file.')
            ->emptyStateIcon('heroicon-o-credit-card')
            ->records(fn (): Collection => collect($this->records))
            ->columns([
                TextColumn::make('type')
                    ->default(new HtmlString('&ndash;'))
                    ->formatStateUsing(fn ($state) => is_string($state) ? Str::ucfirst($state) : $state),
                TextColumn::make('brand')
                    ->default(new HtmlString('&ndash;'))
                    ->formatStateUsing(fn ($state) => is_string($state) ? Str::ucfirst($state) : $state),
                TextColumn::make('last4')
                    ->label('Last 4')
                    ->default(new HtmlString('&ndash;')),
                TextColumn::make('expMonth')
                    ->label('Expiration - Month')
                    ->default(new HtmlString('&ndash;')),
                TextColumn::make('expYear')
                    ->label('Expiration - Year')
                    ->default(new HtmlString('&ndash;')),
                TextColumn::make('holderName')
                    ->label('Holder Name')
                    ->default(new HtmlString('&ndash;')),
                TextColumn::make('holderEmail')
                    ->label('Holder Email')
                    ->default(new HtmlString('&ndash;')),
            ])
            ->headerActions([
                NewAction::make()
                    ->user($this->user),
            ])
            ->recordActions([

            ]);
    }

    public function render(): View
    {
        return view('livewire.payment-methods.list-payment-methods');
    }
}

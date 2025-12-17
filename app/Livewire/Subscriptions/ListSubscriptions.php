<?php

declare(strict_types=1);

namespace App\Livewire\Subscriptions;

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Filament\Admin\Resources\Subscriptions\Actions\CancelAction;
use App\Filament\Admin\Resources\Subscriptions\Actions\ContinueAction;
use App\Filament\Admin\Resources\Subscriptions\Actions\NewAction;
use App\Filament\Admin\Resources\Subscriptions\Actions\SwapAction;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
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
use Livewire\Component;

class ListSubscriptions extends Component implements HasActions, HasSchemas, HasTable
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
            $this->records = app(PaymentManager::class)->listSubscriptions($this->user)->toArray() ?? [];
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Subscriptions')
            ->description("The user's subscription history.")
            ->emptyStateHeading('No subscriptions')
            ->emptyStateDescription('This user has no subscription history.')
            ->emptyStateIcon('heroicon-o-arrow-path')
            ->records(fn (): Collection => collect($this->records))
            ->columns([
                TextColumn::make('product.name')
                    ->placeholder('Unknown Product'),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->url(fn (array $record): ?string => data_get($record, 'user') ? EditUser::getUrl(['record' => data_get($record, 'user.id')]) : null),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => SubscriptionStatus::tryFrom($state)->getLabel())
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trialing' => 'info',
                        'canceled' => 'warning',
                        'incomplete', 'incomplete_expired', 'past_due' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('price.amount')
                    ->money()
                    ->label('Price')
                    ->placeholder('No Price'),
                TextColumn::make('price.interval')
                    ->money()
                    ->label('Interval')
                    ->formatStateUsing(fn ($state) => SubscriptionInterval::tryFrom($state ?? ''))
                    ->placeholder('No Interval'),
                TextColumn::make('trialEndsAt')
                    ->label('Trial Ends At')
                    ->since()
                    ->dateTimeTooltip()
                    ->placeholder('No Trial'),
                TextColumn::make('endsAt')
                    ->label('Ends At')
                    ->since()
                    ->dateTimeTooltip()
                    ->placeholder('Active')
                    ->color(fn ($state): string => $state ? 'warning' : 'success'),
                TextColumn::make('createdAt')
                    ->since()
                    ->dateTimeTooltip()
                    ->label('Started'),
            ])
            ->headerActions([
                SwapAction::make()
                    ->user($this->user),
                NewAction::make()
                    ->user($this->user),
            ])
            ->recordActions([
                CancelAction::make(),
                ContinueAction::make(),
            ]);
    }

    public function render(): View
    {
        return view('livewire.subscriptions.list-subscriptions');
    }
}

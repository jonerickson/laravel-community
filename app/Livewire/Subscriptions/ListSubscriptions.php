<?php

declare(strict_types=1);

namespace App\Livewire\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Filament\Admin\Resources\Subscriptions\Actions\CancelAction;
use App\Filament\Admin\Resources\Subscriptions\Actions\ContinueAction;
use App\Filament\Admin\Resources\Subscriptions\Actions\NewAction;
use App\Filament\Admin\Resources\Subscriptions\Actions\SwapAction;
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
use Livewire\Component;

class ListSubscriptions extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public ?User $user = null;

    public array $records = [];

    public function mount(User $record): void
    {
        $this->user = $record;
        $this->records = app(PaymentManager::class)->listSubscriptions($this->user)->toArray();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Subscriptions')
            ->description('The user\'s subscription history.')
            ->records(fn () => collect($this->records))
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('product.name')
                    ->searchable(),
                TextColumn::make('status')
                    ->formatStateUsing(fn ($state) => SubscriptionStatus::tryFrom($state))
                    ->badge()
                    ->searchable(),
                TextColumn::make('trialEndsAt')
                    ->label('Trial Ends At')
                    ->since()
                    ->dateTimeTooltip()
                    ->placeholder('None')
                    ->searchable(),
                TextColumn::make('endsAt')
                    ->label('Ends At')
                    ->since()
                    ->dateTimeTooltip()
                    ->placeholder('None')
                    ->searchable(),
                TextColumn::make('createdAt')
                    ->since()
                    ->dateTimeTooltip()
                    ->label('Purchased On')
                    ->sortable(),
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

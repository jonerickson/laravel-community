<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Data\SubscriptionData;
use App\Filament\Admin\Resources\Subscriptions\Actions\CancelAction;
use App\Filament\Admin\Resources\Subscriptions\Actions\ContinueAction;
use App\Managers\PaymentManager;
use App\Models\User;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedCreditCard;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('type'),
                TextEntry::make('stripe_id'),
                TextEntry::make('stripe_status'),
                TextEntry::make('stripe_price')
                    ->placeholder('-'),
                TextEntry::make('quantity')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('trial_ends_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('ends_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->description('The user\'s subscription history.')
            ->records(function () {
                $paymentManager = app(PaymentManager::class);
                /** @var User $user */
                $user = $this->getOwnerRecord();
                $subscription = $paymentManager->currentSubscription($user);

                return Collection::wrap($subscription)->map(function (SubscriptionData $subscriptionData) {
                    return [
                        'type' => $subscriptionData->name,
                    ];
                });
            })
            ->columns([
                TextColumn::make('type')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('stripe_status')
                    ->badge()
                    ->label('Status')
                    ->searchable(),
                TextColumn::make('stripe_id')
                    ->label('External Subscription ID')
                    ->searchable(),
                TextColumn::make('stripe_price')
                    ->label('External Price ID')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('trial_ends_at')
                    ->label('Trial Ends At')
                    ->placeholder('None')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('End At')
                    ->placeholder('None')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                CancelAction::make(),
                ContinueAction::make(),
            ]);
    }
}

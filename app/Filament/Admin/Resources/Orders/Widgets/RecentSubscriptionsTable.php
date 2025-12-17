<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Widgets;

use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Livewire\Subscriptions\ListSubscriptions;
use App\Managers\PaymentManager;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentSubscriptionsTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        $listSubscriptions = new ListSubscriptions;
        $listSubscriptions->mount();
        $listSubscriptions->records = app(PaymentManager::class)->listSubscriptions(filters: ['limit' => 15])->toArray() ?? [];

        return $listSubscriptions->table($table)
            ->heading('Recent Subscriptions')
            ->description('Most recent subscription activity.')
            ->searchable(false)
            ->deferLoading()
            ->headerActions([])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (array $record): string => EditUser::getUrl([
                        'record' => data_get($record, 'user.id'),
                        'tab' => 'subscriptions::data::tab',
                    ])),
            ]);
    }
}

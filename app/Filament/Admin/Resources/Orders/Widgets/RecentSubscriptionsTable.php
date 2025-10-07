<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Widgets;

use App\Livewire\Subscriptions\ListSubscriptions;
use App\Managers\PaymentManager;
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
        $listSubscriptions->records = app(PaymentManager::class)->listSubscriptions(filters: ['limit' => 15])->toArray();

        return $listSubscriptions->table($table)
            ->heading('Recent Subscriptions')
            ->description('Most recent subscription activity.')
            ->searchable(false)
            ->headerActions([])
            ->recordActions([]);
    }
}

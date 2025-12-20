<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\RelationManagers;

use App\Models\Order;
use App\Models\User;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Override;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

class HistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'storedEvents';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedClock;

    protected static ?string $badgeColor = 'primary';

    protected static ?string $title = 'History';

    #[Override]
    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        /** @var Order $ownerRecord */
        return (string) EloquentStoredEvent::query()
            ->where('aggregate_uuid', (string) $ownerRecord->id)
            ->count();
    }

    #[Override]
    public function isReadOnly(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Order History')
            ->description('Complete audit trail of all changes to this order.')
            ->modifyQueryUsing(function ($query) {
                $query->where('aggregate_uuid', (string) $this->getOwnerRecord()->id)
                    ->orderBy('created_at', 'desc');
            })
            ->columns([
                TextColumn::make('event_name')
                    ->label('Event')
                    ->badge()
                    ->color(fn (EloquentStoredEvent $record): string => match (class_basename($record->event_class)) {
                        'OrderCreated' => 'info',
                        'OrderSaved' => 'gray',
                        'OrderPending' => 'warning',
                        'OrderProcessing' => 'warning',
                        'OrderSucceeded' => 'success',
                        'OrderRefunded' => 'danger',
                        'OrderCancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (EloquentStoredEvent $record): string => str_replace('Order', '', class_basename($record->event_class))
                    )
                    ->sortable()
                    ->searchable(query: function ($query, string $search): void {
                        $query->where('event_class', 'like', "%{$search}%");
                    }),
                TextColumn::make('event_details')
                    ->label('Details')
                    ->html()
                    ->wrap()
                    ->formatStateUsing(function (EloquentStoredEvent $record): HtmlString {
                        return new HtmlString($this->formatEventDetails($record));
                    })
                    ->searchable(query: function ($query, string $search): void {
                        $query->where('event_properties', 'like', "%{$search}%");
                    }),
                TextColumn::make('created_by_user')
                    ->label('Changed By')
                    ->formatStateUsing(function (EloquentStoredEvent $record): string {
                        $createdBy = $record->event_properties['createdBy'] ?? null;

                        if (! $createdBy) {
                            return 'System';
                        }

                        $user = User::find($createdBy);

                        return $user ? $user->name : 'Unknown User';
                    })
                    ->sortable(query: function ($query, string $direction): void {
                        $query->orderByRaw("JSON_EXTRACT(event_properties, '$.createdBy') {$direction}");
                    })
                    ->searchable(query: function ($query, string $search): void {
                        $userIds = User::where('name', 'like', "%{$search}%")->pluck('id');
                        $query->where(function ($q) use ($userIds) {
                            foreach ($userIds as $userId) {
                                $q->orWhereRaw("JSON_EXTRACT(event_properties, '$.createdBy') = ?", [$userId]);
                            }
                        });
                    }),
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100]);
    }

    private function formatEventDetails(EloquentStoredEvent $record): string
    {
        $properties = $record->event_properties;
        $eventType = class_basename($record->event_class);

        // Get the Order from the properties
        $orderData = $properties['order'] ?? [];

        return match ($eventType) {
            'OrderCreated' => sprintf(
                'Order created with status <strong>%s</strong>%s',
                $orderData['status'] ?? 'unknown',
                isset($orderData['amount_due']) ? sprintf(' &bull; Due: $%.2f', $orderData['amount_due'] / 100) : ''
            ),
            'OrderSaved' => $this->formatOrderSavedDetails($orderData),
            'OrderSucceeded' => sprintf(
                'Payment succeeded%s',
                isset($orderData['amount_paid']) ? sprintf(' &bull; Paid: $%.2f', $orderData['amount_paid'] / 100) : ''
            ),
            'OrderRefunded' => sprintf(
                'Order refunded%s%s',
                isset($orderData['refund_reason']) ? ' &bull; Reason: <strong>'.e($orderData['refund_reason']).'</strong>' : '',
                isset($orderData['refund_notes']) ? '<br><em>'.e($orderData['refund_notes']).'</em>' : ''
            ),
            'OrderCancelled' => 'Order cancelled',
            'OrderProcessing' => 'Order is being processed',
            'OrderPending' => 'Order is pending',
            default => 'Event occurred',
        };
    }

    private function formatOrderSavedDetails(array $orderData): string
    {
        $details = [];

        if (isset($orderData['status'])) {
            $details[] = sprintf('Status: <strong>%s</strong>', $orderData['status']);
        }

        if (isset($orderData['amount_paid'])) {
            $details[] = sprintf('Paid: $%.2f', $orderData['amount_paid'] / 100);
        }

        if (isset($orderData['amount_remaining'])) {
            $details[] = sprintf('Remaining: $%.2f', $orderData['amount_remaining'] / 100);
        }

        if (empty($details)) {
            return 'Order updated';
        }

        return 'Order updated &bull; '.implode(' &bull; ', $details);
    }
}

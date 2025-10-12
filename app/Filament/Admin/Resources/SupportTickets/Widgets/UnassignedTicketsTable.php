<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTickets\Widgets;

use App\Filament\Admin\Resources\SupportTickets\Actions\AssignToMeAction;
use App\Filament\Admin\Resources\SupportTickets\Pages\ViewSupportTicket;
use App\Models\SupportTicket;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UnassignedTicketsTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(SupportTicket::query()->unassigned()->active()->orderBy('created_at', 'desc'))
            ->heading('Unassigned Tickets')
            ->description('Open tickets waiting for assignment.')
            ->deferLoading()
            ->columns([
                TextColumn::make('ticket_number')
                    ->label('Ticket #')
                    ->sortable()
                    ->weight('medium')
                    ->copyable(),
                TextColumn::make('subject')
                    ->sortable()
                    ->limit(50)
                    ->tooltip(fn (SupportTicket $record) => $record->subject),
                TextColumn::make('author.name')
                    ->label('Customer')
                    ->sortable(),
                TextColumn::make('category.name')
                    ->badge()
                    ->color(fn (SupportTicket $record) => $record->category?->color ?? 'gray'),
                TextColumn::make('priority')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->sortable()
                    ->dateTimeTooltip()
                    ->since(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (SupportTicket $record): string => ViewSupportTicket::getUrl(['record' => $record])),
                AssignToMeAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

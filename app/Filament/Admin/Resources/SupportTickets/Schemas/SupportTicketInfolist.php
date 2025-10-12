<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTickets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupportTicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Ticket Information')
                    ->columns()
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('ticket_number')
                            ->label('Ticket Number')
                            ->weight('bold')
                            ->size('lg'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('priority')
                            ->badge(),
                        TextEntry::make('category.name')
                            ->label('Category')
                            ->badge()
                            ->color(fn ($record) => $record->category?->color ?? 'gray'),
                        TextEntry::make('author.name')
                            ->label('Submitted By'),
                        TextEntry::make('author.email')
                            ->label('Email')
                            ->copyable(),
                        TextEntry::make('assignedTo.name')
                            ->label('Assigned To')
                            ->placeholder('Unassigned')
                            ->badge()
                            ->color('info'),
                        TextEntry::make('order.reference_id')
                            ->label('Related Order')
                            ->placeholder('No related order')
                            ->url(fn ($record): ?string => $record->order ? route('filament.admin.resources.orders.view', ['record' => $record->order]) : null),
                    ]),

                Section::make('Details')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('subject')
                            ->weight('bold')
                            ->size('lg')
                            ->columnSpanFull(),
                        TextEntry::make('description')
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Section::make('External Integration')
                    ->columns()
                    ->columnSpanFull()
                    ->visible(fn ($record) => $record?->isExternal())
                    ->schema([
                        TextEntry::make('external_driver')
                            ->label('External System')
                            ->badge(),
                        TextEntry::make('external_id')
                            ->label('External ID')
                            ->copyable(),
                    ]),

                Section::make('Timestamps')
                    ->columns()
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime()
                            ->since(),
                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime()
                            ->since(),
                        TextEntry::make('resolved_at')
                            ->label('Resolved At')
                            ->dateTime()
                            ->placeholder('Not resolved'),
                        TextEntry::make('closed_at')
                            ->label('Closed At')
                            ->dateTime()
                            ->placeholder('Not closed'),
                    ]),
            ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTickets\Schemas;

use App\Filament\Admin\Resources\Notes\RelationManagers\NotesRelationManager;
use App\Filament\Admin\Resources\Orders\Schemas\OrderInfolist;
use App\Filament\Admin\Resources\SupportTickets\Pages\ViewSupportTicket;
use App\Filament\Admin\Resources\SupportTickets\RelationManagers\CommentsRelationManager;
use App\Filament\Admin\Resources\SupportTickets\RelationManagers\FilesRelationManager;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\SupportTicket;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SupportTicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Tabs::make()
                    ->columnSpanFull()
                    ->contained(false)
                    ->persistTabInQueryString()
                    ->tabs([
                        Tabs\Tab::make('Ticket Information')
                            ->badgeColor(fn (SupportTicket $ticket) => $ticket->status->getColor())
                            ->badge(fn (SupportTicket $ticket) => $ticket->status->getLabel())
                            ->icon(Heroicon::OutlinedLifebuoy)
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
                                            ->url(fn (SupportTicket $record): string => UserResource::getUrl('edit', ['record' => $record->created_by]), shouldOpenInNewTab: true)
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
                                            ->label('Opened At')
                                            ->dateTime()
                                            ->since(),
                                        TextEntry::make('updated_at')
                                            ->label('Last Updated At')
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
                            ]),

                        Tabs\Tab::make('Replies')
                            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                            ->badge(fn (SupportTicket $record): string => (string) $record->comments->count())
                            ->badgeColor('info')
                            ->schema([
                                Livewire::make(CommentsRelationManager::class, fn (SupportTicket $record): array => [
                                    'ownerRecord' => $record,
                                    'pageClass' => ViewSupportTicket::class,
                                ]),
                            ]),

                        Tabs\Tab::make('Attachments')
                            ->icon(Heroicon::OutlinedPhoto)
                            ->badge(fn (SupportTicket $record): string => (string) $record->files->count())
                            ->badgeColor('gray')
                            ->schema([
                                Livewire::make(FilesRelationManager::class, fn (SupportTicket $record): array => [
                                    'ownerRecord' => $record,
                                    'pageClass' => ViewSupportTicket::class,
                                ]),
                            ]),

                        Tabs\Tab::make('Order Information')
                            ->icon(Heroicon::OutlinedCurrencyDollar)
                            ->visible(fn ($record): bool => $record->order_id !== null)
                            ->schema(fn (SupportTicket $record): array => [
                                ...OrderInfolist::configure(Schema::make()->record($record->order))->getComponents(),
                            ]),

                        Tabs\Tab::make('Notes')
                            ->icon(Heroicon::OutlinedDocument)
                            ->badge(fn (SupportTicket $record): string => (string) $record->notes->count())
                            ->badgeColor('gray')
                            ->schema([
                                Livewire::make(NotesRelationManager::class, fn (SupportTicket $record): array => [
                                    'ownerRecord' => $record,
                                    'pageClass' => ViewSupportTicket::class,
                                ]),
                            ]),
                    ]),
            ]);
    }
}

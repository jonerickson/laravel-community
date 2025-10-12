<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTickets\Schemas;

use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use Filament\Forms;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SupportTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Ticket Information')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        Forms\Components\TextInput::make('ticket_number')
                            ->label('Ticket Number')
                            ->disabled(),
                        Forms\Components\TextInput::make('author.name')
                            ->label('Submitted By')
                            ->disabled(),
                        Forms\Components\TextInput::make('author.email')
                            ->label('Email')
                            ->disabled(),
                        Forms\Components\Select::make('support_ticket_category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->required()
                            ->preload()
                            ->searchable(),
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigned Agent')
                            ->relationship('assignedTo', 'name', fn (Builder $query) => $query->role(['super-admin', 'support-agent']))
                            ->searchable()
                            ->preload()
                            ->placeholder('Unassigned'),
                        Forms\Components\Select::make('order_id')
                            ->label('Related Order')
                            ->relationship('order', 'reference_id')
                            ->searchable()
                            ->preload()
                            ->placeholder('No related order'),
                    ]),

                Section::make('Details')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\RichEditor::make('description')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Status & Priority')
                    ->columnSpanFull()
                    ->schema([
                        Fieldset::make('Current State')
                            ->columns()
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->options(SupportTicketStatus::class)
                                    ->required()
                                    ->native(false),
                                Forms\Components\Select::make('priority')
                                    ->options(SupportTicketPriority::class)
                                    ->required()
                                    ->native(false),
                            ]),
                    ]),

                Section::make('External Integration')
                    ->columns()
                    ->visible(fn ($record) => $record?->isExternal())
                    ->schema([
                        Forms\Components\TextInput::make('external_driver')
                            ->label('External System')
                            ->disabled(),
                        Forms\Components\TextInput::make('external_id')
                            ->label('External ID')
                            ->disabled(),
                    ]),
            ]);
    }
}

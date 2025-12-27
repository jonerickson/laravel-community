<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTickets\Schemas;

use App\Enums\Role;
use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use App\Models\User;
use Filament\Forms;
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
                        Forms\Components\Select::make('created_by')
                            ->searchable()
                            ->required()
                            ->preload()
                            ->relationship('author', 'name')
                            ->label('Submitted By'),
                        Forms\Components\Select::make('support_ticket_category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->required()
                            ->preload()
                            ->searchable(),
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigned Agent')
                            ->relationship('assignedTo', 'name', fn (Builder|User $query) => $query->role([Role::Administrator, Role::SupportAgent]))
                            ->searchable()
                            ->preload()
                            ->placeholder('Unassigned'),
                    ]),
                Section::make('Details')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\RichEditor::make('description')
                            ->maxLength(65535)
                            ->required()
                            ->columnSpanFull(),
                    ]),
                Section::make('Status & Priority')
                    ->columnSpanFull()
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

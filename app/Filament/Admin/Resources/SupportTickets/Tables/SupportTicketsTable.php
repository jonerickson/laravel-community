<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTickets\Tables;

use App\Enums\Role;
use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use App\Filament\Admin\Resources\SupportTickets\Actions\AssignToMeAction;
use App\Filament\Admin\Resources\SupportTickets\Actions\CloseAction;
use App\Filament\Admin\Resources\SupportTickets\Actions\ReopenAction;
use App\Filament\Admin\Resources\SupportTickets\Actions\ResolveAction;
use App\Filament\Admin\Resources\SupportTickets\Actions\UnassignAction;
use App\Models\SupportTicket;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class SupportTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateDescription('No support tickets found.')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')
                    ->label('Ticket #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->subject),
                Tables\Columns\TextColumn::make('author.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->badge()
                    ->color(fn ($record) => $record->category?->color ?? 'gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigned To')
                    ->placeholder('Unassigned')
                    ->sortable(),
                Tables\Columns\TextColumn::make('comments_count')
                    ->label('Replies')
                    ->counts('comments')
                    ->sortable(),
                Tables\Columns\TextColumn::make('files_count')
                    ->label('Attachments')
                    ->counts('files')
                    ->sortable(),
                Tables\Columns\TextColumn::make('notes_count')
                    ->label('Notes')
                    ->counts('notes')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at->format('M d, Y g:i A')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->preload()
                    ->options(SupportTicketStatus::class),
                Tables\Filters\SelectFilter::make('priority')
                    ->multiple()
                    ->preload()
                    ->options(SupportTicketPriority::class),
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('Assigned Agent')
                    ->relationship('assignedTo', 'name', fn (Builder|User $query) => $query->role([Role::Administrator, Role::SupportAgent]))
                    ->multiple()
                    ->preload()
                    ->searchable(),
                Tables\Filters\Filter::make('assigned_to_me')
                    ->label('Assigned to Me')
                    ->query(fn (Builder|SupportTicket $query) => $query->where('assigned_to', Auth::id()))
                    ->toggle()
                    ->default(),
                Tables\Filters\Filter::make('unassigned')
                    ->label('Unassigned Only')
                    ->query(fn (Builder|SupportTicket $query) => $query->unassigned())
                    ->toggle(),
                Tables\Filters\Filter::make('active')
                    ->label('Active Only')
                    ->query(fn (Builder|SupportTicket $query) => $query->active())
                    ->toggle()
                    ->default(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ActionGroup::make([
                    AssignToMeAction::make(),
                    UnassignAction::make(),
                    ResolveAction::make(),
                    CloseAction::make(),
                    ReopenAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkAction::make('assign')
                    ->label('Assign Selected')
                    ->icon('heroicon-o-user-plus')
                    ->color('info')
                    ->schema([
                        Forms\Components\Select::make('assigned_to')
                            ->label('Agent')
                            ->options(fn () => User::role([Role::Administrator, Role::SupportAgent])->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        $records->each(function (SupportTicket $record) use ($data): void {
                            $agent = User::find($data['assigned_to']);
                            if ($agent) {
                                $record->assign($agent);
                            }
                        });
                    }),
                BulkAction::make('change_status')
                    ->label('Change Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options(SupportTicketStatus::class)
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        $records->each(function (SupportTicket $record) use ($data): void {
                            $status = data_get($data, 'status');

                            if ($record->canTransitionTo($status)) {
                                $record->updateStatus($status);

                                if ($status === SupportTicketStatus::Resolved) {
                                    $record->update(['resolved_at' => now()]);
                                } elseif ($status === SupportTicketStatus::Closed) {
                                    $record->update(['closed_at' => now()]);
                                }
                            }
                        });
                    }),
                BulkAction::make('change_priority')
                    ->label('Change Priority')
                    ->icon('heroicon-o-flag')
                    ->color('warning')
                    ->schema([
                        Forms\Components\Select::make('priority')
                            ->options(SupportTicketPriority::class)
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        $records->each(function (SupportTicket $record) use ($data): void {
                            $record->update(['priority' => $data['priority']]);
                        });
                    }),
                BulkAction::make('delete')
                    ->label('Delete Selected')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $records->each->delete();
                    }),
            ]);
    }
}

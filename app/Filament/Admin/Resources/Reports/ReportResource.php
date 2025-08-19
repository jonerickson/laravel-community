<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Reports;

use App\Filament\Admin\Resources\Reports\Pages\ListReports;
use App\Filament\Admin\Resources\Reports\Pages\ViewReport;
use App\Models\Report;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $navigationLabel = 'Reports';

    protected static string|UnitEnum|null $navigationGroup = 'Forums';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Report Details')
                    ->schema([
                        Forms\Components\TextInput::make('reporter.name')
                            ->label('Reporter')
                            ->disabled(),
                        Forms\Components\TextInput::make('reportable_type')
                            ->label('Content Type')
                            ->disabled(),
                        Forms\Components\TextInput::make('reportable_id')
                            ->label('Content ID')
                            ->disabled(),
                        Forms\Components\Select::make('reason')
                            ->options([
                                'spam' => 'Spam',
                                'harassment' => 'Harassment',
                                'inappropriate_content' => 'Inappropriate Content',
                                'abuse' => 'Abuse',
                                'impersonation' => 'Impersonation',
                                'false_information' => 'False Information',
                                'other' => 'Other',
                            ])
                            ->disabled(),
                        Forms\Components\Textarea::make('additional_info')
                            ->label('Additional Information')
                            ->disabled()
                            ->rows(3),
                    ])
                    ->columns(2),

                Section::make('Review')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->rows(3),
                        Forms\Components\Hidden::make('reviewed_by')
                            ->default(fn () => auth()->id()),
                        Forms\Components\Hidden::make('reviewed_at')
                            ->default(fn () => now()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reporter.name')
                    ->label('Reporter')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reportable_type')
                    ->label('Content Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => class_basename($state)),
                Tables\Columns\TextColumn::make('reason')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'spam' => 'warning',
                        'harassment', 'abuse', 'inappropriate_content' => 'danger',
                        'impersonation' => 'warning',
                        'false_information' => 'info',
                        'other' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Reported At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label('Reviewed By')
                    ->placeholder('Not reviewed'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('reason')
                    ->options([
                        'spam' => 'Spam',
                        'harassment' => 'Harassment',
                        'inappropriate_content' => 'Inappropriate Content',
                        'abuse' => 'Abuse',
                        'impersonation' => 'Impersonation',
                        'false_information' => 'False Information',
                        'other' => 'Other',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function (Report $record) {
                        $record->update([
                            'status' => 'approved',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                    })
                    ->visible(fn (Report $record): bool => $record->status === 'pending'),
                Action::make('reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(function (Report $record) {
                        $record->update([
                            'status' => 'rejected',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                    })
                    ->visible(fn (Report $record): bool => $record->status === 'pending'),
            ])
            ->toolbarActions([
                BulkAction::make('approve')
                    ->label('Approve Selected')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function ($records) {
                        $records->each(function (Report $record) {
                            $record->update([
                                'status' => 'approved',
                                'reviewed_by' => auth()->id(),
                                'reviewed_at' => now(),
                            ]);
                        });
                    }),
                BulkAction::make('reject')
                    ->label('Reject Selected')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(function ($records) {
                        $records->each(function (Report $record) {
                            $record->update([
                                'status' => 'rejected',
                                'reviewed_by' => auth()->id(),
                                'reviewed_at' => now(),
                            ]);
                        });
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReports::route('/'),
            // 'view' => ViewReport::route('/{record}'),
        ];
    }
}

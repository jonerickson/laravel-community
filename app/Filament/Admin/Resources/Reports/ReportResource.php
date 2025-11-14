<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Reports;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Filament\Admin\Resources\Reports\Actions\ApproveAction;
use App\Filament\Admin\Resources\Reports\Actions\DetailsAction;
use App\Filament\Admin\Resources\Reports\Actions\RejectAction;
use App\Filament\Admin\Resources\Reports\Actions\ViewContentAction;
use App\Filament\Admin\Resources\Reports\Pages\ListReports;
use App\Models\Report;
use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Override;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getNavigationBadge(): ?string
    {
        return Number::format(static::getModel()::where('status', ReportStatus::Pending)->count());
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    #[Override]
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
                            ->options(ReportReason::class)
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
                            ->options(ReportStatus::class)
                            ->required(),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->rows(3),
                        Forms\Components\Hidden::make('reviewed_by')
                            ->default(fn () => Auth::id()),
                        Forms\Components\Hidden::make('reviewed_at')
                            ->default(fn (): CarbonInterface => now()),
                    ]),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateDescription('There are no reports to display.')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('author.name')
                    ->label('Reporter')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reportable_type')
                    ->label('Content Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => class_basename($state)),
                Tables\Columns\TextColumn::make('reason')
                    ->badge()
                    ->searchable(['reason', 'additional_info']),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Reported')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label('Reviewed By')
                    ->placeholder('Not Reviewed'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->options(ReportStatus::class),
                Tables\Filters\SelectFilter::make('reason')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->options(ReportReason::class),
            ])
            ->recordActions([
                DetailsAction::make(),
                ViewContentAction::make(),
                ApproveAction::make(),
                RejectAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkAction::make('approve')
                    ->label('Approve Selected')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function ($records): void {
                        $records->each(function (Report $record): void {
                            $record->approve(Auth::user());
                        });
                    }),
                BulkAction::make('reject')
                    ->label('Reject Selected')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(function ($records): void {
                        $records->each(function (Report $record): void {
                            $record->reject(Auth::user());
                        });
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReports::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

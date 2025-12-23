<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Payouts\Schemas;

use App\Enums\CommissionStatus;
use App\Enums\PayoutDriver;
use App\Filament\Admin\Resources\Orders\Pages\ViewOrder;
use App\Models\Commission;
use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;

class PayoutForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payout Details')
                    ->columnSpanFull()
                    ->columns(1)
                    ->schema([
                        Select::make('seller_id')
                            ->required()
                            ->helperText('Select the recipient of the payout. The recipient must have payouts enabled to be selected.')
                            ->preload()
                            ->relationship('seller', 'name', modifyQueryUsing: fn (Builder|User $query): Builder => $query->where('payouts_enabled', true))
                            ->live()
                            ->searchable(),
                        CheckboxList::make('commissions')
                            ->visibleOn('create')
                            ->required()
                            ->helperText('Select the commissions that will be paid in this payout.')
                            ->searchable()
                            ->live()
                            ->disabled(fn (Get $get): bool => blank($get('seller_id')))
                            ->afterStateUpdated(fn (Set $set, $state): mixed => $set('amount', Commission::findMany($state)->pluck('amount')->sum()))
                            ->options(fn (Get $get) => Commission::query()
                                ->where('status', CommissionStatus::Pending)
                                ->where('seller_id', $get('seller_id'))
                                ->latest()
                                ->get()
                                ->mapWithKeys(fn (Commission $commission): array => [$commission->getKey() => Number::currency($commission->amount)]))
                            ->descriptions(fn (Get $get) => Commission::query()
                                ->where('status', CommissionStatus::Pending)
                                ->where('seller_id', $get('seller_id'))
                                ->latest()
                                ->get()
                                ->mapWithKeys(fn (Commission $commission): array => [$commission->getKey() => new HtmlString(sprintf("<div>Commission on order <a href='%s' class='underline' target='_blank'>#%s</a> placed %s with %s totaling %s.</div>", ViewOrder::getUrl(['record' => $commission->order]), $commission->order->reference_id, $commission->created_at->diffForHumans(), $commission->order->items->pluck('name')->implode(', '), Number::currency($commission->order->amount)))])
                            ),
                        Radio::make('payout_method')
                            ->required()
                            ->helperText('Select the payout method that will be used.')
                            ->label('Payout Method')
                            ->options(PayoutDriver::class)
                            ->default(PayoutDriver::Stripe),
                        RichEditor::make('notes')
                            ->placeholder('Optional notes to add to the payout.')
                            ->label('Notes'),
                        TextInput::make('amount')
                            ->helperText('The total payout amount. This will be auto-calculated when selecting the commissions to pay out. You may also manually adjust this if needed.')
                            ->label('Total')
                            ->required()
                            ->numeric()
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->prefix('$')
                            ->suffix('USD')
                            ->step(0.01)
                            ->rules(['gt:0']),
                    ]),
                Section::make('Failure Details')
                    ->visibleOn('view')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('failure_message')
                            ->hiddenLabel()
                            ->placeholder('No Failure Message')
                            ->html(),
                    ]),
            ]);
    }
}

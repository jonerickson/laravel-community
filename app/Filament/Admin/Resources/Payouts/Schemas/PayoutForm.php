<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Payouts\Schemas;

use App\Enums\PayoutStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;

class PayoutForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(2)
                    ->components([
                        Section::make('Payout Details')
                            ->columns()
                            ->schema([
                                Select::make('user_id')
                                    ->label('Seller')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                TextInput::make('amount')
                                    ->label('Amount')
                                    ->required()
                                    ->numeric()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->prefix('$')
                                    ->suffix('USD')
                                    ->step(0.01)
                                    ->minValue(0),
                                Select::make('payout_method')
                                    ->label('Payout Method')
                                    ->options([
                                        'PayPal' => 'PayPal',
                                        'Bank Transfer' => 'Bank Transfer',
                                        'Stripe' => 'Stripe Connect',
                                        'Check' => 'Check',
                                        'Other' => 'Other',
                                    ])
                                    ->searchable()
                                    ->columnSpanFull(),
                                TextInput::make('external_payout_id')
                                    ->label('External Payout ID')
                                    ->helperText('Reference ID from payment processor.')
                                    ->columnSpanFull(),
                                Textarea::make('notes')
                                    ->label('Notes')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Group::make()
                    ->components([
                        Section::make('Status')
                            ->schema([
                                Select::make('status')
                                    ->label('Status')
                                    ->options(PayoutStatus::class)
                                    ->default(PayoutStatus::Pending)
                                    ->live()
                                    ->required(),
                                DateTimePicker::make('processed_at')
                                    ->label('Processed At')
                                    ->visible(fn (Get $get): bool => in_array($get('status'), [PayoutStatus::Completed->value, PayoutStatus::Failed->value])),
                                Select::make('processed_by')
                                    ->label('Processed By')
                                    ->relationship('processor', 'name')
                                    ->default(Auth::id())
                                    ->visible(fn (Get $get): bool => in_array($get('status'), [PayoutStatus::Completed->value, PayoutStatus::Failed->value]))
                                    ->disabled()
                                    ->dehydrated(),
                            ]),
                    ]),
            ]);
    }
}

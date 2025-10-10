<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Discounts\Schemas;

use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(2)
                    ->components([
                        Section::make('Discount Information')
                            ->columnSpanFull()
                            ->columns()
                            ->schema([
                                TextInput::make('code')
                                    ->columnSpanFull()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->rules(['alpha_dash'])
                                    ->helperText('A unique code for this discount.')
                                    ->default(fn (Get $get) => Discount::make([
                                        'type' => $get('type') ?? DiscountType::PromoCode,
                                    ])->generateCode()),
                                Radio::make('type')
                                    ->live()
                                    ->required()
                                    ->columnSpanFull()
                                    ->options(DiscountType::class)
                                    ->default(DiscountType::PromoCode),
                                Radio::make('discount_type')
                                    ->live()
                                    ->label('Discount Type')
                                    ->required()
                                    ->columnSpanFull()
                                    ->options(DiscountValueType::class)
                                    ->default(DiscountValueType::Percentage),
                                TextInput::make('value')
                                    ->columnSpanFull()
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix(fn (Get $get): string => $get('discount_type') === DiscountValueType::Percentage ? '%' : 'cents')
                                    ->helperText(fn (Get $get): string => $get('discount_type') === DiscountValueType::Fixed ? 'Enter amount in cents (e.g., 1000 = $10.00)' : 'Enter percentage value (e.g., 25 = 25%)'),
                                TextInput::make('current_balance')
                                    ->columnSpanFull()
                                    ->visible(fn (Get $get): bool => $get('type') === DiscountType::GiftCard)
                                    ->label('Current Balance')
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('cents')
                                    ->helperText('The current balance for gift cards (in cents).')
                                    ->nullable(),
                            ]),
                        Section::make('Usage Limits')
                            ->columnSpanFull()
                            ->columns()
                            ->schema([
                                TextInput::make('max_uses')
                                    ->label('Maximum Uses')
                                    ->numeric()
                                    ->minValue(0)
                                    ->helperText('Maximum number of times this discount can be used. Leave empty for unlimited.')
                                    ->nullable(),
                                TextInput::make('min_order_amount')
                                    ->label('Minimum Order Amount')
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('cents')
                                    ->helperText('Minimum order amount required to use this discount (in cents). Leave empty for no minimum.')
                                    ->nullable(),
                            ]),
                    ]),
                Group::make()
                    ->components([
                        Section::make('Associations')
                            ->components([
                                Select::make('user_id')
                                    ->label('User')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Assign this discount to a specific user. Leave empty for general use.')
                                    ->nullable(),
                                Select::make('product_id')
                                    ->label('Product')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Associate this discount with a product (e.g., gift card product). When the product is purchase, this discount will be issued to the customer.')
                                    ->nullable(),
                            ]),
                        Section::make('Dates')
                            ->components([
                                DateTimePicker::make('expires_at')
                                    ->label('Expires At')
                                    ->helperText('When this discount expires. Leave empty for no expiration.')
                                    ->nullable(),
                                DateTimePicker::make('activated_at')
                                    ->label('Activated At')
                                    ->helperText('When this discount becomes active. Leave empty to activate immediately.')
                                    ->nullable(),
                            ]),
                        Section::make('Statistics')
                            ->visibleOn('edit')
                            ->components([
                                Placeholder::make('times_used')
                                    ->label('Times Used')
                                    ->content(fn ($record): string => (string) $record->times_used),
                                Placeholder::make('created_at')
                                    ->label('Created At')
                                    ->content(fn ($record): string => $record->created_at?->format('M j, Y g:i A') ?? '—'),
                                Placeholder::make('updated_at')
                                    ->label('Last Updated')
                                    ->content(fn ($record): string => $record->updated_at?->format('M j, Y g:i A') ?? '—'),
                            ]),
                    ]),
            ]);
    }
}

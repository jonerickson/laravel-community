<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Discounts\Schemas;

use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use App\Services\DiscountService;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

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
                                    ->default(fn (Get $get): string => new Discount([
                                        'type' => $get('type') ?? DiscountType::PromoCode,
                                    ])->generateCode()),
                                Radio::make('type')
                                    ->live()
                                    ->required()
                                    ->columnSpanFull()
                                    ->options(DiscountType::class)
                                    ->default(DiscountType::PromoCode)
                                    ->afterStateUpdated(fn (Set $set, DiscountType $state): mixed => $set('code', app(DiscountService::class)->generateUniqueCode($state))),
                                Radio::make('discount_type')
                                    ->live()
                                    ->label('Discount Type')
                                    ->required()
                                    ->columnSpanFull()
                                    ->options(DiscountValueType::class)
                                    ->rules([
                                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get): void {
                                            if (DiscountValueType::tryFrom($value) === DiscountValueType::Percentage && $get('type') === DiscountType::GiftCard) {
                                                $fail('The :attribute field should be fixed when selecting a gift card.');
                                            }
                                        },
                                    ])
                                    ->default(DiscountValueType::Percentage),
                                TextInput::make('value')
                                    ->columnSpanFull()
                                    ->required()
                                    ->numeric()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->prefix(fn (Get $get): string => $get('discount_type') === DiscountValueType::Percentage ? '' : '$')
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->suffix(fn (Get $get): string => $get('discount_type') === DiscountValueType::Percentage ? '%' : 'USD')
                                    ->helperText('The initial value of the discount.'),
                                TextInput::make('current_balance')
                                    ->columnSpanFull()
                                    ->visible(fn (Get $get): bool => $get('type') === DiscountType::GiftCard)
                                    ->label('Current Balance')
                                    ->numeric()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->prefix('$')
                                    ->suffix('USD')
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->helperText('The current balance of the gift card.')
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
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->prefix('$')
                                    ->suffix('USD')
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->helperText('The minimum order amount required to use this discount. Leave empty for no minimum.')
                                    ->nullable(),
                            ]),
                    ]),
                Group::make()
                    ->components([
                        Section::make('Details')
                            ->visibleOn('edit')
                            ->components([
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->since()
                                    ->dateTimeTooltip(),
                                TextEntry::make('updated_at')
                                    ->label('Updated')
                                    ->since()
                                    ->dateTimeTooltip(),
                                TextEntry::make('reference_id')
                                    ->label('Reference ID')
                                    ->copyable(),
                                TextEntry::make('times_used')
                                    ->label('Times Used'),
                            ]),
                        Section::make('Associations')
                            ->components([
                                Select::make('user_id')
                                    ->label('User')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Assign this discount to a specific user. Only the designated user can apply the discount. Leave empty for general use.')
                                    ->nullable(),
                                Select::make('product_id')
                                    ->label('Product')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Associate this discount with a product (e.g., gift card product). When the product is purchased, a matching discount will be created and issued to the customer.')
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
                    ]),
            ]);
    }
}

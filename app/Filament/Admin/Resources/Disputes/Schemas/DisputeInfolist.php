<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Disputes\Schemas;

use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Dispute;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;

class DisputeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dispute information')
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        TextEntry::make('external_dispute_id')
                            ->label('Dispute ID')
                            ->copyable(),
                        TextEntry::make('external_charge_id')
                            ->label('Charge ID')
                            ->copyable(),
                        TextEntry::make('external_payment_intent_id')
                            ->label('Payment Intent ID')
                            ->copyable()
                            ->default(new HtmlString('&mdash;')),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('reason'),
                        TextEntry::make('amount')
                            ->formatStateUsing(fn (int $state, Dispute $record): string => Number::currency($state / 100, strtoupper($record->currency))),
                        TextEntry::make('currency')
                            ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                        TextEntry::make('evidence_due_by')
                            ->label('Evidence due by')
                            ->dateTime()
                            ->default(new HtmlString('&mdash;')),
                        IconEntry::make('is_charge_refundable')
                            ->label('Charge refundable')
                            ->boolean(),
                        TextEntry::make('network_reason_code')
                            ->label('Network reason code')
                            ->default(new HtmlString('&mdash;')),
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->since()
                            ->dateTimeTooltip(),
                        TextEntry::make('updated_at')
                            ->label('Updated')
                            ->since()
                            ->dateTimeTooltip(),
                    ]),
                Section::make('Order information')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('order.reference_id')
                            ->label('Order number')
                            ->url(fn (Dispute $record): string => OrderResource::getUrl('view', ['record' => $record->order_id]), shouldOpenInNewTab: true),
                        TextEntry::make('order.status')
                            ->label('Order status')
                            ->badge(),
                        TextEntry::make('order.amount_due')
                            ->label('Order amount')
                            ->money(),
                    ]),
                Section::make('User information')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Name')
                            ->url(fn (Dispute $record): string => UserResource::getUrl('edit', ['record' => $record->user_id]), shouldOpenInNewTab: true),
                        TextEntry::make('user.email')
                            ->label('Email'),
                    ]),
                Section::make('Raw metadata')
                    ->columnSpanFull()
                    ->schema([
                        KeyValueEntry::make('metadata')
                            ->label(false)
                            ->default([]),
                    ]),
            ]);
    }
}

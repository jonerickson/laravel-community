<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WarningConsequences\Schemas;

use App\Enums\WarningConsequenceType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WarningConsequenceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Consequence Configuration')
                    ->description('Configure warning point thresholds and their consequences.')
                    ->columnSpanFull()
                    ->columns(1)
                    ->schema([
                        Select::make('type')
                            ->label('Consequence Type')
                            ->options(WarningConsequenceType::class)
                            ->required()
                            ->helperText('The action that will be applied when the threshold is reached.'),
                        TextInput::make('threshold')
                            ->label('Point Threshold')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Number of warning points required to trigger this consequence.'),
                        TextInput::make('duration_days')
                            ->label('Duration (Days)')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Number of days this consequence remains active.'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

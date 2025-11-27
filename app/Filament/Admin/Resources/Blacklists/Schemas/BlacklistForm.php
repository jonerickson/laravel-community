<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Blacklists\Schemas;

use App\Enums\FilterType;
use App\Models\Fingerprint;
use App\Models\User;
use Exception;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Throwable;

class BlacklistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Blacklist Information')
                    ->columnSpanFull()
                    ->columns(1)
                    ->schema([
                        Radio::make('filter')
                            ->required()
                            ->live()
                            ->default(FilterType::String)
                            ->options(FilterType::class),
                        TextInput::make('content')
                            ->visible(fn (Get $get): bool => $get('filter') === FilterType::String)
                            ->maxLength(255)
                            ->live()
                            ->helperText('The content that should be prevented. Multiple non-regex items should be separated by a comma. This can be an email address, profanity, or a regex pattern to match multiple items.')
                            ->required(),
                        TextInput::make('content')
                            ->label('IP Address')
                            ->visible(fn (Get $get): bool => $get('filter') === FilterType::IpAddress)
                            ->maxLength(255)
                            ->ip()
                            ->helperText('The IP address that should be blocked.')
                            ->required(),
                        MorphToSelect::make('resource')
                            ->label(fn (Get $get): string => $get('filter')->getLabel())
                            ->visible(fn (Get $get): bool => in_array($get('filter'), [FilterType::Fingerprint, FilterType::User]))
                            ->contained(false)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->types([
                                MorphToSelect\Type::make(User::class)
                                    ->titleAttribute('name'),
                                MorphToSelect\Type::make(Fingerprint::class)
                                    ->titleAttribute('fingerprint_id'),
                            ]),
                        Textarea::make('description')
                            ->maxLength(65535)
                            ->helperText('An optional description of the blacklist entry. This is public facing and will be shown to the user.')
                            ->nullable(),
                        Select::make('warning_id')
                            ->relationship('warning', 'name')
                            ->helperText('Issue the specified warning when a user meets this blacklist entry requirement.')
                            ->preload()
                            ->searchable()
                            ->nullable(),
                        Toggle::make('is_regex')
                            ->visible(fn (Get $get): bool => $get('filter') === FilterType::String)
                            ->helperText('The content above is a regex pattern.')
                            ->live()
                            ->required(),
                    ]),
                Section::make('Regex Test & Example')
                    ->columnSpanFull()
                    ->visible(fn (Get $get): mixed => $get('is_regex'))
                    ->schema([
                        TextEntry::make('regex_example')
                            ->helperText('Matches any word with a character in range A - Z. Case insensitive.')
                            ->label('Example')
                            ->copyable()
                            ->state('/([A-Z])\w+/i'),
                        TextInput::make('regex_test')
                            ->label('Subject')
                            ->helperText('Enter some text to determine if the regex pattern above matches the provided subject.')
                            ->live()
                            ->dehydrated(false),
                        TextEntry::make('regex_test_result')
                            ->label('Result')
                            ->placeholder('Enter A Subject')
                            ->getStateUsing(function (Get $get): ?string {
                                $subject = $get('regex_test');
                                $pattern = $get('content');
                                $isRegex = $get('is_regex');

                                if (! $subject) {
                                    return null;
                                }

                                if (! $pattern || ! $isRegex) {
                                    return htmlspecialchars($subject);
                                }

                                try {
                                    $matchCount = preg_match_all($pattern, $subject, $matches, PREG_OFFSET_CAPTURE);

                                    if ($matchCount === false) {
                                        throw new Exception('No matches.');
                                    }

                                    if ($matchCount === 0 || empty($matches[0])) {
                                        return htmlspecialchars($subject);
                                    }

                                    $result = '';
                                    $lastOffset = 0;

                                    foreach ($matches[0] as $match) {
                                        $matchText = $match[0];
                                        $matchOffset = $match[1];

                                        if ($matchOffset > $lastOffset) {
                                            $result .= htmlspecialchars(substr($subject, $lastOffset, $matchOffset - $lastOffset));
                                        }

                                        $result .= '<mark class="bg-yellow-200 dark:bg-yellow-800 px-1 rounded">'.htmlspecialchars($matchText).'</mark>';

                                        $lastOffset = $matchOffset + strlen($matchText);
                                    }

                                    if ($lastOffset < strlen($subject)) {
                                        $result .= htmlspecialchars(substr($subject, $lastOffset));
                                    }

                                    return $result;
                                } catch (Throwable) {
                                    return htmlspecialchars($subject).' <span class="text-red-500 text-sm">(Invalid regex pattern)</span>';
                                }
                            })
                            ->html()
                            ->dehydrated(false),
                    ]),
            ]);
    }
}

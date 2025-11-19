<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Webhooks\Schemas;

use App\Enums\WebhookMethod;
use App\Events\SubscriptionCreated;
use App\Events\SubscriptionDeleted;
use App\Facades\ExpressionLanguage;
use Closure;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Throwable;

class WebhookForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Webhook Information')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('event')
                            ->helperText('The event that will trigger the webhook to be sent.')
                            ->options([
                                SubscriptionCreated::class => 'Subscription Created',
                                SubscriptionDeleted::class => 'Subscription Deleted',
                            ])
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('url')
                            ->helperText('The URL the webhook will be sent to.')
                            ->label('URL')
                            ->url()
                            ->required()
                            ->columnSpanFull(),
                        Select::make('method')
                            ->helperText('The HTTP method that the webhook will be sent with.')
                            ->default(WebhookMethod::Post)
                            ->options(WebhookMethod::class)
                            ->required()
                            ->columnSpanFull(),
                        KeyValue::make('headers')
                            ->helperText('Any additiional headers that should be added to the HTTP request.')
                            ->keyLabel('Header'),
                        CodeEditor::make('payload')
                            ->helperText(new HtmlString(<<<'HTML'
The webhook body that will be sent. You may use <a href="https://symfony.com/doc/current/components/expression_language.html" target="_blank" class="underline">Symfony Expression Language</a> to compose the JSON payload. All expressions must be prefixed with <span class="font-mono bg-gray-200 px-1">expr:</span>
HTML
                            ))
                            ->language(CodeEditor\Enums\Language::Json)
                            ->required()
                            ->json()
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT))
                            ->dehydrateStateUsing(fn ($state) => Str::isJson($state) ? json_decode($state, true) : $state)
                            ->columnSpanFull()
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail): void {
                                    $payload = Str::isJson($value) ? json_decode($value, true) : null;

                                    if (is_null($payload)) {
                                        return;
                                    }

                                    try {
                                        ExpressionLanguage::lint($payload);
                                    } catch (Throwable $throwable) {
                                        $fail($throwable->getMessage());
                                    }
                                },
                            ]),
                        TextInput::make('secret')
                            ->password()
                            ->revealable()
                            ->required()
                            ->default(Str::random(32))
                            ->helperText('The secret the webhook will be signed with using the Signature header.'),
                    ]),
            ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Webhooks\Schemas;

use App\Enums\HttpMethod;
use App\Enums\RenderEngine;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderRefunded;
use App\Events\PaymentSucceeded;
use App\Events\PostCreated;
use App\Events\PostDeleted;
use App\Events\PostUpdated;
use App\Events\SubscriptionCreated;
use App\Events\SubscriptionDeleted;
use App\Events\SubscriptionUpdated;
use App\Events\TopicCreated;
use App\Events\TopicDeleted;
use App\Events\TopicUpdated;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserIntegrationCreated;
use App\Events\UserIntegrationDeleted;
use App\Events\UserUpdated;
use App\Facades\ExpressionLanguage;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserIntegration;
use App\Models\Webhook;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
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
                            ->options(self::events())
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
                            ->default(HttpMethod::Post)
                            ->options(HttpMethod::class)
                            ->required()
                            ->columnSpanFull(),
                        KeyValue::make('headers')
                            ->helperText('Any additiional headers that should be added to the HTTP request.')
                            ->keyLabel('Header'),
                        Radio::make('render')
                            ->live()
                            ->label('Renderer')
                            ->helperText('The rendering engine that will be used to generate the payload.')
                            ->default(RenderEngine::ExpressionLanguage)
                            ->options(RenderEngine::class)
                            ->required(),
                        CodeEditor::make('payload_text')
                            ->hintActions([
                                self::seeExampleObjectAction(),
                                fn (?Webhook $record, Get $get): Action => self::testPayloadAction($record, $get('event')),
                            ])
                            ->label('Payload')
                            ->visible(fn (Get $get): bool => $get('render') === RenderEngine::Blade)
                            ->language(CodeEditor\Enums\Language::Html)
                            ->helperText(new HtmlString(<<<'HTML'
 The webhook body that will be sent. You may use <a href="https://laravel.com/docs/12.x/blade" target="_blank" class="underline">Blade Templating Engine</a> to compose the JSON payload. The final output should be JSON encoded.
 HTML
                            ))
                            ->required(),
                        CodeEditor::make('payload_json')
                            ->hintActions([
                                self::seeExampleObjectAction(),
                                fn (?Webhook $record, Get $get): Action => self::testPayloadAction($record, $get('event')),
                            ])
                            ->label('Payload')
                            ->visible(fn (Get $get): bool => $get('render') === RenderEngine::ExpressionLanguage)
                            ->helperText(new HtmlString(<<<'HTML'
 The webhook body that will be sent. You may use <a href="https://symfony.com/doc/current/components/expression_language.html" target="_blank" class="underline">Symfony Expression Language</a> to compose the JSON payload. All expressions must be prefixed with <span class="font-mono bg-gray-200 dark:bg-gray-700 px-1">expr:</span>
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

    protected static function testPayloadAction(?Webhook $webhook = null, ?string $event = null): Action
    {
        return Action::make('test_payload')
            ->visible(fn (): bool => filled($event) && filled($webhook))
            ->icon(Heroicon::OutlinedCommandLine)
            ->label('Test Payload')
            ->slideOver()
            ->modalCancelActionLabel('Close')
            ->modalSubmitAction(false)
            ->modalDescription('Generate and test the webhook payload using the example object selected below.')
            ->schema([
                Select::make('model')
                    ->label('Object')
                    ->helperText('Select an example object to use to view the schema.')
                    ->live()
                    ->options(fn (Get $get): array => self::getExampleOptionsForEvent($event))
                    ->afterStateUpdated(function (Set $set, Get $get, $state) use ($webhook, $event): void {
                        if (blank($state)) {
                            return;
                        }

                        $set('payload', self::generatePayloadForEvent($webhook, $event, self::generateStateForEvent($event, $state)));
                    }),
                CodeEditor::make('payload')
                    ->helperText('The payload that will be generated when the webhook sends.')
                    ->language(CodeEditor\Enums\Language::Json),
            ]);
    }

    protected static function seeExampleObjectAction(): Action
    {
        return Action::make('example_object')
            ->label('See Example Object')
            ->icon(Heroicon::OutlinedCodeBracket)
            ->slideOver()
            ->modalCancelActionLabel('Close')
            ->modalSubmitAction(false)
            ->modalDescription('The data below represents the event object schema passed to the webhook as it is sent.')
            ->schema([
                Select::make('event')
                    ->helperText('The event triggering the webhook.')
                    ->live()
                    ->options(self::events()),
                Select::make('model')
                    ->label('Object')
                    ->helperText('Select an example object to use to view the schema.')
                    ->disabled(fn (Get $get): bool => blank($get('event')))
                    ->live()
                    ->placeholder(fn (Get $get): string => filled($get('event')) ? 'Select an object' : 'Select an event from above')
                    ->options(function (Get $get): array {
                        if (blank($event = $get('event'))) {
                            return [];
                        }

                        return self::getExampleOptionsForEvent($event);
                    })
                    ->afterStateUpdated(function (Set $set, Get $get, $state): void {
                        if (blank($state) || blank($event = $get('event'))) {
                            return;
                        }

                        $set('schema', self::generateSchemaForEvent($event, self::generateStateForEvent($event, $state)));
                    }),
                CodeEditor::make('schema')
                    ->helperText('The schema that will be passed to the webhook for the selected event.')
                    ->language(CodeEditor\Enums\Language::Json),
            ]);
    }

    protected static function generatePayloadForEvent(Webhook $webhook, string $event, array $state): string
    {
        if (blank($state)) {
            return 'Unable to generate state for the example object.';
        }

        if (($webhook->render === RenderEngine::Blade && blank($webhook->payload_text)) || ($webhook->render === RenderEngine::ExpressionLanguage && blank($webhook->payload_json))) {
            return 'The provided webhook payload is empty. Please build the payload before testing it.';
        }

        if ($webhook->render === RenderEngine::ExpressionLanguage) {
            $payload = ExpressionLanguage::evaluate($webhook->payload_json, [
                'event' => app($event, $state),
            ]);
        } else {
            $payload = json_decode(Blade::render($webhook->payload_text, ['event' => app($event, $state)]), true);
        }

        return json_encode($payload, JSON_PRETTY_PRINT);
    }

    protected static function generateSchemaForEvent(string $event, array $state): string
    {
        if (blank($state)) {
            return '';
        }

        return json_encode(app($event, $state), JSON_PRETTY_PRINT);
    }

    protected static function generateStateForEvent(string $event, int $modelId): array
    {
        return match ($event) {
            OrderCreated::class, OrderCancelled::class, OrderRefunded::class, PaymentSucceeded::class => ['order' => Order::query()->with(['items.price.product', 'discounts', 'user.integrations'])->findOrFail($modelId)],
            PostCreated::class, PostUpdated::class, PostDeleted::class => ['post' => Post::query()->findOrFail($modelId)],
            SubscriptionCreated::class, SubscriptionUpdated::class, SubscriptionDeleted::class => ['user' => Auth::user(), 'product' => Product::query()->findOrFail($modelId)],
            UserCreated::class, UserUpdated::class, UserDeleted::class => ['user' => User::query()->with(['integrations', 'pendingReports'])->findOrFail($modelId)],
            TopicCreated::class, TopicUpdated::class, TopicDeleted::class => ['topic' => Topic::query()->findOrFail($modelId)],
            UserIntegrationCreated::class, UserIntegrationDeleted::class => ['integration' => UserIntegration::query()->with(['user'])->findOrFail($modelId)],
        };
    }

    protected static function getExampleOptionsForEvent(string $event): array
    {
        return match ($event) {
            OrderCreated::class, PaymentSucceeded::class => Order::query()->completed()->latest()->limit(50)->get()->mapWithKeys(fn (Order $order): array => [$order->getKey() => $order->getLabel()])->toArray(),
            OrderCancelled::class => Order::query()->cancelled()->latest()->limit(50)->get()->mapWithKeys(fn (Order $order): array => [$order->getKey() => $order->getLabel()])->toArray(),
            OrderRefunded::class, => Order::query()->refunded()->latest()->limit(50)->get()->mapWithKeys(fn (Order $order): array => [$order->getKey() => $order->getLabel()])->toArray(),
            SubscriptionCreated::class, SubscriptionUpdated::class, SubscriptionDeleted::class => Product::query()->subscriptions()->orderBy('name')->limit(50)->get()->mapWithKeys(fn (Product $product): array => [$product->getKey() => $product->name])->toArray(),
            PostCreated::class, PostUpdated::class, PostDeleted::class => Post::query()->orderBy('title')->limit(50)->get()->mapWithKeys(fn (Post $post): array => [$post->getKey() => $post->title])->toArray(),
            TopicCreated::class, TopicUpdated::class, TopicDeleted::class => Topic::query()->orderBy('title')->limit(50)->get()->mapWithKeys(fn (Topic $topic): array => [$topic->getKey() => $topic->title])->toArray(),
            UserCreated::class, UserUpdated::class, UserDeleted::class => User::query()->orderBy('name')->limit(50)->get()->mapWithKeys(fn (User $user): array => [$user->getKey() => $user->name])->toArray(),
            UserIntegrationCreated::class, UserIntegrationDeleted::class => UserIntegration::query()->orderBy('provider_name')->limit(50)->get()->mapWithKeys(fn (UserIntegration $integration): array => [$integration->getKey() => sprintf('%s: %s (%s)', Str::title($integration->provider), $integration->provider_name, $integration->provider_id)])->toArray(),
        };
    }

    protected static function events(): array
    {
        return [
            OrderCreated::class => 'Order Created',
            OrderCancelled::class => 'Order Cancelled',
            OrderRefunded::class => 'Order Refunded',
            PaymentSucceeded::class => 'Payment Succeeded',
            PostCreated::class => 'Post Created',
            PostUpdated::class => 'Post Updated',
            PostDeleted::class => 'Post Deleted',
            SubscriptionCreated::class => 'Subscription Created',
            SubscriptionUpdated::class => 'Subscription Updated',
            SubscriptionDeleted::class => 'Subscription Deleted',
            TopicCreated::class => 'Topic Created',
            TopicUpdated::class => 'Topic Updated',
            TopicDeleted::class => 'Topic Deleted',
            UserCreated::class => 'User Created',
            UserUpdated::class => 'User Updated',
            UserDeleted::class => 'User Deleted',
            UserIntegrationCreated::class => 'User Integration Created',
            UserIntegrationDeleted::class => 'User Integration Deleted',
        ];
    }
}

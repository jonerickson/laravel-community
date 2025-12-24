<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Pages\Schemas;

use App\Filament\Admin\Resources\Pages\PageResource;
use App\Models\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Phiki\Grammar\Grammar;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Section::make('Page Content')
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $context, $state, Set $set): void {
                                        if ($context === 'create') {
                                            $set('slug', Str::slug($state));
                                            $set('navigation_label', Str::title($state));
                                        }
                                    }),
                                TextInput::make('slug')
                                    ->disabledOn('edit')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->rules(['alpha_dash']),
                                Textarea::make('description')
                                    ->maxLength(65535)
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                        Section::make('HTML Content')
                            ->columnSpanFull()
                            ->headerActions([
                                Action::make('html_example')
                                    ->label('See HTML Example')
                                    ->color('gray')
                                    ->modalHeading('HTML Example')
                                    ->modalDescription('This example uses a mix of CSS for styling and the lightweight JavaScript package, Alpine.js, for some interactivity.')
                                    ->modalCancelActionLabel('Close')
                                    ->modalSubmitAction(false)
                                    ->schema([
                                        CodeEntry::make('html')
                                            ->hiddenLabel()
                                            ->copyable()
                                            ->helperText('Click to copy to clipboard.')
                                            ->getStateUsing(fn (): string => PageResource::defaultHtml())
                                            ->grammar(Grammar::Html),
                                    ]),
                            ])
                            ->schema([
                                CodeEditor::make('html_content')
                                    ->hiddenLabel()
                                    ->required()
                                    ->columnSpanFull()
                                    ->helperText(new HtmlString("The HTML content of the page. This application uses Tailwind CSS for styling. We suggest you use tailwind to style your content rather than building custom CSS. See the <a href='https://tailwindcss.com/' class='font-medium underline' target='_blank'>docs</a> for more. If a class is not available, you may need to incude it in your custom CSS."))
                                    ->language(CodeEditor\Enums\Language::Html),
                            ]),
                        Section::make('Custom Styles & Scripts')
                            ->columnSpanFull()
                            ->schema([
                                CodeEditor::make('css_content')
                                    ->label('CSS')
                                    ->columnSpanFull()
                                    ->helperText('Custom CSS styles for this page.')
                                    ->language(CodeEditor\Enums\Language::Css),
                                CodeEditor::make('js_content')
                                    ->label('JavaScript')
                                    ->columnSpanFull()
                                    ->helperText('Custom JavaScript for this page.')
                                    ->language(CodeEditor\Enums\Language::JavaScript),
                            ]),
                    ]),
                Group::make()
                    ->schema([
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
                                TextEntry::make('url')
                                    ->label('URL')
                                    ->getStateUsing(fn (Page $record): string => route('pages.show', $record->slug))
                                    ->copyable()
                                    ->suffixAction(fn (Page $record): Action => Action::make('open')
                                        ->url(route('pages.show', $record->slug), shouldOpenInNewTab: true)
                                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                                    ),
                            ]),
                        Section::make('Publishing')
                            ->schema([
                                Toggle::make('is_published')
                                    ->label('Published')
                                    ->default(false),
                                DateTimePicker::make('published_at')
                                    ->label('Publish Date'),
                            ]),
                        Section::make('Navigation')
                            ->schema([
                                Toggle::make('show_in_navigation')
                                    ->label('Show in Navigation')
                                    ->default(true)
                                    ->helperText('Display this page in the site navigation.'),
                                TextInput::make('navigation_label')
                                    ->label('Label')
                                    ->maxLength(255)
                                    ->helperText('Optional custom label for navigation. The title will be used if empty.'),
                                TextInput::make('navigation_order')
                                    ->label('Order')
                                    ->numeric()
                                    ->default(10)
                                    ->helperText('Order in which this page appears in navigation.'),
                            ]),
                    ]),
            ]);
    }
}

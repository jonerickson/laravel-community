<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Pages;

use App\Filament\Admin\Resources\Pages\Pages\CreatePage;
use App\Filament\Admin\Resources\Pages\Pages\EditPage;
use App\Filament\Admin\Resources\Pages\Pages\ListPages;
use App\Filament\Admin\Resources\Pages\Schemas\PageForm;
use App\Filament\Admin\Resources\Pages\Tables\PagesTable;
use App\Models\Page;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return PageForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return PagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }

    public static function defaultHtml(): string
    {
        return <<<'HTML'
<script src="https://unpkg.com/alpinejs" defer></script>

<div class="p-4" x-data="{
  show: false
}">
  <div id="hello-world" class="tracking-tight leading-8 text-xl font-bold">Hello, World!</div>
  <div x-on:click="show = ! show">Click Me</div>
  <div x-cloak x-show="show">Surprise</div>
</div>
HTML;
    }

    public static function defaultCss(): string
    {
        return <<<'CSS'
.container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 2rem;
}
CSS;
    }

    public static function defaultJavascript(): string
    {
        return <<<'JS'
console.log("Page loaded!");
JS;
    }
}

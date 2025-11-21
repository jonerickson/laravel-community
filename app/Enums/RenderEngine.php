<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

enum RenderEngine: string implements HasDescription, HasLabel
{
    case Blade = 'blade';
    case ExpressionLanguage = 'expression_language';

    public function getLabel(): string|Htmlable|null
    {
        return Str::of($this->value)->replace('_', ' ')->title()->toString();
    }

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            RenderEngine::Blade => new HtmlString('Use the Blade Templating Engine to render a payload.'),
            RenderEngine::ExpressionLanguage => new HtmlString('Use Symfony Expression Language to render a payload.')
        };
    }
}

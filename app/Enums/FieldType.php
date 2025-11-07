<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum FieldType: string implements HasLabel
{
    case Checkbox = 'checkbox';
    case Date = 'date';
    case DateTime = 'datetime';
    case Number = 'number';
    case Radio = 'radio';
    case RichText = 'rich_text';
    case Select = 'select';
    case Text = 'text';
    case Textarea = 'textarea';

    public function getLabel(): string
    {
        return Str::of($this->value)
            ->replace('_', ' ')
            ->title()
            ->__toString();
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Field;
use App\Rules\BlacklistRule;
use App\Rules\NoProfanity;
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

    public function getRules(Field $field): array
    {
        $rules = [];

        if ($field->is_required) {
            $rules[] = 'required';
        }

        return array_merge(match ($this) {
            FieldType::Text, FieldType::Textarea, FieldType::RichText => [new NoProfanity, new BlacklistRule],
            FieldType::Date, FieldType::DateTime => ['date'],
            FieldType::Number => ['numeric'],
            default => [],
        }, $rules);
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum WebhookMethod: string implements HasLabel
{
    case Get = 'get';
    case Post = 'post';

    public function getLabel(): string
    {
        return Str::title($this->value);
    }
}

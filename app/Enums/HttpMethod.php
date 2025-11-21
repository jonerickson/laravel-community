<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

enum HttpMethod: string implements HasLabel
{
    case Head = 'head';
    case Get = 'get';
    case Post = 'post';
    case Put = 'put';
    case Patch = 'patch';
    case Delete = 'delete';
    case Options = 'options';

    public function getLabel(): string
    {
        return Str::upper($this->value);
    }
}

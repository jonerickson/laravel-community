<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class EmailSettings extends Settings
{
    public ?string $welcome_email = null;

    public static function group(): string
    {
        return 'emails';
    }
}

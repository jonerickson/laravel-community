<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class DisputeSettings extends Settings
{
    /** @var array<int, string> */
    public array $dispute_actions = [];

    public static function group(): string
    {
        return 'disputes';
    }
}

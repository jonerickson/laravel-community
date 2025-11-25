<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class RegistrationSettings extends Settings
{
    public array $required_policy_ids = [];

    public static function group(): string
    {
        return 'registration';
    }
}

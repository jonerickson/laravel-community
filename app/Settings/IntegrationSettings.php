<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class IntegrationSettings extends Settings
{
    public bool $intercom_enabled = false;

    public ?string $intercom_app_id = null;

    public bool $intercom_auth_required = false;

    public ?string $intercom_secret_key = null;

    public static function group(): string
    {
        return 'integrations';
    }
}

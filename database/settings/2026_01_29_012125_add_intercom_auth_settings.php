<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('integrations.intercom_auth_required', false);
        $this->migrator->add('integrations.intercom_secret_key', null);
    }
};

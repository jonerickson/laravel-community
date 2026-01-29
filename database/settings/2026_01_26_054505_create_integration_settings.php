<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('integrations.intercom_enabled', false);
        $this->migrator->add('integrations.intercom_app_id', null);
    }
};

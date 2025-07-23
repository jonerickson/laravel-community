<?php

declare(strict_types=1);

namespace App\Enums;

enum AnnouncementType: string
{
    case Info = 'info';
    case Success = 'success';
    case Warning = 'warning';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Info => 'Info',
            self::Success => 'Success',
            self::Warning => 'Warning',
            self::Error => 'Error',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Info => 'info',
            self::Success => 'success',
            self::Warning => 'warning',
            self::Error => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Info => 'info',
            self::Success => 'check-circle',
            self::Warning => 'triangle-alert',
            self::Error => 'x-circle',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->toArray();
    }
}
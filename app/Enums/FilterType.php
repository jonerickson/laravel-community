<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum FilterType: string implements HasDescription, HasLabel
{
    case Fingerprint = 'fingerprint';
    case IpAddress = 'ip_address';
    case User = 'user';
    case String = 'string';

    public function getLabel(): string
    {
        return match ($this) {
            FilterType::Fingerprint => 'Fingerprint',
            FilterType::IpAddress => 'IP Address',
            FilterType::User => 'User',
            FilterType::String => 'String',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            FilterType::Fingerprint => 'Filter by a request fingerprint.',
            FilterType::IpAddress => 'Filter by an IP address.',
            FilterType::User => 'Filter by a specific user account.',
            FilterType::String => 'Filter using a string of text.',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ReportReason: string implements HasColor, HasIcon, HasLabel
{
    case Spam = 'spam';
    case Harassment = 'harassment';
    case InappropriateContent = 'inappropriate_content';
    case Abuse = 'abuse';
    case Impersonation = 'impersonation';
    case FalseInformation = 'false_information';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Spam => 'Spam',
            self::Harassment => 'Harassment',
            self::InappropriateContent => 'Inappropriate Content',
            self::Abuse => 'Abuse',
            self::Impersonation => 'Impersonation',
            self::FalseInformation => 'False Information',
            self::Other => 'Other',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Spam => 'warning',
            self::Harassment => 'danger',
            self::InappropriateContent => 'danger',
            self::Abuse => 'danger',
            self::Impersonation => 'warning',
            self::FalseInformation => 'info',
            self::Other => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Spam => 'heroicon-o-no-symbol',
            self::Harassment => 'heroicon-o-exclamation-triangle',
            self::InappropriateContent => 'heroicon-o-eye-slash',
            self::Abuse => 'heroicon-o-shield-exclamation',
            self::Impersonation => 'heroicon-o-identification',
            self::FalseInformation => 'heroicon-o-question-mark-circle',
            self::Other => 'heroicon-o-ellipsis-horizontal',
        };
    }
}

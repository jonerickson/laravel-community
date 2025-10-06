<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum WarningConsequenceType: string implements HasColor, HasIcon, HasLabel
{
    case None = 'none';
    case ModerateContent = 'moderate_content';
    case PostRestriction = 'post_restriction';
    case Ban = 'ban';

    public function getLabel(): string
    {
        return match ($this) {
            self::None => 'No Restrictions',
            self::ModerateContent => 'Moderate Content',
            self::PostRestriction => 'Post Restriction',
            self::Ban => 'Banned',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::None => 'success',
            self::ModerateContent => 'info',
            self::PostRestriction => 'warning',
            self::Ban => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::None => 'heroicon-o-check-circle',
            self::ModerateContent => 'heroicon-o-eye',
            self::PostRestriction => 'heroicon-o-no-symbol',
            self::Ban => 'heroicon-o-x-circle',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::None => 'There are no restrictions.',
            self::ModerateContent => 'Your posts require approval before being published.',
            self::PostRestriction => 'You cannot create posts or topics.',
            self::Ban => 'You are banned from accessing the website.',
        };
    }
}

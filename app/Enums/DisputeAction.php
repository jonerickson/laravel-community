<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

enum DisputeAction: string implements HasLabel
{
    case BlacklistUser = 'blacklist_user';
    case CancelSubscription = 'cancel_subscription';
    case FlagForReview = 'flag_for_review';
    case Nothing = 'nothing';

    public function getLabel(): string|Htmlable|null
    {
        return Str::of($this->value)
            ->replace('_', ' ')
            ->title()
            ->__toString();
    }
}

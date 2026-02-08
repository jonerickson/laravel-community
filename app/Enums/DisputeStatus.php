<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

enum DisputeStatus: string implements HasColor, HasLabel
{
    case WarningNeedsResponse = 'warning_needs_response';
    case WarningUnderReview = 'warning_under_review';
    case WarningClosed = 'warning_closed';
    case NeedsResponse = 'needs_response';
    case UnderReview = 'under_review';
    case Won = 'won';
    case Lost = 'lost';
    case ChargeRefunded = 'charge_refunded';

    public function getLabel(): string|Htmlable|null
    {
        return Str::of($this->value)
            ->replace('_', ' ')
            ->title()
            ->__toString();
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            DisputeStatus::NeedsResponse, DisputeStatus::Lost => 'danger',
            DisputeStatus::UnderReview, DisputeStatus::WarningNeedsResponse, DisputeStatus::WarningUnderReview => 'warning',
            DisputeStatus::Won => 'success',
            DisputeStatus::WarningClosed, DisputeStatus::ChargeRefunded => 'gray',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DiscountType: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Cancellation = 'cancellation';
    case GiftCard = 'gift_card';
    case PromoCode = 'promo_code';
    case Manual = 'manual';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cancellation => 'Cancellation Offer',
            self::GiftCard => 'Gift Card',
            self::PromoCode => 'Promo Code',
            self::Manual => 'Manual',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Cancellation => 'danger',
            self::GiftCard => 'success',
            self::PromoCode => 'warning',
            self::Manual => 'info',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Cancellation => 'heroicon-o-x-circle',
            self::GiftCard => 'heroicon-o-gift',
            self::PromoCode => 'heroicon-o-ticket',
            self::Manual => 'heroicon-o-pencil-square',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Cancellation => 'A discount that can only be applied when cancelling a subscription.',
            self::GiftCard => "A gift card purchased by a customer that can be redeemed for it's remaining balance. The order amount will be deducted from the remaining amount automatically after purchase.",
            self::PromoCode => 'A promotional code that can be shared and used by multiple customers.',
            self::Manual => 'A manual discount created by an administrator for a specific order.',
        };
    }

    public function canBeUsedAtCheckout(): bool
    {
        return match ($this) {
            self::Cancellation, self::Manual => false,
            default => true,
        };
    }
}

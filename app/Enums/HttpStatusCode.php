<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Symfony\Component\HttpFoundation\Response;

enum HttpStatusCode: string implements HasColor, HasLabel
{
    case Okay = '200';
    case Created = '201';
    case Accepted = '202';
    case NoContent = '204';
    case MovedPermanently = '301';
    case Found = '302';
    case SeeOther = '303';
    case BadRequest = '400';
    case Unauthorized = '401';
    case PaymentRequired = '402';
    case Forbidden = '403';
    case NotFound = '404';
    case MethodNotAllowed = '405';
    case NotAcceptable = '406';
    case Conflict = '409';
    case Unprocessable = '422';
    case Locked = '423';
    case ServerError = '500';
    case ServiceUnavailable = '503';

    public function getLabel(): string
    {
        $phrase = Response::$statusTexts[(int) $this->value] ?? 'Unknown';

        return sprintf('%s %s', $this->value, $phrase);
    }

    public function getColor(): string|array|null
    {
        $value = (int) $this->value;

        return match (true) {
            $value >= 200 && $value < 300 => 'success',
            $value >= 300 && $value < 400 => 'warning',
            $value >= 400 => 'danger',
            default => 'info',
        };
    }
}

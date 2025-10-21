<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Administrator = 'super-admin';
    case Moderator = 'moderator';
    case User = 'user';
    case Guest = 'guest';
    case SupportAgent = 'support-agent';
}

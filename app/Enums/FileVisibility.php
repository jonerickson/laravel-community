<?php

declare(strict_types=1);

namespace App\Enums;

enum FileVisibility: string
{
    case Public = 'public';
    case Private = 'private';
}

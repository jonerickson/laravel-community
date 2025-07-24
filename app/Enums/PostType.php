<?php

declare(strict_types=1);

namespace App\Enums;

enum PostType: string
{
    case Blog = 'blog';
    case Forum = 'forum';

    public function label(): string
    {
        return match ($this) {
            self::Blog => 'Blog Post',
            self::Forum => 'Forum Post',
        };
    }
}

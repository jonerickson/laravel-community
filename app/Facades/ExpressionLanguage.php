<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed evaluate(\Symfony\Component\ExpressionLanguage\Expression|array|string $expression, array $values = [])
 * @method static string|bool lint(\Symfony\Component\ExpressionLanguage\Expression|array|string $expression, array $values = [])
 *
 * @see \App\Managers\ExpressionLanguageManager
 */
class ExpressionLanguage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'expression-language';
    }
}

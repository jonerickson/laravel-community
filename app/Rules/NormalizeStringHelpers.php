<?php

declare(strict_types=1);

namespace App\Rules;

use Illuminate\Support\Str;

trait NormalizeStringHelpers
{
    protected function normalize(string $string): string
    {
        if (Str::isAscii($string)) {
            return Str::lower($string);
        }

        $result = transliterator_transliterate(
            'Any-Latin; Latin-ASCII; Lower()',
            $string
        );

        if ($result === false) {
            return $string;
        }

        return $result;
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;

class EmailParserService
{
    public const string DELIMITER = 'Please reply above this line.';

    public static function parse(?string $text = null): string
    {
        if (! $text) {
            return '';
        }

        $text = self::normalize($text);
        $text = self::cutAtDelimiter($text);
        $text = self::stripReplyHeaders($text);
        $text = self::stripQuotedText($text);
        $text = self::stripSignatures($text);
        $text = self::convertLineBreaks($text);

        return trim($text);
    }

    protected static function normalize(string $text): string
    {
        // Normalize line endings & encoding artifacts
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Decode quoted-printable leftovers if any
        if (str_contains($text, '=E2')) {
            return quoted_printable_decode($text);
        }

        return $text;
    }

    protected static function cutAtDelimiter(string $text): string
    {
        return Str::contains($text, static::DELIMITER)
            ? Str::before($text, static::DELIMITER)
            : $text;
    }

    protected static function stripReplyHeaders(string $text): string
    {
        // Gmail / Apple Mail
        $text = preg_replace(
            '/^On\s.+?wrote:\s*\n+/ms',
            '',
            $text
        );

        // Outlook
        return preg_replace(
            '/^From:.*?\nSent:.*?\nTo:.*?\nSubject:.*?\n+/ms',
            '',
            (string) $text
        );
    }

    protected static function stripQuotedText(string $text): string
    {
        $lines = explode("\n", $text);

        $lines = array_filter($lines, fn ($line): bool => ! Str::startsWith(trim((string) $line), '>'));

        return implode("\n", $lines);
    }

    protected static function stripSignatures(string $text): string
    {
        // Standard signature delimiter
        $parts = preg_split('/\n--\s*\n/', $text);

        return $parts[0];
    }

    protected static function convertLineBreaks(string $text): string
    {
        return str_replace("\n", '<br>', $text);
    }
}

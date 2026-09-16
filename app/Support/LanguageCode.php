<?php

namespace App\Support;

final class LanguageCode
{
    /**
     * Base language of a code: 'en-gb' → 'en', 'es_AR' → 'es', 'NL' → 'nl'.
     */
    public static function base(string $code): string
    {
        $code = strtolower(trim($code));

        return preg_match('/^([a-z]{2,3})(?:[-_][a-z0-9]+)*$/', $code, $matches) ? $matches[1] : $code;
    }

    /**
     * Whether two codes are variants of the same language (e.g. en-gb and en-us).
     */
    public static function isSameLanguage(string $first, string $second): bool
    {
        return self::base($first) === self::base($second);
    }
}

<?php

declare(strict_types=1);

namespace App\Util;

class StringUtil
{
    public static function isFirstLevelLowercase(string $string): bool
    {
        if (mb_strlen($string) < 1) {
            return false;
        }

        $lowercaseChar = mb_strtolower(mb_substr($string, 0, 1));

        return $string[0] === $lowercaseChar;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Util;

use App\Util\StringUtil;
use PHPUnit\Framework\TestCase;

class StringUtilTest extends TestCase
{
    public function testIsFirstLevelLowercaseSuccess(): void
    {
        self::assertTrue(StringUtil::isFirstLevelLowercase('string'));
    }

    public function testIsFirstLevelLowercaseFail(): void
    {
        self::assertFalse(StringUtil::isFirstLevelLowercase('String'));
    }

    public function testIsFirstLevelLowercaseFalseIfEmpty(): void
    {
        self::assertFalse(StringUtil::isFirstLevelLowercase(''));
    }
}
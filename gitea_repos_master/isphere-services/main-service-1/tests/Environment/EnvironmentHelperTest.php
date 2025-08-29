<?php

declare(strict_types=1);

namespace Tests\Environment;

use App\Environment\EnvironmentHelper;
use PHPUnit\Framework\TestCase;

class EnvironmentHelperTest extends TestCase
{
    public function testArrayParser(): void
    {
        putenv('SECTION_0_PARAM1=value1');
        putenv('SECTION_0_PARAM2=value2');
        putenv('SECTION_1_PARAM1=value3');
        putenv('SECTION_1_PARAM2=value4');

        $environmentHelper = new EnvironmentHelper();

        self::assertSame(
            [
                [
                    'PARAM1' => 'value1',
                    'PARAM2' => 'value2',
                ],
                [
                    'PARAM1' => 'value3',
                    'PARAM2' => 'value4',
                ],
            ],
            $environmentHelper->getArray('SECTION')
        );
    }
}

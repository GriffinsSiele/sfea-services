<?php

declare(strict_types=1);

namespace App\Tests\Component\Bulk\Parser;

use App\Component\Bulk\Parser\XLSXParser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class XLSXParserTest extends KernelTestCase
{
    public function testCase(): void
    {
        $filename = \realpath(__DIR__.'/../../../../var/Test_1_ISPHERE_results 2.xlsx');

        self::bootKernel();

        /** @var XLSXParser $parser */
        $parser = self::getContainer()->get(XLSXParser::class);

        $result = $parser->parse($filename, '');

        self::assertCount(1001, $result);
        self::assertCount(4, $result[0]);
    }
}

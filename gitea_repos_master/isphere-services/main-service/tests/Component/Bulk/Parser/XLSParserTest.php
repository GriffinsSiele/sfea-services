<?php

declare(strict_types=1);

namespace App\Tests\Component\Bulk\Parser;

use App\Component\Bulk\Parser\XLSXParser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class XLSParserTest extends KernelTestCase
{
    public function testCase(): void
    {
        $filename = \realpath(__DIR__.'/../../../../var/ресо л.xls');

        self::bootKernel();

        /** @var XLSXParser $parser */
        $parser = self::getContainer()->get(XLSXParser::class);

        $result = $parser->parse($filename, '');

        self::assertCount(521, $result);
        self::assertCount(3, $result[0]);
    }
}

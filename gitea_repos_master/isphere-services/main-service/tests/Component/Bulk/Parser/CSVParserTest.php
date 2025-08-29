<?php

declare(strict_types=1);

namespace App\Tests\Component\Bulk\Parser;

use App\Component\Bulk\Parser\CSVParser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CSVParserTest extends KernelTestCase
{
    /**
     * @dataProvider delimiterDataProvider
     */
    public function testCase(string $delimiter): void
    {
        self::bootKernel();

        /** @var CSVParser $parser */
        $parser = self::getContainer()->get(CSVParser::class);

        $filename = \tempnam(\sys_get_temp_dir(), 'test_csv_delimiter.csv');

        for ($i = 0; $i < 100; ++$i) {
            \file_put_contents($filename, "a{$delimiter}b{$delimiter}c{$delimiter}\n", \FILE_APPEND);
        }

        try {
            $result = $parser->parse($filename, '');

            self::assertCount(100, $result);
            self::assertCount(3, $result[0]);
        } finally {
            \unlink($filename);
        }
    }

    public function delimiterDataProvider(): iterable
    {
        yield [','];
        yield [';'];
        yield ["\t"];
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckIPController;
use App\Model\IPReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class ShodanTest extends AbstractPluginTest
{
    public function testIP(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckIPController::NAME)
                ->addSource('shodan')
                ->addIp(
                    (new IPReq())
                        ->setIp('78.140.221.69')
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('shodan'));

        $source = $response->getSourceByCheckType('shodan');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(2, $records);

        $firstRecord = $records[0];

        self::assertSame(
            'RU',
            $firstRecord->getFieldValueByName('country_code'),
        );

        self::assertSame(
            'Russian Federation',
            $firstRecord->getFieldValueByName('country'),
        );

        self::assertSame(
            'Moscow',
            $firstRecord->getFieldValueByName('city'),
        );

        self::assertSame(
            '[{"coords":[55.75222,37.61556],"text":""}]',
            $firstRecord->getFieldValueByName('Location'),
        );

        self::assertSame(
            'JSC Severen-Telecom',
            $firstRecord->getFieldValueByName('organization'),
        );

        self::assertSame(
            'Enterprise Cloud Ltd.',
            $firstRecord->getFieldValueByName('provider'),
        );

        self::assertSame(
            'AS48096',
            $firstRecord->getFieldValueByName('asn'),
        );

        self::assertSame(
            'api.i-sphere.ru',
            $firstRecord->getFieldValueByName('hostnames'),
        );

        self::assertSame(
            '80,443',
            $firstRecord->getFieldValueByName('ports'),
        );

        self::assertSame(
            'eol-product',
            $firstRecord->getFieldValueByName('tags'),
        );

        self::assertSame(
            'ip',
            $firstRecord->getFieldValueByName('recordtype'),
        );

        $secondRecord = $records[1];

        self::assertSame(
            '443',
            $secondRecord->getFieldValueByName('port'),
        );

        self::assertSame(
            'tcp',
            $secondRecord->getFieldValueByName('transport'),
        );

        self::assertSame(
            'https',
            $secondRecord->getFieldValueByName('service'),
        );

        self::assertSame(
            'nginx',
            $secondRecord->getFieldValueByName('product'),
        );

        self::assertSame(
            '1.14.0',
            $secondRecord->getFieldValueByName('version'),
        );

        self::assertSame(
            'eol-product',
            $secondRecord->getFieldValueByName('tags'),
        );

        self::assertSame(
            'service',
            $secondRecord->getFieldValueByName('recordtype'),
        );
    }
}

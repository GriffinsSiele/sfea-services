<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckIPController;
use App\Model\IPReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class CensysTest extends AbstractPluginTest
{
    public function testIP(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckIPController::NAME)
                ->addSource('censys')
                ->addIp(
                    (new IPReq())
                        ->setIp('78.140.221.69')
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('censys'));

        $source = $response->getSourceByCheckType('censys');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(4, $records);

        $firstRecord = $records[0];

        self::assertSame(
            'RU',
            $firstRecord->getFieldValueByName('country_code'),
        );

        self::assertSame(
            'Russia',
            $firstRecord->getFieldValueByName('country'),
        );

        self::assertSame(
            'Moscow',
            $firstRecord->getFieldValueByName('province'),
        );

        self::assertSame(
            'Moscow',
            $firstRecord->getFieldValueByName('city'),
        );

        self::assertSame(
            'Europe/Moscow',
            $firstRecord->getFieldValueByName('timezone'),
        );

        self::assertSame(
            '[{"coords":[55.75222,37.61556],"text":"Europe\/Moscow"}]',
            $firstRecord->getFieldValueByName('Location'),
        );

        self::assertSame(
            '48096',
            $firstRecord->getFieldValueByName('asn'),
        );

        self::assertSame(
            'ITGRAD',
            $firstRecord->getFieldValueByName('organization'),
        );

        self::assertSame(
            'infosfera.ru,www.reputax.ru,i-sphere.ru,www.i-sphere.ru,api.i-sphere.ru,www.infosfera.ru,reputax.ru',
            $firstRecord->getFieldValueByName('hostnames'),
        );

        self::assertSame(
            'ip',
            $firstRecord->getFieldValueByName('recordtype'),
        );

        $secondRecord = $records[1];

        self::assertSame(
            '22',
            $secondRecord->getFieldValueByName('port'),
        );

        self::assertSame(
            'SSH',
            $secondRecord->getFieldValueByName('service'),
        );

        self::assertSame(
            'TCP',
            $secondRecord->getFieldValueByName('transport'),
        );

        self::assertSame(
            'service',
            $secondRecord->getFieldValueByName('recordtype'),
        );

        $thirdRecord = $records[2];

        self::assertSame(
            '80',
            $thirdRecord->getFieldValueByName('port'),
        );

        self::assertSame(
            'HTTP',
            $thirdRecord->getFieldValueByName('service'),
        );

        self::assertSame(
            'TCP',
            $thirdRecord->getFieldValueByName('transport'),
        );

        self::assertSame(
            'service',
            $thirdRecord->getFieldValueByName('recordtype'),
        );

        $fourthRecord = $records[3];

        self::assertSame(
            '443',
            $fourthRecord->getFieldValueByName('port'),
        );

        self::assertSame(
            'HTTP',
            $fourthRecord->getFieldValueByName('service'),
        );

        self::assertSame(
            'TCP',
            $fourthRecord->getFieldValueByName('transport'),
        );

        self::assertSame(
            'service',
            $fourthRecord->getFieldValueByName('recordtype'),
        );
    }
}

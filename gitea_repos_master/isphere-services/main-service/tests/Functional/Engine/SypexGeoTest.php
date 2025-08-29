<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckIPController;
use App\Model\IPReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class SypexGeoTest extends AbstractPluginTest
{
    public function testIP(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckIPController::NAME)
                ->addSource('sypexgeo')
                ->addIp(
                    (new IPReq())
                        ->setIp('78.140.221.69')
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('sypexgeo'));

        $source = $response->getSourceByCheckType('sypexgeo');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(1, $records);

        $record = $records[0];

        self::assertSame(
            'RU',
            $record->getFieldValueByName('country_code'),
        );

        self::assertSame(
            'Россия',
            $record->getFieldValueByName('country'),
        );

        self::assertSame(
            'Москва',
            $record->getFieldValueByName('region'),
        );

        self::assertSame(
            'Москва',
            $record->getFieldValueByName('city'),
        );

        self::assertSame(
            '[{"coords":[55.75222,37.61556],"text":"Москва"}]',
            $record->getFieldValueByName('Location'),
        );
    }
}

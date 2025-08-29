<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckPhoneController;
use App\Model\PhoneReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class RosSvyazTest extends AbstractPluginTest
{
    public function testPhone(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckPhoneController::NAME)
                ->addSource('rossvyaz')
                ->addPhone(
                    (new PhoneReq())
                        ->setPhone('79772776278'),
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('rossvyaz_phone'));

        $source = $response->getSourceByCheckType('rossvyaz_phone');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(1, $records);

        $record = $records[0];

        self::assertSame(
            '79772776278',
            $record->getFieldValueByName('PhoneNumber'),
        );

        self::assertSame(
            'Т2 Мобайл',
            $record->getFieldValueByName('PhoneOperator'),
        );

        self::assertSame(
            'г. Москва',
            $record->getFieldValueByName('PhoneRegion'),
        );

        self::assertSame(
            '77',
            $record->getFieldValueByName('PhoneRegionCode'),
        );

        self::assertSame(
            'Мобильный',
            $record->getFieldValueByName('PhoneStandart'),
        );
    }
}

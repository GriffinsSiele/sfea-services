<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckPhoneController;
use App\Model\PhoneReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class PochtaTest extends AbstractPluginTest
{
    public function testPhone(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckPhoneController::NAME)
                ->addSource('pochta')
                ->addPhone(
                    (new PhoneReq())
                        ->setPhone('79772776278'),
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('pochta_phone'));

        $source = $response->getSourceByCheckType('pochta_phone');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(1, $records);

        $record = $records[0];

        self::assertSame(
            '79772776278',
            $record->getFieldValueByName('Phone'),
        );

        self::assertSame(
            'Российская Федерация, обл Тульская, р-н Заокский, д Венюково',
            $record->getFieldValueByName('Address'),
        );

        self::assertSame(
            '301020',
            $record->getFieldValueByName('Index'),
        );

        self::assertSame(
            'Тульская',
            $record->getFieldValueByName('Region'),
        );

        self::assertSame(
            'Венюково',
            $record->getFieldValueByName('Settlement'),
        );

        self::assertSame(
            'Найден',
            $record->getFieldValueByName('Result'),
        );

        self::assertSame(
            'FOUND',
            $record->getFieldValueByName('ResultCode'),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckPhoneController;
use App\Model\PhoneReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class SMSCTest extends AbstractPluginTest
{
    public function testPhone(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckPhoneController::NAME)
                ->addSource('smsc')
                ->addPhone(
                    (new PhoneReq())
                        ->setPhone('79772776278'),
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('smsc_phone'));

        $source = $response->getSourceByCheckType('smsc_phone');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(1, $records);

        $record = $records[0];

        self::assertSame(
            '79772776278',
            $record->getFieldValueByName('PhoneNumber'),
        );

        self::assertSame(
            '1',
            $record->getFieldValueByName('HLRStatus'),
        );

        self::assertSame(
            'Доступен',
            $record->getFieldValueByName('HLRStatusText'),
        );

        self::assertSame(
            'Tele2',
            $record->getFieldValueByName('Operator'),
        );

        self::assertSame(
            'Russia',
            $record->getFieldValueByName('Country'),
        );

        self::assertSame(
            '25020', // @todo почему это у smsc imsi?
            $record->getFieldValueByName('IMSI'),
        );
    }
}

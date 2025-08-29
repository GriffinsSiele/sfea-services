<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckPhoneController;
use App\Model\PhoneReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class HLRTest extends AbstractPluginTest
{
    public function testPhone(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckPhoneController::NAME)
                ->addSource('hlr')
                ->addPhone(
                    (new PhoneReq())
                        ->setPhone('79772776278'),
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('hlr_phone'));

        $source = $response->getSourceByCheckType('hlr_phone');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(1, $records);

        $record = $records[0];

        self::assertSame(
            '79772776278',
            $record->getFieldValueByName('PhoneNumber'),
        );

        self::assertSame(
            'Доступен',
            $record->getFieldValueByName('HLRStatus'),
        );
    }
}

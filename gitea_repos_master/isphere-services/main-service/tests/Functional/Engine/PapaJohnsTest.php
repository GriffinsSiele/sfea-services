<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckPhoneController;
use App\Model\PhoneReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class PapaJohnsTest extends AbstractPluginTest
{
    public function testPhone(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckPhoneController::NAME)
                ->addSource('papajohns')
                ->addPhone(
                    (new PhoneReq())
                        ->setPhone('79165112801'),
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('papajohns_phone'));

        $source = $response->getSourceByCheckType('papajohns_phone');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(1, $records);

        $record = $records[0];

        self::assertSame(
            '79165112801',
            $record->getFieldValueByName('phone'),
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

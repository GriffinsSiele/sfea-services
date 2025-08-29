<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckPhoneController;
use App\Model\PhoneReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class GetContactTest extends AbstractPluginTest
{
    public function testPhone(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckPhoneController::NAME)
                ->addSource('getcontact')
                ->addPhone(
                    (new PhoneReq())
                        ->setPhone('79772776278'),
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('getcontact_phone'));

        $source = $response->getSourceByCheckType('getcontact_phone');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(1, $records);

        $record = $records[0];

        self::assertSame(
            'Сергей Коденцов',
            $record->getFieldValueByName('Name'),
        );

        self::assertSame(
            'Нет',
            $record->getFieldValueByName('IsUser'),
        );

        self::assertSame(
            '35',
            $record->getFieldValueByName('TagsCount'),
        );

        self::assertSame(
            '0',
            $record->getFieldValueByName('DeletedTagsCount'),
        );
    }
}

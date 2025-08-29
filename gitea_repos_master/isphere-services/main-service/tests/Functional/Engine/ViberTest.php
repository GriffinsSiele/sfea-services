<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckPhoneController;
use App\Model\PhoneReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class ViberTest extends AbstractPluginTest
{
    public function testPhone(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckPhoneController::NAME)
                ->addSource('viber')
                ->addPhone(
                    (new PhoneReq())
                        ->setPhone('79527077034'),
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('viber_phone'));

        $source = $response->getSourceByCheckType('viber_phone');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(1, $records);

        $record = $records[0];

        self::assertSame(
            '79527077034',
            $record->getFieldValueByName('phone'),
        );

        self::assertSame(
            'Владимир',
            \preg_replace('~[^а-яё]+~ui', '', $record->getFieldValueByName('name')),
        );

        self::assertTrue(
            $record->hasFieldByName('Photo')
        );
    }
}

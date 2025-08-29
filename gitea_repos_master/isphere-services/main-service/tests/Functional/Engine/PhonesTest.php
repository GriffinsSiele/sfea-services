<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckPhoneController;
use App\Model\PhoneReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class PhonesTest extends AbstractPluginTest
{
    public function testPhone(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckPhoneController::NAME)
                ->addSource('phones')
                ->addPhone(
                    (new PhoneReq())
                        ->setPhone('79036628984'),
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('phones_phone'));

        $source = $response->getSourceByCheckType('phones_phone');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertGreaterThan(0, \count($records));

        foreach ($records as $record) {
            self::assertTrue($record->hasFieldByName('Phone'));
        }
    }
}

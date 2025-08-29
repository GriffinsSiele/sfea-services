<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckPhoneController;
use App\Model\PhoneReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class NamesTest extends AbstractPluginTest
{
    public function testPhone(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckPhoneController::NAME)
                ->addSource('names')
                ->addPhone(
                    (new PhoneReq())
                        ->setPhone('79038460804'),
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('names_phone'));

        $source = $response->getSourceByCheckType('names_phone');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertGreaterThan(0, \count($records));

        foreach ($records as $record) {
            self::assertTrue($record->hasFieldByName('FirstName'));
            self::assertTrue($record->hasFieldByName('LastName'));
            self::assertTrue($record->hasFieldByName('Name'));
        }
    }
}

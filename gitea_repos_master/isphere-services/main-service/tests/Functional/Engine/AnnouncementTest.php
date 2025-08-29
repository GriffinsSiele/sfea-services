<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckPhoneController;
use App\Model\PhoneReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class AnnouncementTest extends AbstractPluginTest
{
    public function testPhone(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckPhoneController::NAME)
                ->addSource('announcement')
                ->addPhone(
                    (new PhoneReq())
                        ->setPhone('79262265836'),
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('announcement_phone'));

        $source = $response->getSourceByCheckType('announcement_phone');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertGreaterThan(0, $records);

        foreach ($records as $record) {
            self::assertTrue($record->hasFieldByName('phone'));
            self::assertTrue($record->hasFieldByName('url'));
            self::assertTrue($record->hasFieldByName('cat'));
            self::assertTrue($record->hasFieldByName('subcat'));
            self::assertTrue($record->hasFieldByName('region'));
            self::assertTrue($record->hasFieldByName('city'));
            self::assertTrue($record->hasFieldByName('status'));
            self::assertTrue($record->hasFieldByName('contact_name'));
            self::assertTrue($record->hasFieldByName('operator'));
            self::assertTrue($record->hasFieldByName('operator_region'));
            self::assertTrue($record->hasFieldByName('date'));
            self::assertTrue($record->hasFieldByName('time'));
            self::assertTrue($record->hasFieldByName('title'));
            self::assertTrue($record->hasFieldByName('parameters'));
            self::assertTrue($record->hasFieldByName('text'));
            self::assertTrue($record->hasFieldByName('price'));
            self::assertTrue($record->hasFieldByName('longtitude'));
            self::assertTrue($record->hasFieldByName('latitude'));
            self::assertTrue($record->hasFieldByName('metro'));
        }
    }
}

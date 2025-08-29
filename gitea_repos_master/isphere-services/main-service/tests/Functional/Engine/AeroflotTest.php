<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckEmailController;
use App\Controller\CheckPhoneController;
use App\Model\EmailReq;
use App\Model\PhoneReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class AeroflotTest extends AbstractPluginTest
{
    public function testEmail(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckEmailController::NAME)
                ->addSource('aeroflot')
                ->addEmail(
                    (new EmailReq())
                        ->setEmail('sergeyvkz@gmail.com'),
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('aeroflot_email'));

        $source = $response->getSourceByCheckType('aeroflot_email');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(1, $records);

        $record = $records[0];

        self::assertSame(
            'sergeyvkz@gmail.com',
            $record->getFieldValueByName('Email'),
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

    public function testPhone(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckPhoneController::NAME)
                ->addSource('aeroflot')
                ->addPhone(
                    (new PhoneReq())
                        ->setPhone('79204538711'),
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('aeroflot_phone'));

        $source = $response->getSourceByCheckType('aeroflot_phone');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(1, $records);

        $record = $records[0];

        self::assertSame(
            '79204538711',
            $record->getFieldValueByName('Phone'),
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

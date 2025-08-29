<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckEmailController;
use App\Controller\CheckPhoneController;
use App\Model\EmailReq;
use App\Model\PhoneReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class GosUslugiTest extends AbstractPluginTest
{
    public function testEmail(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckEmailController::NAME)
                ->addSource('gosuslugi_email')
                ->addEmail(
                    (new EmailReq())
                        ->setEmail('soulkoden@gmail.com'),
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('gosuslugi_email'));

        $source = $response->getSourceByCheckType('gosuslugi_email');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(1, $records);

        $record = $records[0];

        self::assertSame(
            'soulkoden@gmail.com',
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
                ->addSource('gosuslugi_phone')
                ->addPhone(
                    (new PhoneReq())
                        ->setPhone('79772776278'),
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('gosuslugi_phone'));

        $source = $response->getSourceByCheckType('gosuslugi_phone');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(1, $records);

        $record = $records[0];

        self::assertSame(
            '79772776278',
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

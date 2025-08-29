<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckEmailController;
use App\Controller\CheckPhoneController;
use App\Model\EmailReq;
use App\Model\PhoneReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class AvitoTest extends AbstractPluginTest
{
    public function testEmail(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckEmailController::NAME)
                ->addSource('avito')
                ->addEmail(
                    (new EmailReq())
                        ->setEmail('stil985@yandex.ru'),
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('avito_email'));

        $source = $response->getSourceByCheckType('avito_email');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(1, $records);

        $record = $records[0];

        self::assertSame(
            'stil985@yandex.ru',
            $record->getFieldValueByName('email'),
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
                ->addSource('avito')
                ->addPhone(
                    (new PhoneReq())
                        ->setPhone('79626145371'),
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('avito_phone'));

        $source = $response->getSourceByCheckType('avito_phone');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(1, $records);

        $record = $records[0];

        self::assertSame(
            '79626145371',
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

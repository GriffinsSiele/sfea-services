<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckEmailController;
use App\Controller\CheckPhoneController;
use App\Model\EmailReq;
use App\Model\PhoneReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class TwitterTest extends AbstractPluginTest
{
    public function testEmail(): void
    {
        self::markTestIncomplete();

        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckEmailController::NAME)
                ->addSource('twitter')
                ->addEmail(
                    (new EmailReq())
                        ->setEmail('soulkoden@gmail.com'),
                ),
        );

        dd($response);
    }

    public function testPhone(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckPhoneController::NAME)
                ->addSource('twitter')
                ->addPhone(
                    (new PhoneReq())
                        ->setPhone('79772776278'),
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('twitter_phone'));

        $source = $response->getSourceByCheckType('twitter_phone');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(1, $records);

        $record = $records[0];

        self::assertSame(
            '79772776278',
            $record->getFieldValueByName('Phone'),
        );

        self::assertSame(
            'Найдена учетная запись',
            $record->getFieldValueByName('Result'),
        );

        self::assertSame(
            'FOUND',
            $record->getFieldValueByName('ResultCode'),
        );
    }
}

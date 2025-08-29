<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckEmailController;
use App\Model\EmailReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class RZDTest extends AbstractPluginTest
{
    public function testEmail(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckEmailController::NAME)
                ->addSource('rzd')
                ->addEmail(
                    (new EmailReq())
                        ->setEmail('soulkoden@gmail.com'),
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('rzd_email'));

        $source = $response->getSourceByCheckType('rzd_email');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(1, $records);

        $record = $records[0];

        self::assertSame(
            'soulkoden@gmail.com зарегистрирован на сайте rzd.ru',
            $record->getFieldValueByName('result'),
        );

        self::assertSame(
            'FOUND',
            $record->getFieldValueByName('result_code'),
        );
    }
}

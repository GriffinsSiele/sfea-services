<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckPhoneController;
use App\Model\PhoneReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class NumBusterTest extends AbstractPluginTest
{
    public function testPhone(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckPhoneController::NAME)
                ->addSource('numbuster')
                ->addPhone(
                    (new PhoneReq())
                        ->setPhone('79772776278'),
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('numbuster_phone'));

        $source = $response->getSourceByCheckType('numbuster_phone');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(1, $records);

        $record = $records[0];

        self::assertSame(
            '79772776278',
            $record->getFieldValueByName('phone'),
        );

        self::assertSame(
            'Сергей К.',
            $record->getFieldValueByName('name'),
        );

        self::assertSame(
            'Сергей',
            $record->getFieldValueByName('first_name'),
        );

        self::assertSame(
            'К.',
            $record->getFieldValueByName('last_name'),
        );

        self::assertSame(
            '5',
            $record->getFieldValueByName('index'),
        );

        self::assertSame(
            'Россия, Москва и Московская область',
            $record->getFieldValueByName('region'),
        );

        self::assertSame(
            'TELE2',
            $record->getFieldValueByName('operator'),
        );

        self::assertSame(
            'Нет',
            $record->getFieldValueByName('is_install_app'),
        );

        self::assertSame(
            'Нет',
            $record->getFieldValueByName('IsHidden'),
        );

        self::assertSame(
            'Нет',
            $record->getFieldValueByName('IsVerified'),
        );

        self::assertSame(
            'Нет',
            $record->getFieldValueByName('is_banned'),
        );

        self::assertSame(
            'Нет',
            $record->getFieldValueByName('IsUnwanted'),
        );

        self::assertSame(
            'Нет',
            $record->getFieldValueByName('is_pro'),
        );

        self::assertSame(
            'profile',
            $record->getFieldValueByName('Type'),
        );
    }
}

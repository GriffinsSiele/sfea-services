<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckPhoneController;
use App\Model\PhoneReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class BoardsTest extends AbstractPluginTest
{
    public function testPhone(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckPhoneController::NAME)
                ->addSource('boards')
                ->addPhone(
                    (new PhoneReq())
                        ->setPhone('79772776278'),
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('boards_phone'));

        $source = $response->getSourceByCheckType('boards_phone');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertGreaterThan(0, $records);

        $kokosy = null;

        foreach ($records as $record) {
            if ($record->hasFieldByName('Name')
                && 'Сергей Коденцов' === $record->getFieldValueByName('Name')
                && $record->hasFieldByName('Source')
                && 'avito.ru' === $record->getFieldValueByName('Source')
            ) {
                $kokosy = $record;

                break;
            }
        }

        self::assertNotNull($kokosy);

        self::assertSame(
            'Сергей Коденцов',
            $record->getFieldValueByName('Name'),
        );

        self::assertSame(
            '2020-08-10 17:29:04',
            $record->getFieldValueByName('Time'),
        );

        self::assertSame(
            'Санкт-Петербург, ',
            $record->getFieldValueByName('Location'),
        );

        self::assertSame(
            'avito.ru',
            $record->getFieldValueByName('Source'),
        );

        self::assertSame(
            'https://www.avito.ru/hapo_oye/zemelnye_uchastki/uchastok_7.8_sot._snt_dnp_1973223648',
            $record->getFieldValueByName('URL'),
        );

        self::assertSame(
            'Недвижимость, Земельные участки',
            $record->getFieldValueByName('Category'),
        );

        self::assertSame(
            'Участок 7.8 сот. (СНТ, ДНП)',
            $record->getFieldValueByName('Title'),
        );

        self::assertTrue(
            $record->hasFieldByName('Description')
        );

        self::assertSame(
            '1485040',
            $record->getFieldValueByName('Price'),
        );
    }
}

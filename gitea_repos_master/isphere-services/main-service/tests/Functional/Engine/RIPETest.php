<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckIPController;
use App\Model\IPReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class RIPETest extends AbstractPluginTest
{
    public function testIP(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckIPController::NAME)
                ->addSource('ripe')
                ->addIp(
                    (new IPReq())
                        ->setIp('78.140.221.69')
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('ripe'));

        $source = $response->getSourceByCheckType('ripe');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(4, $records);

        $firstRecord = $records[0];

        self::assertSame(
            'inetnum',
            $firstRecord->getFieldValueByName('type'),
        );

        self::assertSame(
            '78.140.192.0 - 78.140.239.255',
            $firstRecord->getFieldValueByName('inetnum'),
        );

        self::assertSame(
            'RU-SEVEREN-20070705',
            $firstRecord->getFieldValueByName('netname'),
        );

        self::assertSame(
            'RU',
            $firstRecord->getFieldValueByName('country'),
        );

        self::assertSame(
            'ORG-SL18-RIPE',
            $firstRecord->getFieldValueByName('org'),
        );

        self::assertSame(
            'SPEC1-RIPE',
            $firstRecord->getFieldValueByName('admin-c'),
        );

        self::assertSame(
            'SPEC1-RIPE',
            $firstRecord->getFieldValueByName('tech-c'),
        );

        self::assertSame(
            'ALLOCATED PA',
            $firstRecord->getFieldValueByName('status'),
        );

        self::assertSame(
            'PROMETEY-MNT',
            $firstRecord->getFieldValueByName('mnt-by'),
        );

        self::assertSame(
            '2021-09-15T13:58:02Z',
            $firstRecord->getFieldValueByName('created'),
        );

        self::assertSame(
            '2021-12-21T11:38:59Z',
            $firstRecord->getFieldValueByName('last-modified'),
        );

        self::assertSame(
            'RIPE',
            $firstRecord->getFieldValueByName('source'),
        );

        $secondRecord = $records[1];

        self::assertSame(
            'organisation',
            $secondRecord->getFieldValueByName('type'),
        );

        self::assertSame(
            'ORG-SL18-RIPE',
            $secondRecord->getFieldValueByName('organisation'),
        );

        self::assertSame(
            'JSC "Severen-Telecom"',
            $secondRecord->getFieldValueByName('org-name'),
        );

        self::assertSame(
            'RU',
            $secondRecord->getFieldValueByName('country'),
        );

        self::assertSame(
            'LIR',
            $secondRecord->getFieldValueByName('org-type'),
        );

        self::assertSame(
            'RUSSIAN FEDERATION',
            $secondRecord->getFieldValueByName('address'),
        );

        self::assertSame(
            '+7 812 740-7070',
            $secondRecord->getFieldValueByName('phone'),
        );

        self::assertSame(
            '+7 812 740-7071',
            $secondRecord->getFieldValueByName('fax-no'),
        );

        self::assertSame(
            'STKM1-RIPE',
            $secondRecord->getFieldValueByName('admin-c'),
        );

        self::assertSame(
            'STKM1-RIPE',
            $secondRecord->getFieldValueByName('tech-c'),
        );

        self::assertSame(
            'RIPE-NCC-HM-MNT',
            $secondRecord->getFieldValueByName('mnt-ref'),
        );

        self::assertSame(
            'SEVEREN-MNT',
            $secondRecord->getFieldValueByName('mnt-by'),
        );

        self::assertSame(
            'STKM1-RIPE',
            $secondRecord->getFieldValueByName('abuse-c'),
        );

        self::assertSame(
            '2004-04-17T11:58:16Z',
            $secondRecord->getFieldValueByName('created'),
        );

        self::assertSame(
            '2021-12-14T13:46:25Z',
            $secondRecord->getFieldValueByName('last-modified'),
        );

        self::assertSame(
            'RIPE',
            $secondRecord->getFieldValueByName('source'),
        );

        $thirdRecord = $records[2];

        self::assertSame(
            'role',
            $thirdRecord->getFieldValueByName('type'),
        );

        self::assertSame(
            'SEVEREN-TELECOM NOC ROLE',
            $thirdRecord->getFieldValueByName('role'),
        );

        self::assertSame(
            'Russia',
            $thirdRecord->getFieldValueByName('address'),
        );

        self::assertSame(
            'ORG-SL18-RIPE',
            $thirdRecord->getFieldValueByName('org'),
        );

        self::assertSame(
            'MNB29-RIPE',
            $thirdRecord->getFieldValueByName('admin-c'),
        );

        self::assertSame(
            'MNB29-RIPE',
            $thirdRecord->getFieldValueByName('tech-c'),
        );

        self::assertSame(
            'SPEC1-RIPE',
            $thirdRecord->getFieldValueByName('nic-hdl'),
        );

        self::assertSame(
            'abuse@severen.ru',
            $thirdRecord->getFieldValueByName('abuse-mailbox'),
        );

        self::assertSame(
            'PROMETEY-MNT',
            $thirdRecord->getFieldValueByName('mnt-by'),
        );

        self::assertSame(
            '2007-04-25T12:46:43Z',
            $thirdRecord->getFieldValueByName('created'),
        );

        self::assertSame(
            '2022-08-22T15:01:20Z',
            $thirdRecord->getFieldValueByName('last-modified'),
        );

        self::assertSame(
            'RIPE',
            $thirdRecord->getFieldValueByName('source'),
        );

        $fourthRecord = $records[3];

        self::assertSame(
            'route',
            $fourthRecord->getFieldValueByName('type'),
        );

        self::assertSame(
            '78.140.221.0/24',
            $fourthRecord->getFieldValueByName('route'),
        );

        self::assertSame(
            'IT-Grad_LLC',
            $fourthRecord->getFieldValueByName('descr'),
        );

        self::assertSame(
            'AS48096',
            $fourthRecord->getFieldValueByName('origin'),
        );

        self::assertSame(
            'PROMETEY-MNT',
            $fourthRecord->getFieldValueByName('mnt-by'),
        );

        self::assertSame(
            '2017-10-14T05:07:08Z',
            $fourthRecord->getFieldValueByName('created'),
        );

        self::assertSame(
            '2017-10-14T05:07:08Z',
            $fourthRecord->getFieldValueByName('last-modified'),
        );

        self::assertSame(
            'RIPE',
            $fourthRecord->getFieldValueByName('source'),
        );
    }
}

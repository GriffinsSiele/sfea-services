<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckController;
use App\Controller\CheckOrgController;
use App\Model\OrgReq;
use App\Model\PersonReq;
use App\Model\Record;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class BankrotTest extends AbstractPluginTest
{
    public function testINNPerson(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckController::NAME)
                ->addSource('bankrot')
                ->addPerson(
                    (new PersonReq())
                        ->setInn('668600245192'),
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('bankrot_inn'));

        $source = $response->getSourceByCheckType('bankrot_inn');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(1, $records);

        $record = $records[0];

        $this->assertPerson($record);
    }

    public function testPerson(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckController::NAME)
                ->addSource('bankrot')
                ->addPerson(
                    (new PersonReq())
                        ->setSurname('ДАВЫДОВ')
                        ->setName('АЛЕКСЕЙ')
                        ->setPatronymic('ВЛАДИМИРОВИЧ')
                        ->setBirthday(new \DateTimeImmutable('22.02.1998'))
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('bankrot_person'));

        $source = $response->getSourceByCheckType('bankrot_person');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(1, $records);

        $record = $records[0];

        $this->assertPerson($record);
    }

    public function testINNOrganization(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckOrgController::NAME)
                ->addSource('bankrot')
                ->addOrg(
                    (new OrgReq())
                        ->setInn('4230020425'),
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('bankrot_org'));

        $source = $response->getSourceByCheckType('bankrot_org');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(1, $records);

        $record = $records[0];

        $this->assertOrganization($record);
    }

    public function testNameOrganization(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckOrgController::NAME)
                ->addSource('bankrot')
                ->addOrg(
                    (new OrgReq())
                        ->setName('ОБЩЕСТВО С ОГРАНИЧЕННОЙ ОТВЕТСТВЕННОСТЬЮ "ЮРГИНСКИЙ МАШИНОСТРОИТЕЛЬНЫЙ ЗАВОД"')
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('bankrot_org'));

        $source = $response->getSourceByCheckType('bankrot_org');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(1, $records);

        $record = $records[0];

        $this->assertOrganization($record);
    }

    private function assertPerson(Record $record): void
    {
        self::assertSame(
            'ДАВЫДОВ',
            $record->getFieldValueByName('lastname'),
        );

        self::assertSame(
            'АЛЕКСЕЙ',
            $record->getFieldValueByName('firstname'),
        );

        self::assertSame(
            'ВЛАДИМИРОВИЧ',
            $record->getFieldValueByName('middlename'),
        );

        self::assertSame(
            '22.02.1998',
            $record->getFieldValueByName('birthdate'),
        );

        self::assertSame(
            'гор. Уральск Западно-Казахстанской обл.',
            $record->getFieldValueByName('birthplace'),
        );

        self::assertSame(
            'Свердловская область',
            $record->getFieldValueByName('region'),
        );

        self::assertSame(
            '668600245192',
            $record->getFieldValueByName('inn'),
        );

        self::assertSame(
            '176-298-952 33',
            $record->getFieldValueByName('snils'),
        );

        self::assertSame(
            'Физическое лицо',
            $record->getFieldValueByName('categoryname'),
        );

        self::assertSame(
            '624091, Россия, Свердловская обл., гор. Верхняя Пышма, ул. Петрова, д. 34В, кв. 3',
            $record->getFieldValueByName('address'),
        );

        self::assertSame(
            'ДАВЫДОВ АЛЕКСЕЙ ВЛАДИМИРОВИЧ',
            $record->getFieldValueByName('name'),
        );

        self::assertSame(
            'https://old.bankrot.fedresurs.ru/PrivatePersonCard.aspx?ID=9D1B12304DAAEC69F634DD211B8DD5AC',
            $record->getFieldValueByName('url'),
        );

        self::assertSame(
            '7',
            $record->getFieldValueByName('msgcount'),
        );

        self::assertSame(
            '19',
            $record->getFieldValueByName('doccount'),
        );

        self::assertSame(
            '13.09.2022 11:05:36',
            $record->getFieldValueByName('lastmsgdate'),
        );

        self::assertSame(
            'Сообщение о судебном акте',
            $record->getFieldValueByName('lastmsgtitle'),
        );

        self::assertSame(
            'https://old.bankrot.fedresurs.ru/MessageWindow.aspx?ID=9ADD6B2A90E01E58B40414E4EF3192AD',
            $record->getFieldValueByName('lastmsgurl'),
        );

        self::assertSame(
            '21.03.2022 10:40:03',
            $record->getFieldValueByName('publicationdate'),
        );

        self::assertSame(
            'Сообщение о судебном акте',
            $record->getFieldValueByName('publicationtitle'),
        );

        self::assertSame(
            'https://old.bankrot.fedresurs.ru/MessageWindow.aspx?ID=E293DAC1AFD8BC58EF64BFF6373713B7',
            $record->getFieldValueByName('publicationurl'),
        );

        self::assertSame(
            'Нет',
            $record->getFieldValueByName('simplified'),
        );

        self::assertSame(
            '16.09.2022',
            $record->getFieldValueByName('lastdocdate'),
        );

        self::assertSame(
            'О завершении реализации имущества гражданина и освобождении гражданина от исполнения обязательств',
            $record->getFieldValueByName('lastdoctitle'),
        );

        self::assertSame(
            'http://kad.arbitr.ru/PdfDocument/9d96f138-e5aa-48c7-bdb1-5e40ef5b22b2/A60_927_2022_Opredelenie_20220916.pdf',
            $record->getFieldValueByName('lastdocurl'),
        );

        self::assertSame(
            '16.09.2022',
            $record->getFieldValueByName('completiondate'),
        );

        self::assertSame(
            'http://kad.arbitr.ru/PdfDocument/9d96f138-e5aa-48c7-bdb1-5e40ef5b22b2/A60_927_2022_Opredelenie_20220916.pdf',
            $record->getFieldValueByName('completionurl'),
        );

        self::assertSame(
            '23.03.2022',
            $record->getFieldValueByName('decisiondate'),
        );

        self::assertSame(
            'http://kad.arbitr.ru/PdfDocument/ae1840d9-3228-498c-9fb6-3137ef6af928/A60_927_2022_Reshenija_i_postanovlenija_20220323.pdf',
            $record->getFieldValueByName('decisionurl'),
        );

        self::assertSame(
            '15.02.2022',
            $record->getFieldValueByName('petitiondate'),
        );

        self::assertSame(
            'http://kad.arbitr.ru/PdfDocument/6ba54028-ee95-48cd-8b22-fc20999c5399/A60_927_2022_Opredelenie_20220215.pdf',
            $record->getFieldValueByName('petitionurl'),
        );
    }

    private function assertOrganization(Record $record): void
    {
        self::assertSame(
            'Стратегическое предприятие и организация',
            $record->getFieldValueByName('category'),
        );

        self::assertSame(
            'ОБЩЕСТВО С ОГРАНИЧЕННОЙ ОТВЕТСТВЕННОСТЬЮ "ЮРГИНСКИЙ МАШИНОСТРОИТЕЛЬНЫЙ ЗАВОД"',
            $record->getFieldValueByName('name'),
        );

        self::assertSame(
            '4230020425',
            $record->getFieldValueByName('inn'),
        );

        self::assertSame(
            '1054230016180',
            $record->getFieldValueByName('ogrn'),
        );

        self::assertSame(
            'Кемеровская область',
            $record->getFieldValueByName('region'),
        );

        self::assertSame(
            '652050, ГОРОД ЮРГА, УЛИЦА ШОССЕЙНАЯ, 3',
            $record->getFieldValueByName('address'),
        );

        self::assertSame(
            'https://old.bankrot.fedresurs.ru/OrganizationCard.aspx?ID=C9A0A37D102041BB0EF4DA4EC2A6D330',
            $record->getFieldValueByName('url'),
        );
    }
}

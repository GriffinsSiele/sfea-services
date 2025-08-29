<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckIPController;
use App\Model\IPReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class DNSTest extends AbstractPluginTest
{
    public function testIP(): void
    {
        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckIPController::NAME)
                ->addSource('dns')
                ->addIp(
                    (new IPReq())
                        ->setIp('92.127.107.64')
                ),
        );

        self::assertTrue($response->hasSourceByCheckType('dns'));

        $source = $response->getSourceByCheckType('dns');

        self::assertNull($source->getError());

        $records = $source->getRecords();

        self::assertCount(1, $records);

        $record = $records[0];

        self::assertSame(
            '92-127-107-64-bbc-dynamic.kuzbass.net',
            $record->getFieldValueByName('name'),
        );

        self::assertSame(
            '92.127.107.64',
            $record->getFieldValueByName('hosts'),
        );
    }
}

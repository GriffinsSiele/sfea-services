<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckAutoController;
use App\Model\CarReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class GIBDDTest extends AbstractPluginTest
{
    public function testCarHistory(): void
    {
        self::markTestIncomplete();

        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckAutoController::NAME)
                ->addSource('gibdd_history')
                ->addCar(
                    (new CarReq())
                        ->setVin('XUUNA486JC0030559')
                        ->setBodyNumber('XUUNA486JC0030559')
                )
        );

        dd($response);
    }
}

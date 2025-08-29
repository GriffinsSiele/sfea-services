<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckEmailController;
use App\Model\EmailReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class AppleTest extends AbstractPluginTest
{
    public function testEmail(): void
    {
        self::markTestIncomplete();

        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckEmailController::NAME)
                ->addSource('apple')
                ->addEmail(
                    (new EmailReq())
                        ->setEmail('soulkoden@gmail.com'),
                ),
        );

        dd($response);
    }
}

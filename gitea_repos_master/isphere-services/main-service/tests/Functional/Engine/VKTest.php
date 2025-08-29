<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckEmailController;
use App\Controller\CheckPhoneController;
use App\Model\EmailReq;
use App\Model\PhoneReq;
use App\Model\Request;
use App\Model\URLReq;
use App\Test\AbstractPluginTest;

class VKTest extends AbstractPluginTest
{
    public function testEmail(): void
    {
        self::markTestIncomplete();

        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckEmailController::NAME)
                ->addSource('vk')
                ->addEmail(
                    (new EmailReq())
                        ->setEmail('natalie1211@mail.ru'),
                ),
        );

        dd($response);
    }

    public function testPhone(): void
    {
        self::markTestIncomplete();

        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckPhoneController::NAME)
                ->addSource('vk')
                ->addPhone(
                    (new PhoneReq())
                        ->setPhone('79772776278'),
                ),
        );

        dd($response);
    }

    public function testUrl(): void
    {
        self::markTestIncomplete();

        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckEmailController::NAME)
                ->addSource('vk')
                ->addUrl(
                    (new URLReq())
                        ->setUrl('https://vk.com/soulkoden'),
                ),
        );

        dd($response);
    }
}

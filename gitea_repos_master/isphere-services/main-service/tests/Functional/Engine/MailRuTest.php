<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Controller\CheckEmailController;
use App\Controller\CheckPhoneController;
use App\Model\EmailReq;
use App\Model\PhoneReq;
use App\Model\Request;
use App\Test\AbstractPluginTest;

class MailRuTest extends AbstractPluginTest
{
    public function testEmail(): void
    {
        self::markTestIncomplete();

        $this->init();
        $this->authenticate();

        $response = $this->post(
            (new Request())
                ->setRequestType(CheckEmailController::NAME)
                ->addSource('mailru')
                ->addEmail(
                    (new EmailReq())
                        ->setEmail('soulkoden@gmail.com'),
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
                ->addSource('mailru')
                ->addPhone(
                    (new PhoneReq())
                        ->setPhone('79772776278'),
                ),
        );

        dd($response);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Functional\Renderer;

use App\Entity\SystemUser;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PDFRenderTest extends KernelTestCase
{
    public function testRender(): void
    {
        self::bootKernel();

        $logger = self::getContainer()->get('app.logger');

        $xmlpath = \realpath(__DIR__.'/../../../var/log/dev/xml');

        $user = $this->getMockBuilder(SystemUser::class)
            ->getMock();

        $user->method('getId')->willReturn(1);

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $this->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result->method('fetchAllAssociative')
            ->willReturn([['non', 'empty', 'array']]);

        $connection->method('executeQuery')->willReturn($result);

        $GLOBALS['app'] = self::getContainer()->get('kernel');

        $_REQUEST = [
            'id' => '10',
            'mode' => 'pdf',
        ];

        $_SERVER = [
            'HTTP_HOST' => 'i-sphere.ru',
        ];

        require __DIR__.'/../../../templates/showresult.php';

        self::assertTrue(true);
    }
}

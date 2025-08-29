<?php

declare(strict_types=1);

namespace App\Test;

use App\Test\Model\Params;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ParamsFactory implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function __construct(
        private readonly Connection $cbrConnection,
        private readonly Connection $connection,
        private readonly Connection $fnsConnection,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function create(string $requestType, string $source): Params
    {
        return (new Params())
            ->setUserIp('127.0.0.1')
            ->setRequestType($requestType)
            ->setSources([
                $source,
            ])
            ->setContainer($this->container)
            ->setConnection($this->connection)
            ->setCbrConnection($this->cbrConnection)
            ->setFnsConnection($this->fnsConnection)
            ->setLogger($this->logger)
            ->setContactTypes([
                'email',
                'nick',
                'phone',
                'skype',
                'telegram',
            ])
            ->setContactUrls([
                'facebook' => 'facebook.com',
                'hh' => 'hh.ru',
                'instagram' => 'instagram.com',
                'ok' => 'ok.ru',
                'vk' => 'vk.com',
            ])
            ->setClientId('1')
            ->setUserId('1')
            ->setReqId((string) \random_int(0, 1 << 31))
            ->setReqTime(\date('Y-m-d\TH:i:s'))
            ->setReqDate(\date('Y-m-d'))
            ->setHttpConnectTimeout(5)
            ->setHttpTimeout(55)
            ->setTimeout(60)
            ->setHttpAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:106.0) Gecko/20100101 Firefox/106.0')
            ->setXmlPath($this->container->getParameter('app.xml_path'))
            ->setServiceUrl('http://localhost')
            ->setUserSources([
                '2gis' => true,
                'aeroflot' => true,
                'akbars' => true,
                'alfabank' => true,
                'announcement' => true,
                'apple' => true,
                'avangard' => true,
                'avito' => true,
                'avtokod' => true,
                'bankrot' => true,
                'banks' => true,
                'biglion' => true,
                'boards' => true,
                'callapp' => true,
                'cbr' => true,
                'censys' => true,
                'commerce' => true,
                'dns' => true,
                'eaisto' => true,
                'egrul' => true,
                'elecsnet' => true,
                'emt' => true,
                'facebook' => true,
                'fms' => true,
                'fmsdb' => true,
                'fns' => true,
                'fssp' => true,
                'fssp_suspect' => true,
                'fsspapi' => true,
                'fsspsite' => true,
                'gazenergobank' => true,
                'gazprombank' => true,
                'getcontact' => true,
                'gibdd' => true,
                'gibdd_aiusdtp' => true,
                'gibdd_diagnostic' => true,
                'gibdd_driver' => true,
                'gibdd_fines' => true,
                'gibdd_history' => true,
                'gibdd_restricted' => true,
                'gibdd_wanted' => true,
                'gisgmp' => true,
                'gks' => true,
                'google' => true,
                'googleplus' => true,
                'gosuslugi' => true,
                'gosuslugi_email' => true,
                'gosuslugi_inn' => true,
                'gosuslugi_passport' => true,
                'gosuslugi_phone' => true,
                'hh' => true,
                'hlr' => true,
                'icq' => true,
                'infobip' => true,
                'instagram' => true,
                'ipgeobase' => true,
                'kad' => true,
                'mailru' => true,
                'mkb' => true,
                'mvd' => true,
                'names' => true,
                'notariat' => true,
                'numbuster' => true,
                'numbusterapp' => true,
                'ok' => true,
                'openbank' => true,
                'papajohns' => true,
                'people' => true,
                'phonenumber' => true,
                'phones' => true,
                'pochta' => true,
                'psbank' => true,
                'qiwi' => true,
                'qiwibank' => true,
                'raiffeisen' => true,
                'reestrzalogov' => true,
                'ripe' => true,
                'rnko' => true,
                'rosbank' => true,
                'rossvyaz' => true,
                'rsa' => true,
                'rsa_bsostate' => true,
                'rsa_kbm' => true,
                'rsa_org' => true,
                'rsa_osagovehicle' => true,
                'rsa_policy' => true,
                'rsb' => true,
                'rz' => true,
                'rzd' => true,
                'sber' => true,
                'sberbank' => true,
                'sbertest' => true,
                'shodan' => true,
                'simpler' => true,
                'skype' => true,
                'smsc' => true,
                'sovcombank' => true,
                'sypexgeo' => true,
                'tc' => true,
                'telegram' => true,
                'telegramweb' => true,
                'terrorist' => true,
                'test' => true,
                'testr' => true,
                'tinkoff' => true,
                'truecaller' => true,
                'twitter' => true,
                'unicredit' => true,
                'uralair' => true,
                'vestnik' => true,
                'viber' => true,
                'viber_phone' => true,
                'viberwin_phone' => true,
                'vk' => true,
                'vtb' => true,
                'webmoney' => true,
                'whatsapp' => true,
                'whatsapp_phone' => true,
                'whatsappweb' => true,
                'yamap' => true,
                'yamoney' => true,
                'zakupki' => true,
            ]);
    }
}

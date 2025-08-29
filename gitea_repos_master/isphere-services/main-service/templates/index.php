<?php

/**
 * @global Connection $connection
 * @global ContainerInterface $container
 * @global Request $request
 * @global SystemUser $user
 */

use App\Entity\SystemUser;
use App\Message\AsyncProcessCommandMessage;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

require_once 'functions.php';

$mainRequest = $request;

if ($mainRequest->isMethod(Request::METHOD_GET)) {
    \header('Location: /admin', true, 302);
}

// header('Access-Control-Allow-Origin: https://itkom.amocrm.ru');

if (!$mainRequest->isMethod(Request::METHOD_POST)) {
    return;
}

$xml = $request->getContent();
$xml = \substr($xml, \strpos($xml, '<'));

if (!$xml) {
    echo '
    <error>
        <code>400</code>
        <message>Запрос отсутствует</message>
    </error>';
    \header('HTTP/1.1 400 Bad Request');

    return;
}

if (false === \strpos($xml, '<Request>')) {
    echo '
    <error>
        <code>400</code>
        <message>В запросе отсутствует тег Request</message>
    </error>';
    \header('HTTP/1.1 400 Bad Request');

    return;
}

// ////////////////////////////////////////////////////////
require_once 'cookies.php';
require_once 'str.php';
require_once 'neuro.php';

require_once 'engine/RequestManager.php';
require_once 'engine/RequestContext.php';
require_once 'engine/ResultDataField.php';
require_once 'engine/ResultDataList.php';

require_once 'engine/plugins/PluginInterface.php';

$contact_types = ['phone', 'email', 'skype', 'telegram', 'nick'];
$contact_urls = [
    'vk' => 'vk.com',
    'facebook' => 'facebook.com',
    'ok' => 'ok.ru',
    'instagram' => 'instagram.com'
    /* ,'mymail'=>'my.mail.ru' */,
    'hh' => 'hh.ru',
];

$reqtime = \date("Y-m-d\TH:i:s");
$reqdate = \date('Y-m-d');
$restime = '';
/*
$fout1 = fopen($logpath.'request.'.date('Y-m-d').'.log', 'a');
fputs($fout1, date("Y-m-d H:i:s")."\n".$xml."\n\n");
fclose($fout1);
*/
$userId = $user->getId();

$processing = 0;
$total_processing = 0;
if ($userId && !$mainRequest->attributes->has('_skipLimits') && ($result = $connection->executeQuery(
        <<<SQL
SELECT sum(user_id=$userId) processing,
       COUNT(*) total_processing
FROM RequestNew
WHERE created_at>date_sub(now(),interval 5 minute) AND status=0
SQL,
    )->fetchAllAssociative())) {
    if ($row = $result[0]) {
        $processing = $row['processing'];
        $total_processing = $row['total_processing'];
    }

    if ($processing >= (3302 == $user->getId() ? 10 : 50)) {
        echo '
        <error>
            <code>429</code>
            <message>Слишком много запросов в обработке</message>
        </error>';
        \header('HTTP/1.1 429 Too Many Requests');

        return;
    }
    if ($total_processing >= 300) {
        echo '
        <error>
            <code>503</code>
            <message>Сервис перегружен запросами</message>
        </error>';
        \header('HTTP/1.1 503 Service Unavailable');

        return;
    }
}

$user_sources = [];
if ($userId && ($result = $connection->executeQuery(
        <<<'SQL'
SELECT a.source_name
FROM AccessSource a,SystemUsers u
WHERE a.allowed=1
  AND a.Level=u.AccessLevel
  AND u.id=:user_id
SQL,
        [
            'user_id' => $user->getId(),
        ]
    )->fetchAllAssociative())) {
    foreach ($result as $row) {
        $user_sources[$row['source_name']] = true;
        if ('fssp' == $row['source_name']) {
            $user_sources['fsspsite'] = true;
        }
        if ('viber' == $row['source_name']) {
            $user_sources['viberwin_phone'] = true;
        }
    }
}

$clientId = $user->getClient()?->getId();
$status = $user->getClient()?->getStatus();

if ($status < 1) {
    echo '
        <error>
            <code>401</code>
            <message>Доступ приостановлен</message>
        </error>';
    \header('HTTP/1.1 401 Unauthorized');

    return;
}

if (2430 == $userId) {
    $total_timeout = 30;
}

$params = parseParams($xml, $total_timeout);
$params['_clientId'] = $user->getClient()?->getId();
$params['_connection'] = $connection;
$params['_cbrConnection'] = $cbrConnection;
$params['_commerceConnection'] = $commerceConnection;
$params['_fedsfmConnection'] = $fedsfmConnection;
$params['_fnsConnection'] = $fnsConnection;
$params['_rossvyazConnection'] = $rossvyazConnection;
$params['_statsConnection'] = $statsConnection;
$params['_vkConnection'] = $vkConnection;
$params['_contact_types'] = $contact_types;
$params['_contact_urls'] = $contact_urls;
$params['_container'] = $container;
$params['_http_agent'] = $http_agent;
$params['_http_connecttimeout'] = $http_connecttimeout;
$params['_http_timeout'] = $http_timeout;
$params['_logger'] = $logger;
$params['_reqdate'] = $reqdate;
$params['_reqtime'] = $reqtime;
$params['_restime'] = $restime;
$params['_serviceurl'] = $serviceurl;
$params['_urlGenerator'] = $urlGenerator;
$params['_user_sources'] = $user_sources;
$params['_userId'] = $user->getId();
$params['_xmlpath'] = \rtrim($container->getParameter('app.xml_path'), '/');

if (!\is_dir($container->getParameter('app.egrul_path')) && !\mkdir($concurrentDirectory = $container->getParameter('app.egrul_path'), 0755, true) && !\is_dir($concurrentDirectory)) {
    throw new \RuntimeException(\sprintf('Directory "%s" was not created', $concurrentDirectory));
}

if (!\is_dir($container->getParameter('app.xml_path')) && !\mkdir($concurrentDirectory = $container->getParameter('app.xml_path'), 0755, true) && !\is_dir($concurrentDirectory)) {
    throw new \RuntimeException(\sprintf('Directory "%s" was not created', $concurrentDirectory));
}

$req = \preg_replace("/<\?xml[^>]+>/", '', $xml);
$req = \preg_replace("/<Password>[^<]+<\/Password>/", '<Password>***</Password>', $req);

if (!$mainRequest->attributes->has('_reqId')) {
    $reqId = logRequest($params, $req);
} else {
    $reqId = $mainRequest->attributes->get('_reqId');
}

$params['_reqId'] = $reqId;
$req = \preg_replace("/<Password>[^<]+<\/Password>/", "<requestDateTime>$reqtime</requestDateTime>", $req);
$params['_req'] = $req;

if ($params['async'] && !$mainRequest->attributes->has('_skipMessengerDispatch')) {
    $response = logResponse($params, [], 0);
    echo $response;

    $message = new AsyncProcessCommandMessage(
        userId: $user->getId(),
        clientIp: $mainRequest->getClientIp(),
        reqId: $reqId,
        xml: $xml,
    );
    $messageBus->dispatch($message);

    return;
}

$plugin_interface = [];
$response = runRequests($params);
echo $response;

/*
$fout2 = fopen($logpath.'response.'.date('Y-m-d').'.log', 'a');
fputs($fout2, date("Y-m-d H:i:s")."\n".$response."\n\n");
fclose($fout2);
*/

<?php

/**
 * @global AuthorizationCheckerInterface $authorizationChecker
 * @global Request $request
 */

use App\Controller\AdminController;
use App\Controller\DefaultController;
use App\Entity\AccessRoles;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

require_once 'xml.php';

$mainRequest = $request;

$view->extend('base.php');

\set_time_limit($form_timeout + 30);

$user_level = $user->getAccessLevel();
$user_sources = $user->getAccessSourcesMap();

// Источники (название,выбран,рекурсивный,конец строки)
$check_sources = [
    'gosuslugi_phone' => ['Госуслуги', 1, 0, 0],
    'rossvyaz' => ['Россвязь', 1, 1, 1],
    'hlr' => ['HLR', 1, 1, 0],
//  'infobip'=>array('Infobip',1,1,0),
    'smsc' => ['SMSC', 1, 1, 1],
//  'people'=>array('Соцсети',1,0,0),
//  'beholder'=>array('Beholder',1,1,0),
    'vk' => ['VK', 1, 1, 0],
    'ok' => ['OK', 1, 1, 0],
    'mailru' => ['Mail.Ru', 1, 1, 1],
    'twitter' => ['Twitter', 1, 1, 0],
    'facebook' => ['Facebook', 1, 1, 0],
    'instagram' => ['Instagram', 1, 1, 1],
//  'hh'=>array('HH',1,1,0),
//  'commerce'=>array('Commerce',1,1,0),
    'announcement' => ['Объявления', 1, 1, 0],
    'boards' => ['Boards', 1, 1, 1],
    'skype' => ['Skype', 1, 1, 0],
    'google' => ['Google', 1, 1, 0],
    'google_name' => ['Google имя', 1, 1, 0],
    'googleplus' => ['Google+', 1, 1, 1],
    'whatsapp' => ['WhatsApp', 1, 1, 0],
    'telegram' => ['Telegram', 1, 1, 0],
//  'telegramweb'=>array('Telegram',1,1,0),
//  'icq'=>array('ICQ',1,1,0),
    'viber' => ['Viber', 1, 1, 1],
    'yamap' => ['Яндекс.Карты', 1, 1, 0],
    '2gis' => ['2ГИС', 1, 1, 0],
    'egrul' => ['ЕГРЮЛ', 1, 1, 1],
    'getcontactweb' => ['GetContact', 1, 1, 0],
    'getcontact' => ['GetContact', 1, 1, 0],
    'truecaller' => ['TrueCaller', 1, 1, 0],
    'emt' => ['EmobileTracker', 1, 1, 1],
    'callapp' => ['CallApp', 1, 1, 0],
    'simpler' => ['Simpler', 1, 1, 0],
    'numbuster' => ['NumBuster', 1, 1, 1],
//  'numbusterapp'=>array('NumBuster',1,2,0),
    'names' => ['Имена', 1, 1, 0],
    'phones' => ['Телефоны', 1, 1, 1],
//  'avinfo'=>array('AvInfo',1,1,0)),
//  'phonenumber'=>array('PhoneNumber',1,1,0),
//  'banks'=>array('Банки СБП',0,0,0),
//  'tinkoff'=>array('Тинькофф',0,1,0),
//  'alfabank'=>array('Альфа-Банк',0,1,0),
//  'vtb'=>array('ВТБ',0,1,0),
//  'openbank'=>array('Открытие',0,1,1),
//  'psbank'=>array('Промсвязьбанк',0,1,0),
//  'rosbank'=>array('Росбанк',0,1,0),
//  'unicredit'=>array('Юникредит',0,1,0),
//  'raiffeisen'=>array('Райффайзен',0,1,1),
//  'sovcombank'=>array('Совкомбанк',0,1,0),
//  'gazprombank'=>array('Газпромбанк',0,1,0),
//  'mkb'=>array('МКБ',0,1,0),
//  'rsb'=>array('Русский стандарт',0,1,1),
//  'avangard'=>array('Авангард',0,1,0),
//  'qiwibank'=>array('КИВИ Банк',0,1,0),
//  'rnko'=>array('РНКО Платежный центр',0,1,1),
//  'visa'=>array('VISA',0,1,0),
//  'webmoney'=>array('WebMoney',1,1,0),
//  'sber'=>array('Сбер Онлайн',0,0,0),
//  'sbertest'=>array('Сбербанк тест',0,1,0),
//  'sberbank'=>array('Сбербанк',0,1,1),
//  'qiwi'=>array('Qiwi',1,1,0),
//  'yamoney'=>array('Яндекс.Деньги',1,1,1),
//  'elecsnet'=>array('Элекснет',1,1,1),
    'pochta' => ['Почта', 1, 1, 0],
    'aeroflot' => ['Аэрофлот', 1, 1, 0],
//  'uralair'=>array('Уральские авиалинии',1,1,1),
//  'biglion'=>array('Биглион',1,1,0),
    'papajohns' => ['Папа Джонс', 1, 1, 0],
    'avito' => ['Авито', 1, 1, 1],
//  'rz'=>array('Реестр залогов',1,0,0),
    'reestrzalogov' => ['Реестр залогов', 1, 0, 0],
    'fssp' => ['ФССП', 1, 0, 0],
    'fssp_suspect' => ['ФССП розыск', 1, 0, 0],
    'gisgmp' => ['ГИС ГМП', 1, 0, 1],
    'bankrot' => ['Банкроты', 1, 0, 0],
    'terrorist' => ['Террористы', 1, 0, 0],
    'mvd' => ['МВД', 1, 0, 1],
];

if (!isset($_REQUEST['phone'])) {
    $_REQUEST['phone'] = '';
}
if (!isset($_REQUEST['sources'])) {
    $_REQUEST['sources'] = [];
}
if (!isset($_REQUEST['recursive'])) {
    $_REQUEST['recursive'] = 0;
}
if (!isset($_REQUEST['async'])) {
    $_REQUEST['async'] = ('POST' == $_SERVER['REQUEST_METHOD'] ? 0 : 1);
}

foreach ($_REQUEST as $rn => $rv) {
    $_REQUEST[$rn] = \preg_replace("/[<>\/]/", '', $rv);
}

foreach ($check_sources as $k => $s) {
    if (/* ($user_level<0) || */
        isset($user_sources[$k]) && $user_sources[$k]) {
        if (!isset($_REQUEST['mode']) && !isset($_REQUEST['sources'][$k])) {
            $_REQUEST['sources'][$k] = $s[1];
        }
        //        if ($_REQUEST['recursive'] && $s[2]) $_REQUEST['sources'][$k] = 1;
    }
}

$view['slots']->set('title', 'Проверка телефона 🇷🇺');

?>
    <div class="container-fluid">
        <div class="row">
            <div class="col">
                <form id="checkform" method="POST">
                    <div class="form-group row mb-3">
                        <label for="phone" class="col-sm-3 col-form-label">
                            Номер телефона<span class="req">*</span>
                        </label>
                        <div class="col-sm-4">
                            <input class="form-control" id="phone" type="text" name="phone"
                                   value="<?php
                                   echo $_REQUEST['phone']; ?>" required="1"
                                   maxlength="50" autofocus/>
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label class="col-sm-3 col-form-label">Источники</label>
                        <div class="col-sm-4">
                            <div class="row mb-3">
                                <div class="col d-flex flex-wrap align-content-start" style="gap: 0 1rem;">
                                    <?php
                                    $line = false;
                                    foreach ($check_sources as $k => $s) {
                                        if (/* ($user_level<0) || */
                                        $user->hasAccessSourceBySourceName($k)) {
                                            echo '
<div>
    <div class="form-check">
       <input id="input' . $k . '" type="checkbox" class="form-check-input source" ' . ((isset($_REQUEST['sources'][$k]) && $_REQUEST['sources'][$k]) || $s[2] > 1 ? 'checked' : '') . ' name="sources[' . $k . ']"> 
       <label for="input' . $k . '" class="form-check-label text-nowrap">' . $s[0] . '</label>
    </div>
</div>
';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col">
                                    <button class="btn btn-secondary btn-sm" type="button" id="selectall">Выбрать все
                                    </button>
                                    <button class="btn btn-secondary btn-sm" type="button" id="clearall">Снять все
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <div class="col-sm-3">&nbsp;</div>
                        <div class="col-sm-9">
                            <div class="form-check">
                                <input type="checkbox" <?= $_REQUEST['async'] ? 'checked' : ''; ?> name="async"
                                       id="async"
                                       class="form-check-input"/>
                                <label for="async" class="">Подгружать информацию по мере получения</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="mode" class="col-sm-3 col-form-label">Формат ответа:</label>
                        <div class="col-sm-4">
                            <select class="form-select" name="mode" id="mode">
                                <option value="xml">XML</option>
                                <option value="html" selected>HTML</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <div class="col-sm-3">&nbsp;</div>
                        <div class="col-sm-4">
                            <button id="submitbutton" type="submit" class="btn btn-primary">Найти</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php

if (!isset($_REQUEST['mode']) || (!\count($_REQUEST['sources']))) {
    echo '<div id="request">';
    echo '</div>';
    echo '<div id="response">';
    echo '</div>';
    return;
}

$xml = "
<Request>
        <UserIP>{$_SERVER['REMOTE_ADDR']}</UserIP>
        <UserID>{$user->getUserIdentifier()}</UserID>
        <Password>{$user->getPassword()}</Password>"
    . (!isset($_REQUEST['request_id']) || !$_REQUEST['request_id'] ? '' : "
        <requestId>{$_REQUEST['request_id']}</requestId>"
    ) . '
        <requestType>checkphone</requestType>
        <sources>' . \implode(',', \array_keys($_REQUEST['sources'])) . '</sources>
        <timeout>' . $form_timeout . '</timeout>
        <recursive>' . ($_REQUEST['recursive'] ? '1' : '0') . '</recursive>
        <async>' . ($_REQUEST['async'] ? '1' : '0') . "</async>
        <PhoneReq>
            <phone>{$_REQUEST['phone']}</phone>
        </PhoneReq>
</Request>";

if ('xml' === $_REQUEST['mode']) {
    ?>
    <div id="request">
        <div class="container-fluid">
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Запрос XML</h5>
                            <div class="card-text">
                                <textarea class="form-control" data-ace-editor="xml">
                                    <?= preg_replace(
                                        "/<Password>[^<]+<\/Password>/",
                                        '<Password>***</Password>',
                                        $xml
                                    ) ?>
                                </textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

$subRequest = Request::create($urlGenerator->generate(DefaultController::NAME), Request::METHOD_POST, content: $xml);
$subRequest->attributes->set('_controller', DefaultController::class);
$subRequest->setSession($mainRequest->getSession());
$response = $kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
$answer = $response->getContent();

?>
    <div id="response">
        <div class="container-fluid">
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-body">
                            <?php
                            if ('xml' == $_REQUEST['mode']) {
                                ?>
                                <h5 class="card-title">Ответ XML</h5>
                                <div class="card-text">
                                    <textarea class="form-control" data-ace-editor="xml">
                                        <?= $answer; ?>
                                    </textarea>
                                </div>
                                <?php
                            } else {
                                echo '<div class="card-text">';
                                $answer = \substr($answer, \strpos($answer, '<?xml'));
                                $doc = xml_transform($answer, 'isphere_view.xslt');
                                if ($doc) {
                                    $servicename = isset($servicenames[$_SERVER['HTTP_HOST']]) ? 'платформой ' . $servicenames[$_SERVER['HTTP_HOST']] : '';
                                    echo \strtr($doc->saveHTML(), ['$servicename' => $servicename]);
                                } else {
                                    echo $answer ? 'Некорректный ответ сервиса' : 'Нет ответа от сервиса';
                                }
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
<?php

if (\preg_match(
    "/<Response id=\"([\d]+)\" status=\"([\d]+)\" datetime=\"[^\"]+\" result=\"([^\"]+)\" view=\"([^\"]+)\"/",
    $answer,
    $matches
)) {
    $id = $matches[1];
    $status = $matches[2];
    $url = ('xml' == $_REQUEST['mode']) ? $matches[3] : $matches[4];
} else {
    $id = 0;
    $status = 1;
    $url = '';
}

echo '<input type="hidden" id="id" value="' . $id . '"/>';
echo '<input type="hidden" id="status" value="' . $status . '"/>';
echo '<input type="hidden" id="url" value="' . $url . '"/>';


<?php

use App\Controller\AdminController;
use App\Entity\AccessRoles;

require_once 'xml.php';

$mainRequest = $request;

$view->extend('base.php');

\set_time_limit($total_timeout + $http_timeout + 15);

$user_level = $user->getAccessLevel();
$user_sources = $user->getAccessSourcesMap();

// Источники (название,выбран,рекурсивный,конец строки)
$check_sources = [
    'hlr' => ['HLR', 1, 1, 0],
//  'infobip'=>array('Infobip',1,1,0),
    'smsc' => ['SMSC', 1, 1, 1],
    'vk' => ['VK', 1, 1, 0],
    'ok' => ['OK', 1, 1, 0],
    'facebook' => ['Facebook', 1, 1, 0],
    'instagram' => ['Instagram', 1, 1, 1],
    'skype' => ['Skype', 1, 1, 0],
    'whatsapp' => ['WhatsApp', 1, 1, 0],
    'telegram' => ['Telegram', 1, 1, 0],
//  'telegramweb'=>array('Telegram',1,1,0),
    'viber' => ['Viber', 1, 1, 1],
    'google' => ['Google', 1, 1, 0],
    'google_name' => ['Google имя', 1, 1, 0],
    'googleplus' => ['Google+', 1, 1, 1],
    'boards' => ['Boards', 1, 1, 0],
    'getcontactweb' => ['GetContact', 1, 1, 0],
    'getcontact' => ['GetContact', 1, 1, 0],
    'truecaller' => ['TrueCaller', 1, 1, 0],
    'emt' => ['EmobileTracker', 1, 1, 1],
    'callapp' => ['CallApp', 1, 1, 0],
    'simpler' => ['Simpler', 1, 1, 0],
    'numbuster' => ['NumBuster', 1, 1, 1],
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

$view['slots']->set('title', 'Проверка телефона 🇵🇱');

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
                            <input type="text" id="phone" name="phone" value="<?php echo $_REQUEST['phone']; ?>"
                                   required="1" class="form-control"
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
                                <input type="checkbox" class="form-check-input" <?php
                                echo $_REQUEST['recursive'] ? 'checked' : ''; ?> name="recursive" id="recursive">
                                <label for="recursive" class="form-check-label">Поиск по найденным контактам</label>
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
                            <input id="submitbutton" type="submit" value="Найти" class="btn btn-primary"/>
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
        <requestType>checkphone_pl</requestType>
        <sources>' . \implode(',', \array_keys($_REQUEST['sources'])) . '</sources>
        <recursive>' . ($_REQUEST['recursive'] ? '1' : '0') . '</recursive>
        <async>' . ($_REQUEST['async'] ? '1' : '0') . "</async>
        <PhoneReq>
            <phone>{$_REQUEST['phone']}</phone>
        </PhoneReq>
</Request>";

echo '<div id="request">';
if ('xml' == $_REQUEST['mode']) {
    echo 'Запрос XML: <textarea style="width:100%;height:30%">';
    $request = \preg_replace("/<Password>[^<]+<\/Password>/", '<Password>***</Password>', $xml);
    echo $request;
    echo '</textarea>';
    echo '<hr/>';
}
echo '</div>';

$ch = \curl_init();

\curl_setopt($ch, \CURLOPT_URL, $serviceurl . 'index.php');
\curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);
\curl_setopt($ch, \CURLOPT_TIMEOUT, $total_timeout + 10);
\curl_setopt($ch, \CURLOPT_HEADER, 0);
\curl_setopt($ch, \CURLOPT_SSL_VERIFYPEER, 0);
\curl_setopt($ch, \CURLOPT_SSL_VERIFYHOST, 0);
\curl_setopt($ch, \CURLOPT_POSTFIELDS, $xml);
\curl_setopt($ch, \CURLOPT_POST, 1);

$answer = \curl_exec($ch);
\curl_close($ch);

echo '<div id="response">';
if ('xml' == $_REQUEST['mode']) {
    echo 'Ответ XML: <textarea style="width:100%;height:70%">';
    echo $answer;
    echo '</textarea>';
} else {
    $answer = \substr($answer, \strpos($answer, '<?xml'));
    $doc = xml_transform($answer, 'isphere_view.xslt');
    if ($doc) {
        $servicename = isset($servicenames[$_SERVER['HTTP_HOST']]) ? 'платформой ' . $servicenames[$_SERVER['HTTP_HOST']] : '';
        echo \strtr($doc->saveHTML(), ['$servicename' => $servicename]);
    } else {
        echo $answer ? 'Некорректный ответ сервиса' : 'Нет ответа от сервиса';
    }
}
echo '</div>';

if (\preg_match("/<Response id=\"([\d]+)\" status=\"([\d]+)\" datetime=\"[^\"]+\" result=\"([^\"]+)\" view=\"([^\"]+)\"/", $answer, $matches)) {
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

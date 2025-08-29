<?php

include 'config_new.php';
include 'auth.php';
include 'xml.php';

$user_access = get_user_access($mysqli);
if (!$user_access['checkphone']) {
    echo 'У вас нет доступа к этой странице';
    return;
}

\set_time_limit($form_timeout + 30);

$user_level = get_user_level($mysqli);
$user_sources = get_user_sources($mysqli);

echo '<link rel="stylesheet" type="text/css" href="public/main.css"/>';
$user_message = get_user_message($mysqli);
if ($user_message) {
    echo '<span class="message">'.$user_message.'</span><hr/>';
}

// Источники (название,выбран,рекурсивный,конец строки)
$check_sources = [
  'test' => ['Тест', 0, 0, 0],
  'testr' => ['Тест RabbitMQ', 0, 0, 1],
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
  'hh' => ['HH', 1, 1, 0],
  'commerce' => ['Commerce', 1, 1, 0],
  'announcement' => ['Объявления', 1, 1, 0],
  'boards' => ['Boards', 1, 1, 1],
  'skype' => ['Skype', 1, 1, 0],
  'google' => ['Google', 1, 1, 0],
  'google_name' => ['Google имя', 1, 1, 0],
  'googleplus' => ['Google+', 1, 1, 1],
  'whatsapp' => ['WhatsApp', 1, 1, 0],
//  'whatsappweb'=>array('WhatsApp старый',1,1,0),
  'telegram' => ['Telegram', 1, 1, 0],
//  'telegramweb'=>array('Telegram',1,1,0),
//  'icq'=>array('ICQ',1,1,0),
  'viber' => ['Viber', 1, 1, 1],
  'yamap' => ['Яндекс.Карты', 1, 1, 0],
  '2gis' => ['2ГИС', 1, 1, 0],
  'egrul' => ['ЕГРЮЛ', 1, 1, 1],
//  'getcontactweb'=>array('GetContact',1,1,0),
  'getcontact' => ['GetContact', 1, 1, 0],
  'truecaller' => ['TrueCaller', 1, 1, 0],
  'emt' => ['EmobileTracker', 1, 1, 1],
  'callapp' => ['CallApp', 1, 1, 0],
  'simpler' => ['Simpler', 1, 1, 0],
  'numbuster' => ['NumBuster', 1, 1, 0],
//  'numbusterapp'=>array('NumBuster',1,1,0),
  'names' => ['Имена', 1, 1, 0],
  'phones' => ['Телефоны', 1, 1, 1],
//  'avinfo'=>array('AvInfo',1,1,0)),
//  'phonenumber'=>array('PhoneNumber',1,1,0),
//  'banks'=>array('Банки СБП',0,0,0),
//  'sber'=>array('Сбер Онлайн',0,0,0),
//  'sbertest'=>array('Сбербанк тест',0,0,0),
//  'sberbank'=>array('Сбербанк',0,1,1),
//  'vtb'=>array('ВТБ',0,1,0),
//  'tinkoff'=>array('Тинькофф',0,1,0),
//  'alfabank'=>array('Альфа-Банк',0,1,0),
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
//  'visa'=>array('VISA',1,1,0),
//  'webmoney'=>array('WebMoney',1,1,0),
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
    if (/* ($user_level<0) || */ isset($user_sources[$k]) && $user_sources[$k]) {
        if (!isset($_REQUEST['mode']) && !isset($_REQUEST['sources'][$k])) {
            $_REQUEST['sources'][$k] = 0;
        } // $s[1];
        //        if ($_REQUEST['recursive'] && $s[2]) $_REQUEST['sources'][$k] = 1;
    }
}

?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="public/main_new.js"></script>
<h1>Проверка телефона</h1><hr/><a href="admin.php">Назад</a><br/><br/>
<form id="checkform" method="POST">
    <table>
        <tr>
            <td>Номер телефона<span class="req">*</span></td>
            <td>
                <input type="text" name="phone" value="<?php echo $_REQUEST['phone']; ?>" required="1" maxlength="21" />
            </td>
        </tr>
        <tr>
            <td>Идентификатор запроса</td>
            <td>
                <input type="text" id="request_id" name="request_id" value="" maxlength="100" />
            </td>
        </tr>
        <tr>
            <td>Источники</td>
            <td>
<?php
$line = false;
foreach ($check_sources as $k => $s) {
    if (/* ($user_level<0) || */ isset($user_sources[$k]) && $user_sources[$k]) {
        echo '
               <input type="checkbox" class="source" '.((isset($_REQUEST['sources'][$k]) && $_REQUEST['sources'][$k]) || $s[2] > 1 ? 'checked' : '').' name="sources['.$k.']"> '.$s[0];
        $line = true;
    }
    if ($line && $s[3]) {
        echo '
               <br/>';
        $line = false;
    }
}
?>
                <button type="button" id="selectall">Выбрать все</button>
                <button type="button" id="clearall">Снять все</button>
            </td>
        </tr>
        <tr>
            <td>Поиск по найденным контактам</td>
            <td>
                <input type="checkbox" <?php echo $_REQUEST['recursive'] ? 'checked' : ''; ?> name="recursive" id="recursive">
	    </td>
        </tr>
        <tr>
            <td>Подгружать информацию по мере получения</td>
            <td>
                <input type="checkbox" <?php echo $_REQUEST['async'] ? 'checked' : ''; ?> name="async" id="async">
	    </td>
        </tr>
        <tr>
            <td>Формат ответа:</td>
            <td>
            <select name="mode" id="mode">
                <option value="xml">XML</option>
                <option value="html" selected>HTML</option>
            </select>
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <p class="small-font">Нажимая кнопку "Найти", вы поручаете ООО "Инфо сфера" обработать введенные данные согласно условиям договора.</p>
                <input id="submitbutton" type="submit" value="Найти">
            </td>
        </tr>
    </table>
</form>

<hr/>

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
.(!isset($_REQUEST['request_id']) || !$_REQUEST['request_id'] ? '' : "
        <requestId>{$_REQUEST['request_id']}</requestId>"
).'
        <requestType>checkphone</requestType>
        <sources>'.\implode(',', \array_keys($_REQUEST['sources'])).'</sources>
        <timeout>'.$form_timeout.'</timeout>
        <recursive>'.($_REQUEST['recursive'] ? '1' : '0').'</recursive>
        <async>'.($_REQUEST['async'] ? '1' : '0')."</async>
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

\curl_setopt($ch, \CURLOPT_URL, $serviceurl.'index_new.php');
\curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);
\curl_setopt($ch, \CURLOPT_TIMEOUT, $form_timeout + 10);
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
    $doc = xml_transform($answer, 'isphere_view_new.xslt');
    if ($doc) {
        $servicename = isset($servicenames[$_SERVER['HTTP_HOST']]) ? 'платформой '.$servicenames[$_SERVER['HTTP_HOST']] : '';
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

echo '<input type="hidden" id="id" value="'.$id.'"/>';
echo '<input type="hidden" id="status" value="'.$status.'"/>';
echo '<input type="hidden" id="url" value="'.$url.'"/>';

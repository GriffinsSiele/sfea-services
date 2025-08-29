<?php

include('config_new.php');
include('auth_new.php');
include("xml.php");

$user_access = get_user_access($mysqli);
if (!$user_access['checkphone']) {
    echo 'У вас нет доступа к этой странице';
    exit;
}

set_time_limit($form_timeout+30);

$user_level = get_user_level($mysqli);
$user_sources = get_user_sources($mysqli);

if (isset($_GET['exception'])) {
    throw new \RuntimeException($_GET['exception']);
}

echo '<link rel="stylesheet" type="text/css" href="main.css"/>';
$user_message = get_user_message($mysqli);
if ($user_message) {
    echo '<span class="message">'.$user_message.'</span><hr/>';
}

// Источники (название,выбран,рекурсивный,конец строки)
$check_sources = array(
  'test'=>array('Тест',0,0,0),
  'testr'=>array('Тест RabbitMQ',0,0,1),
  'gosuslugi_phone'=>array('Госуслуги',1,0,0),
  'rossvyaz'=>array('Россвязь',1,1,1),
  'hlr'=>array('HLR',1,1,0),
//  'infobip'=>array('Infobip',1,1,0),
  'smsc'=>array('SMSC',1,1,1),
//  'people'=>array('Соцсети',1,0,0),
//  'beholder'=>array('Beholder',1,1,0),
  'vk'=>array('VK',1,1,0),
  'ok'=>array('OK',1,1,0),
  'fotostrana'=>array('Фотострана',1,1,0),
  'mailru'=>array('Mail.Ru',1,1,1),
  'twitter'=>array('Twitter',1,1,0),
  'facebook'=>array('Facebook',1,1,0),
  'instagram'=>array('Instagram',1,1,1),
  'hh'=>array('HH',1,1,0),
  'commerce'=>array('Commerce',1,1,0),
  'announcement'=>array('Объявления',1,1,0),
  'boards'=>array('Boards',1,1,1),
  'microsoft'=>array('Microsoft',1,1,0),
  'skype'=>array('Skype',1,1,0),
  'google'=>array('Google',1,1,0),
  'google_name'=>array('Google имя',1,1,0),
  'googleplus'=>array('Google+',1,1,1),
  'xiaomi'=>array('Xiaomi',1,1,0),
  'huawei'=>array('Huawei',1,1,0),
  'honor'=>array('Honor',1,1,0),
  'samsung'=>array('Samsung',1,1,0),
  'apple'=>array('Apple',1,1,1),
  'whatsapp'=>array('WhatsApp',1,1,0),
//  'whatsappweb'=>array('WhatsApp старый',1,1,0),
  'telegram'=>array('Telegram',1,1,0),
//  'telegramweb'=>array('Telegram',1,1,0),
//  'icq'=>array('ICQ',1,1,0),
  'viber'=>array('Viber',1,1,1),
  'yamap'=>array('Яндекс.Карты',1,1,0),
  '2gis'=>array('2ГИС',1,1,0),
  'egrul'=>array('ЕГРЮЛ',1,1,1),
//  'getcontactweb'=>array('GetContact',1,1,0),
  'getcontact'=>array('GetContact',1,1,0),
  'getcontacttags'=>array('GetContact Теги',1,1,0),
  'truecaller'=>array('TrueCaller',1,1,1),
  'numbuster'=>array('NumBuster',1,1,0),
//  'numbusterapp'=>array('NumBuster',1,1,0),
  'numbusterpro'=>array('NumBuster Pro',1,1,0),
  'emt'=>array('EmobileTracker',1,1,1),
  'callapp'=>array('CallApp',1,1,0),
  'simpler'=>array('Simpler',1,1,0),
  'eyecon'=>array('EyeCon',1,1,0),
  'viewcaller'=>array('ViewCaller',1,1,0),
  'names'=>array('Имена',1,1,0),
  'phones'=>array('Телефоны',1,1,1),
//  'avinfo'=>array('AvInfo',1,1,0)),
//  'phonenumber'=>array('PhoneNumber',1,1,0),
//  'banks'=>array('Банки СБП',0,0,0),
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
  'pochta'=>array('Почта',1,1,0),
  'yoomoney'=>array('ЮMoney',1,1,0),
//  'domclick'=>array('Домклик',1,1,0),
  'sber'=>array('Сбер',1,1,1),
  'aeroflot'=>array('Аэрофлот',1,1,0),
//  'uralair'=>array('Уральские авиалинии',1,1,1),
//  'biglion'=>array('Биглион',1,1,0),
  'rosneft'=>array('Роснефть',1,1,0),
  'papajohns'=>array('Папа Джонс',1,1,0),
  'litres'=>array('Литрес',1,1,0),
  'avito'=>array('Авито',1,1,1),
  'domru'=>array('Дом.ру',1,1,0),
  'krasnoebeloe'=>array('Красное Белое',1,1,0),
  'winelab'=>array('Винлаб',1,1,0),
  'petrovich'=>array('Петрович',1,1,1),
//  'rz'=>array('Реестр залогов',1,0,0),
  'reestrzalogov'=>array('Реестр залогов',1,0,0),
  'fssp'=>array('ФССП',1,0,0),
  'fssp_suspect'=>array('ФССП розыск',1,0,0),
  'gisgmp'=>array('ГИС ГМП',1,0,1),
  'bankrot'=>array('Банкроты',1,0,0),
  'terrorist'=>array('Террористы',1,0,0),
  'mvd'=>array('МВД',1,0,1),
);

if (!isset($_REQUEST['phone'])) $_REQUEST['phone']='';
if (!isset($_REQUEST['sources'])) $_REQUEST['sources']=array();
if (!isset($_REQUEST['recursive'])) $_REQUEST['recursive']=0;
if (!isset($_REQUEST['async'])) $_REQUEST['async']=($_SERVER['REQUEST_METHOD']=='POST'?0:1);

foreach ($_REQUEST as $rn => $rv) $_REQUEST[$rn] = preg_replace("/[<>\/]/","",$rv);

foreach ($check_sources as $k => $s) {
    if (/*($user_level<0) || */(isset($user_sources[$k]) && $user_sources[$k])) {
        if (!isset($_REQUEST['mode']) && !isset($_REQUEST['sources'][$k])) $_REQUEST['sources'][$k] = 0; //$s[1];
//        if ($_REQUEST['recursive'] && $s[2]) $_REQUEST['sources'][$k] = 1;
    }
}

?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="main_new.js"></script>
<h1>Проверка телефона</h1><hr/><a href="admin.php">Назад</a><br/><br/>
<form id="checkform" method="POST">
    <table>
        <tr>
            <td>Номер телефона<span class="req">*</span></td>
            <td>
                <input type="text" name="phone" value="<?=$_REQUEST['phone']?>" required="1" maxlength="21" />
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
    if (/*($user_level<0) || */(isset($user_sources[$k]) && $user_sources[$k])) {
        echo '
               <input type="checkbox" class="source" '.((isset($_REQUEST['sources'][$k]) && $_REQUEST['sources'][$k]) || $s[2]>1 ? 'checked': '').' name="sources['.$k.']"> '.$s[0];
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
                <input type="checkbox" <?=($_REQUEST['recursive'] ? 'checked': '')?> name="recursive" id="recursive">
	    </td>
        </tr>
        <tr>
            <td>Подгружать информацию по мере получения</td>
            <td>
                <input type="checkbox" <?=($_REQUEST['async'] ? 'checked': '')?> name="async" id="async">
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
<?php
if ($_SERVER['HTTP_HOST']=='i-sphere.ru') {
    echo '
                <p class="small-font">Нажимая кнопку "Найти", Вы выражаете согласие с <a href="/rules" target="_blank">правилами пользования порталом</a><br/>и поручаете обработку введённых данных в соответствии с условиями договора (соглашения).</p>';
}
?>
                <input id="submitbutton" type="submit" value="Найти">
            </td>
        </tr>
    </table>
</form>

<hr/>

<?php

if(!isset($_REQUEST['mode']) || (!sizeof($_REQUEST['sources']))) {
    print '<div id="request">';
    print '</div>';
    print '<div id="response">';
    print '</div>';
    include('footer.php');
    exit();
}

$xml ="
<Request>
        <UserIP>{$_SERVER['REMOTE_ADDR']}</UserIP>
        <UserID>{$_SERVER['PHP_AUTH_USER']}</UserID>
        <Password>{$_SERVER['PHP_AUTH_PW']}</Password>"
. (!isset($_REQUEST['request_id']) || !$_REQUEST['request_id']? "" : "
        <requestId>{$_REQUEST['request_id']}</requestId>"
) . "
        <requestType>checkphone</requestType>
        <sources>".implode(',',array_keys($_REQUEST['sources']))."</sources>
        <timeout>".$form_timeout."</timeout>
        <recursive>".($_REQUEST['recursive']?'1':'0')."</recursive>
        <async>".($_REQUEST['async']?'1':'0')."</async>
        <PhoneReq>
            <phone>{$_REQUEST['phone']}</phone>
        </PhoneReq>
</Request>";

print '<div id="request">';
if ($_REQUEST['mode']=='xml') {
    print 'Запрос XML: <textarea style="width:100%;height:30%">';
    $request = preg_replace("/<Password>[^<]+<\/Password>/", "<Password>***</Password>", $xml);
    print $request;
    print '</textarea>';
    print "<hr/>";
}
print '</div>';

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $serviceurl.'index_new.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, $form_timeout+10);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
curl_setopt($ch, CURLOPT_POST, 1);

$answer = curl_exec($ch);
curl_close($ch);

print '<div id="response">';
if ($_REQUEST['mode']=='xml') {
    print 'Ответ XML: <textarea style="width:100%;height:70%">';
    print $answer;
    print '</textarea>';
} else {
    $answer = substr($answer,strpos($answer,'<?xml'));
    $doc = xml_transform($answer, 'isphere_view_new.xslt');
    if ($doc) {
        $servicename = isset($servicenames[$_SERVER['HTTP_HOST']])?'платформой '.$servicenames[$_SERVER['HTTP_HOST']]:'';
        $login = $_SERVER['PHP_AUTH_USER'];
        $html = strtr($doc->saveHTML(),array('___servicename___'=>$servicename,'___login___'=>$login));
        echo $html;
    } else  {
        echo $answer?'Некорректный ответ сервиса':'Нет ответа от сервиса';
    }
}
print '</div>';

if (preg_match("/<Response id=\"([\d]+)\" status=\"([\d]+)\" datetime=\"[^\"]+\" result=\"([^\"]+)\" view=\"([^\"]+)\"/",$answer,$matches)) {
    $id = $matches[1];
    $status = $matches[2];
    $url = ($_REQUEST['mode']=='xml')?$matches[3]:$matches[4];
} else {
    $id = 0;
    $status = 1;
    $url = '';
}

print '<input type="hidden" id="id" value="'.$id.'"/>';
print '<input type="hidden" id="status" value="'.$status.'"/>';
print '<input type="hidden" id="url" value="'.$url.'"/>';

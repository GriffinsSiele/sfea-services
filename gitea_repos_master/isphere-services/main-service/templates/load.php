<?php

include 'config.php';
include 'auth.php';
include 'xml.php';
// require('user/functions.php');

// print_r($_SERVER);
/*
if($_SERVER['HTTP_HOST'] == 'my.infohub24.ru'){
    echo '<br /><br /><a href="bulkAuto/stepOne.php">for infoHub new location - push me</a>';
    return;
}
*/

$user_access = get_user_access($mysqli);
if (!$user_access['bulk']) {
    echo 'У вас нет доступа к этой странице';
    return;
}

// function get_site($mysqli, $host){
//   if($result = mysqli_query($mysqli, "SELECT * FROM Site WHERE host='".$host."' LIMIT 1")){
//        return $result->fetch_assoc();
//        mysqli_free_result($result);
//   }
//   return false;
// }

// function getUserEmail($mysqli, $usrId){
//      $result = mysqli_query($mysqli, 'SELECT * FROM SystemUsers WHERE `Id`=\''.$usrId.'\' LIMIT 1');
//           if($result->num_rows){
//               while($row = $result->fetch_assoc()){
//                   $userRow = $row;
//               }
//           }
//           mysqli_free_result($result);
//           return $userRow;
// }

// $host = $_SERVER['HTTP_HOST'];
// $siteInfo = get_site($mysqli, $host);

// $userInfo = getUserEmail($mysqli, $_SESSION['userid']);

\set_time_limit($total_timeout * 10);

$user_level = get_user_level($mysqli);
$user_sources = get_user_sources($mysqli);

echo '<link rel="stylesheet" type="text/css" href="public/main.css"/>';

// Источники (название,выбран,рекурсивный,конец строки)
$check_sources = [
  'search' => ['Search', 1, 0, 0],
//  'fssp'=>array('ФССП',1,0,0),
//  'fsspsite'=>array('ФССП (сайт)',1,0,0),
  'fssp_suspect' => ['ФССП розыск', 1, 0, 0],
  'fms' => ['ФМС', 1, 0, 0],
  'fmsdb' => ['ФМС БД', 1, 0, 0],
  'mvd' => ['МВД', 1, 0, 1],
  'gosuslugi_passport' => ['Госуслуги паспорт', 1, 0, 0],
  'gosuslugi_inn' => ['Госуслуги ИНН', 1, 0, 1],
  'gosuslugi_phone' => ['Госуслуги телефон', 1, 0, 0],
  'gosuslugi_email' => ['Госуслуги e-mail', 1, 0, 1],
  'fns' => ['ФНС', 1, 0, 0],
  'gisgmp' => ['ГИС ГМП', 1, 0, 0],
//  'notariat'=>array('Нотариат',1,0,1),
//  'bankrot'=>array('Банкроты',1,0,0),
  'cbr' => ['ЦБ РФ', 1, 0, 0],
  'terrorist' => ['Террористы', 1, 0, 1],
  'reestrzalogov' => ['Реестр залогов', 0, 0, 0],
  'eaisto' => ['ЕАИСТО', 1, 0, 0],
  'avtokod' => ['Автокод', 0, 0, 1],
  'gibdd_history' => ['ГИБДД история', 1, 0, 0],
  'gibdd_aiusdtp' => ['ГИБДД дтп', 1, 0, 1],
  'gibdd_restricted' => ['ГИБДД ограничения', 1, 0, 0],
  'gibdd_wanted' => ['ГИБДД розыск', 1, 0, 1],
  'gibdd_diagnostic' => ['ГИБДД техосмотр', 1, 0, 0],
  'gibdd_fines' => ['ГИБДД штрафы', 0, 0, 1],
  'gibdd_driver' => ['ГИБДД права', 1, 0, 0],
  'rsa_kbm' => ['РСА КБМ', 1, 0, 1],
  'rsa_policy' => ['РСА авто', 1, 0, 0],
//  'rsa_osagovehicle'=>array('РСА полис',1,0,0),
  'rsa_bsostate' => ['РСА бланк', 1, 0, 1],
//  'people'=>array('Соцсети',1,0,0),
//  'beholder'=>array('Beholder',1,1,0),
  'vk' => ['VK', 1, 1, 0],
  'ok' => ['OK', 1, 1, 0],
  'mailru' => ['Mail.Ru', 1, 1, 1],
  'twitter' => ['Twitter', 1, 1, 0],
  'facebook' => ['Facebook', 1, 1, 0],
  'instagram' => ['Instagram', 1, 1, 1],
  'rossvyaz' => ['Россвязь', 1, 1, 0],
  'hlr' => ['HLR', 1, 1, 0],
//  'infobip'=>array('Infobip',1,1,0),
  'smsc' => ['SMSC', 1, 1, 1],
  'hh' => ['HH', 1, 1, 0],
//  'commerce'=>array('Commerce',1,1,0),
  'announcement' => ['Объявления', 1, 1, 0],
  'boards' => ['Boards', 1, 1, 1],
  'skype' => ['Skype', 1, 1, 0],
  'apple' => ['Apple', 1, 1, 0],
  'google' => ['Google', 1, 1, 0],
  'googleplus' => ['Google+', 1, 1, 1],
  'whatsapp' => ['WhatsApp', 1, 1, 0],
  'telegram' => ['Telegram', 1, 1, 0],
  'viber' => ['Viber', 1, 1, 1],
  'yamap' => ['Яндекс.Карты', 1, 1, 0],
  '2gis' => ['2ГИС', 1, 1, 0],
  'egrul' => ['ЕГРЮЛ', 1, 1, 0],
//  'kad'=>array('Арбитражный суд',1,0,0),
  'zakupki' => ['Госзакупки', 1, 0, 1],
  'getcontact' => ['GetContact', 1, 1, 0],
  'truecaller' => ['TrueCaller', 1, 1, 0],
  'emt' => ['EmobileTracker', 1, 1, 1],
  'callapp' => ['CallApp', 1, 1, 0],
  'simpler' => ['Simpler', 1, 1, 0],
  'numbuster' => ['NumBuster', 1, 1, 0],
  'names' => ['Имена', 1, 1, 0],
  'phones' => ['Телефоны', 1, 1, 1],
//  'avinfo'=>array('AvInfo',1,1,0)),
//  'phonenumber'=>array('PhoneNumber',1,1,0),
//  'banks'=>array('Банки',0,0,0),
//  'sbertest'=>array('Сбербанк тест',0,1,0),
//  'sber'=>array('Сбер Онлайн',0,0,0),
//  'sberbank'=>array('Сбербанк',1,1,1),
//  'alfabank'=>array('Альфа-Банк',1,1,0),
//  'tinkoff'=>array('Tinkoff',1,1,1),
//  'psbank'=>array('Промсвязьбанк',1,1,0),
//  'rosbank'=>array('Росбанк',1,1,0),
//  'raiffeisen'=>array('Райффайзен',1,1,1),
//  'visa'=>array('VISA',1,1,0),
//  'sovcombank'=>array('Совкомбанк',1,1,0),
//  'gazprombank'=>array('Газпромбанк',1,1,0),
//  'qiwibank'=>array('КИВИ Банк',1,1,1),
//  'webmoney'=>array('WebMoney',1,1,0),
//  'elecsnet'=>array('Элекснет',1,1,0),
//  'qiwi'=>array('Qiwi',1,1,0),
//  'yamoney'=>array('Яндекс.Деньги',1,1,1),
//  'elecsnet'=>array('Элекснет',1,1,1),
  'rzd' => ['РЖД', 1, 1, 0],
  'aeroflot' => ['Аэрофлот', 1, 1, 0],
//  'uralair'=>array('Уральские авиалинии ',1,1,1),
  'avito' => ['Авито', 1, 1, 0],
//  'biglion'=>array('Биглион',1,1,0),
  'papajohns' => ['Папа Джонс', 1, 1, 1],
];

if (!isset($_REQUEST['sources'])) {
    $_REQUEST['sources'] = [];
}
if (!isset($_REQUEST['recursive'])) {
    $_REQUEST['recursive'] = 0;
}
if (!isset($_REQUEST['async'])) {
    $_REQUEST['async'] = 0;
}

foreach ($check_sources as $k => $s) {
    if (/* ($user_level<0) || */ isset($user_sources[$k]) && $user_sources[$k]) {
        //        if (!isset($_REQUEST['mode']) && !isset($_REQUEST['sources'][$k])) $_REQUEST['sources'][$k] = $s[1];
        //        if ($_REQUEST['recursive'] && $s[2]) $_REQUEST['sources'][$k] = 1;
    }
}

if (isset($_REQUEST['mode']) && isset($_REQUEST['filename']) && isset($_REQUEST['sources']) && \count($_REQUEST['sources'])) {
    echo '<meta http-equiv="Refresh" content="30;bulk.php"/>';
}

?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="public/main.js"></script>
<h1>Проверка по реестру</h1><hr/><a href="bulk.php">Назад</a><br/><br/>
<br/><b>Внимание! Теперь пакетная обработка запросов в ФНС (поиск ИНН), ФССП, ЕФРСБ (банкроты) и Нотариат (наследственные дела) производится в автоматическом режиме через новый интерфейс!</b><br/>
<a href="bulkAuto/stepOne.php">Загрузить новый реестр на обработку по источникам ФНС (поиск ИНН), ФССП, ЕФРСБ (банкроты), Нотариат (наследственные дела)</a><br/><br/>
<form id="loadform" method="POST">
    <table>
        <tr>
            <td>
                <label for="file">Реестр для обработки<br/><b>(excel или csv не менее 100 строк<br/>и размером не более 30 Мб)</b></label>
            </td>
            <td id="forfiles">
                <input type="file" id="thefile" name="file" value="Файл"/>
                <input type="hidden" id="filename" name="filename" style="display:none;" readonly/>
                <img id="loading" src="wait.gif" style="display:none;"/>
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
               <input type="checkbox" class="source"  name="sources['/* ((isset($_REQUEST['sources'][$k]) && $_REQUEST['sources'][$k]) || $s[2]>1 ? 'checked': ''). */.$k.']"> '.$s[0];
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
        <!--tr>
            <td>Поиск по найденным контактам</td>
            <td>
                <input type="checkbox" <?php echo $_REQUEST['recursive'] ? 'checked' : ''; ?> name="recursive">
	    </td>
        </tr-->
        <tr>
            <td>Формат ответа:</td>
            <td>
            <select name="mode">
                <!--option value="csv">CSV</option-->
                <option value="xls" selected>Excel</option>
            </select>
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <input type="hidden" id="ts" name="ts" value="">
                <input type="submit" value="Обработать реестр">
            </td>
        </tr>
    </table>
</form>

<br/><b>Возможны задержки, т.к. обработка реестров производится вручную силами наших специалистов.<br/>Они пока учат роботов понимать, что от них требуется, и обрабатывать данные быстро и без ошибок!</b><br/>
<br/>

<hr/>


<?php

if (!isset($_REQUEST['mode'])) {
    return;
}

if (!isset($_REQUEST['filename']) || !$_REQUEST['filename']) {
    echo 'Не выбран файл для загрузки<br/>';
    return;
}

$sources = $_REQUEST['sources'];
if (!\count($sources)) {
    echo 'Не выбраны источники для обработки<br/>';
    return;
}

$userid = get_user_id($mysqli);
$mysqli->query("INSERT INTO Bulk (created_date, ip, user_id, filename, sources, `recursive`, status) VALUES (CURRENT_DATE,'".$_SERVER['REMOTE_ADDR']."',$userid,'".$_REQUEST['filename']."','".\implode(',', \array_keys($_REQUEST['sources']))."',".($_REQUEST['recursive'] ? '1' : '0').',2)');
$id = $mysqli->insert_id;
$uploaddir = '/opt/bulk/';
$dir = $uploaddir.$id;
if (!\is_dir($dir)) {
    \mkdir($dir, 0777);
}
\rename($uploaddir.$_REQUEST['ts'].'.zip', $dir.'/request.zip');

echo 'Реестр '.$_REQUEST['filename'].' отправлен на обработку<br/><br/>';
echo '<a href="bulk.php">Перейти к результатам</a><br/>';

$serviceurl = 'https://api.telegram.org/bot2103347962:AAHMdZY-Bh6ELR-NB7qOapnnD7sbh2c3bsQ/sendMessage?chat_id=-1001662664995';
$msg = 'Загружен реестр '.$id.' в файле '.$_REQUEST['filename'].' от пользователя '.$user->getUserIdentifier().' по источникам '.\implode(',', \array_keys($_REQUEST['sources']));

$ch = \curl_init();
\curl_setopt($ch, \CURLOPT_URL, $serviceurl);
\curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);
\curl_setopt($ch, \CURLOPT_TIMEOUT, 10);
\curl_setopt($ch, \CURLOPT_HEADER, 0);
\curl_setopt($ch, \CURLOPT_SSL_VERIFYPEER, 0);
\curl_setopt($ch, \CURLOPT_SSL_VERIFYHOST, 0);
\curl_setopt($ch, \CURLOPT_POSTFIELDS, 'text='.\urlencode($msg));
\curl_setopt($ch, \CURLOPT_POST, 1);

$data = \curl_exec($ch);

$answer = $data;

\curl_close($ch);

// if($id > 0){
// @ $sm = sendMail($siteInfo['email_user'], $siteInfo['email_password'], $host, $userInfo['Email'], $userInfo['Name'], 'BULK UPLOAD', 'OK', 1);
// }

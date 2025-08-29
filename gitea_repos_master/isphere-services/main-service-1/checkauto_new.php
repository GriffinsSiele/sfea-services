<?php

include('config_new.php');
include('auth_new.php');
include("xml.php");

$user_access = get_user_access($mysqli);
if (!$user_access['checkauto']) {
    echo 'У вас нет доступа к этой странице';
    exit;
}

set_time_limit($form_timeout+30);

$user_level = get_user_level($mysqli);
$user_sources = get_user_sources($mysqli);

echo '<link rel="stylesheet" type="text/css" href="main.css"/>';
$user_message = get_user_message($mysqli);
if ($user_message) {
    echo '<span class="message">'.$user_message.'</span><hr/>';
}

// Источники (название,выбран,рекурсивный,конец строки)
$check_sources = array(
  'test'=>array('Тест',0,0,0),
  'testr'=>array('Тест RabbitMQ',0,0,1),
  'gibdd_history'=>array('ГИБДД история',1,0,0),
  'gibdd_register'=>array('ГИБДД регистрация',1,0,0),
  'gibdd_aiusdtp'=>array('ГИБДД дтп',1,0,1),
  'gibdd_restricted'=>array('ГИБДД ограничения',1,0,0),
  'gibdd_wanted'=>array('ГИБДД розыск',1,0,1),
  'gibdd_diagnostic'=>array('ГИБДД техосмотр',1,0,0),
  'gibdd_fines'=>array('ГИБДД штрафы',0,0,1),
  'gibdd_driver'=>array('ГИБДД права',1,0,0),
  'rsa_policy'=>array('РСА авто',1,0,0),
  'rsa_bsostate'=>array('РСА полис',1,0,0),
  'rsa_kbm'=>array('РСА КБМ',1,0,1),
  'carinfo'=>array('CarInfo',1,0,0),
  'alfastrah'=>array('Альфастрахование',1,0,0),
  'eaisto'=>array('ЕАИСТО',1,0,0),
  'avtokod'=>array('Автокод',1,0,0),
  'gisgmp'=>array('ГИС ГМП',1,0,1),
  'elpts'=>array('ЭПТС',1,0,0),
  'rz'=>array('Реестр залогов старый без pdf',1,0,1),
  'reestrzalogov'=>array('Реестр залогов',1,0,0),
//  'avinfo'=>array('AvInfo',1,1,0)),
//  'vin'=>array('Расшифровка VIN',1,1,0),
  'fssp'=>array('ФССП',1,0,1),
);

if (!isset($_REQUEST['vin'])) $_REQUEST['vin']='';
if (!isset($_REQUEST['bodynum'])) $_REQUEST['bodynum']='';
if (!isset($_REQUEST['chassis'])) $_REQUEST['chassis']='';
if (!isset($_REQUEST['regnum'])) $_REQUEST['regnum']='';
if (!isset($_REQUEST['ctc'])) $_REQUEST['ctc']='';
if (!isset($_REQUEST['pts'])) $_REQUEST['pts']='';
if (!isset($_REQUEST['osago'])) $_REQUEST['osago']='';
if (!isset($_REQUEST['reqdate'])) $_REQUEST['reqdate']='';
if (!isset($_REQUEST['driver_number'])) $_REQUEST['driver_number']='';
if (!isset($_REQUEST['driver_date'])) $_REQUEST['driver_date']='';
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
<h1>Проверка автомобиля</h1><hr/><a href="admin.php">Назад</a><br/><br/>
<form id="checkform" method="POST">
    <table>
        <tr>
            <td></td>
            <td>
                <button type="button" id="clear">Очистить</button>
	    </td>
        </tr>
        <tr>
            <td>VIN<span></span></td>
            <td>
                <input type="text" name="vin" value="<?=$_REQUEST['vin']?>" maxlength="17" />
            </td>
        </tr>
        <tr>
            <td>Номер кузова<span></span></td>
            <td>
                <input type="text" name="bodynum" value="<?=$_REQUEST['bodynum']?>" maxlength="20" />
            </td>
        </tr>
        <tr>
            <td>Номер шасси<span></span></td>
            <td>
                <input type="text" name="chassis" value="<?=$_REQUEST['chassis']?>" maxlength="20" />
            </td>
        </tr>
        <tr>
            <td>Гос.номер</td>
            <td>
                <input type="text" name="regnum" value="<?=$_REQUEST['regnum']?>" maxlength="10" />
            </td>
        </tr>
        <tr>
            <td>Св-во о регистрации ТС</td>
            <td>
                <input type="text" name="ctc" value="<?=$_REQUEST['ctc']?>" maxlength="10" />
            </td>
        </tr>
        <tr>
            <td>Паспорт ТС</td>
            <td>
                <input type="text" name="pts" value="<?=$_REQUEST['pts']?>" maxlength="15" />
            </td>
        </tr>
        <tr>
            <td>Полис ОСАГО</td>
            <td>
                <input type="text" name="osago" value="<?=$_REQUEST['osago']?>" maxlength="100" />
            </td>
        </tr>
        <tr>
            <td>Дата запроса в РСА</td>
            <td>
                <input type="date" id="reqdate" name="reqdate" value="<?=$_REQUEST['reqdate']?>" data-type="date" maxlength="50" />
            </td>
        </tr>
        <tr>
            <td>Номер в/у</td>
            <td>
                <input type="text" name="driver_number" value="<?=$_REQUEST['driver_number']?>" maxlength="12" />
            </td>
        </tr>
        <tr>
	     <td>Дата выдачи в/у</td>
	     <td>
	         <input type="date" name="driver_date" value="<?=$_REQUEST['driver_date']?>" data-type="date" maxlength="10" />
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

$xml =
"<Request>
        <UserIP>{$_SERVER['REMOTE_ADDR']}</UserIP>
        <UserID>{$_SERVER['PHP_AUTH_USER']}</UserID>
        <Password>{$_SERVER['PHP_AUTH_PW']}</Password>"
. (!isset($_REQUEST['request_id']) || !$_REQUEST['request_id']? "" : "
        <requestId>{$_REQUEST['request_id']}</requestId>"
) . "
        <requestType>checkauto</requestType>
        <sources>".implode(',',array_keys($_REQUEST['sources']))."</sources>
        <timeout>".$form_timeout."</timeout>
        <recursive>".($_REQUEST['recursive']?'1':'0')."</recursive>
        <async>".($_REQUEST['async']?'1':'0')."</async>"
. (!$_REQUEST['driver_number'] ? "" : "
        <PersonReq>
            <driver_number>{$_REQUEST['driver_number']}</driver_number>"
. (!$_REQUEST['driver_date'] ? "" : "
            <driver_date>{$_REQUEST['driver_date']}</driver_date>"
) . "
        </PersonReq>"
) . (!$_REQUEST['vin'] && !$_REQUEST['bodynum'] && !$_REQUEST['chassis'] && !$_REQUEST['regnum'] && !$_REQUEST['ctc'] && !$_REQUEST['pts']? "" : "
        <CarReq>"
. (!$_REQUEST['vin'] ? "" : "
            <vin>{$_REQUEST['vin']}</vin>"
)
. (!$_REQUEST['bodynum'] ? "" : "
            <bodynum>{$_REQUEST['bodynum']}</bodynum>"
)
. (!$_REQUEST['chassis'] ? "" : "
            <chassis>{$_REQUEST['chassis']}</chassis>"
)
. (!$_REQUEST['regnum'] ? "" : "
            <regnum>{$_REQUEST['regnum']}</regnum>"
)
. (!$_REQUEST['ctc'] ? "" : "
            <ctc>{$_REQUEST['ctc']}</ctc>"
)
. (!$_REQUEST['pts'] ? "" : "
            <pts>{$_REQUEST['pts']}</pts>"
)
. (!$_REQUEST['reqdate'] ? "" : "
            <reqdate>{$_REQUEST['reqdate']}</reqdate>"
) . "
        </CarReq>"
) . (!isset($_REQUEST['osago']) || !$_REQUEST['osago']? "" : "
        <OtherReq>"
. (!$_REQUEST['osago'] ? "" : "
            <osago>{$_REQUEST['osago']}</osago>"
) . "
        </OtherReq>"
) . "
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
        echo strtr($doc->saveHTML(),array('$servicename'=>$servicename));
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

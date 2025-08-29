<?php

include('config.php');
include('auth.php');
include("xml.php");

$user_access = get_user_access($mysqli);
if (!$user_access['check']) {
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
  'gosuslugi_inn'=>array('Госуслуги',1,0,0),
  'fns'=>array('ФНС',1,0,0),
  'gisgmp'=>array('ГИС ГМП',1,0,0),
  'bankrot'=>array('Банкроты',1,0,0),
  'cbr'=>array('ЦБ РФ',1,0,0),
  'reestrzalogov'=>array('Реестр залогов',0,0,0),
  'egrul'=>array('ЕГРЮЛ',1,1,0),
  'kad'=>array('Арбитражный суд',1,0,0),
  'zakupki'=>array('Госзакупки',1,0,1),
);

if (!isset($_REQUEST['inn'])) $_REQUEST['inn']='';
if (!isset($_REQUEST['sources'])) $_REQUEST['sources']=array();
if (!isset($_REQUEST['recursive'])) $_REQUEST['recursive']=0;
if (!isset($_REQUEST['async'])) $_REQUEST['async']=($_SERVER['REQUEST_METHOD']=='POST'?0:1);

foreach ($_REQUEST as $rn => $rv) $_REQUEST[$rn] = preg_replace("/[<>\/]/","",$rv);

foreach ($check_sources as $k => $s) {
    if (/*($user_level<0) || */(isset($user_sources[$k]) && $user_sources[$k])) {
        if (!isset($_REQUEST['mode']) && !isset($_REQUEST['sources'][$k])) $_REQUEST['sources'][$k] = $s[1];
//        if ($_REQUEST['recursive'] && $s[2]) $_REQUEST['sources'][$k] = 1;
    }
}

?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="main.js"></script>
<h1>Проверка ИНН</h1><hr/><a href="admin.php">Назад</a><br/><br/>
<form id="checkform" method="POST">
    <table>
        <tr>
            <td>ИНН физлица<span class="req">*</span></td>
            <td>
                <input type="text" name="inn" value="<?=$_REQUEST['inn']?>" required="1" maxlength="12" />
            </td>
        </tr>
        <!--tr>
            <td>Идентификатор запроса</td>
            <td>
                <input type="text" id="request_id" name="request_id" value="" maxlength="100" />
            </td>
        </tr-->
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
        <!--tr>
            <td>Поиск по найденным контактам</td>
            <td>
                <input type="checkbox" <?=($_REQUEST['recursive'] ? 'checked': '')?> name="recursive" id="recursive">
	    </td>
        </tr-->
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
        <requestType>checkinn</requestType>
        <sources>".implode(',',array_keys($sources))."</sources>
        <timeout>".$form_timeout."</timeout>
        <recursive>".($_REQUEST['recursive']?'1':'0')."</recursive>
        <async>".($_REQUEST['async']?'1':'0')."</async>
        <PersonReq>
            <inn>{$_REQUEST['inn']}</inn>
        </PersonReq>
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

curl_setopt($ch, CURLOPT_URL, $serviceurl.'index.php');
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
    $doc = xml_transform($answer, 'isphere_view.xslt');
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

<?php

include('config_new.php');
include('auth.php');
include("xml.php");

$user_access = get_user_access($mysqli);
if (!$user_access['checkorg']) {
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
  'test'=>array('Тест',0,0,1),
  'egrul'=>array('ЕГРЮЛ',1,0,0),
  'fns'=>array('ФНС',1,0,0),
  'gks'=>array('Росстат',1,0,0),
  'bankrot'=>array('Банкроты',1,0,1),
  'cbr'=>array('ЦБ РФ',1,0,0),
  'rosobrnadzor'=>array('Рособрнадзор',1,0,0),
  'minjust'=>array('Иноагенты',1,0,1),
//  'terrorist'=>array('Террористы',1,0,0),
//  'rz'=>array('Реестр залогов',1,0,0),
  'rz'=>array('Реестр залогов старый без pdf',1,0,1),
  'reestrzalogov'=>array('Реестр залогов',1,0,0),
  'rsa_org'=>array('РСА КБМ',0,0,1),
  'fssp'=>array('ФССП',1,0,0),
  'fsspapi'=>array('ФССП (API)',1,0,0),
  'fsspsite'=>array('ФССП (сайт)',1,0,1),
  'vestnik'=>array('Вестник',0,0,0),
  'fedresurs'=>array('Федресурс',1,0,0),
  'kad'=>array('Арбитражный суд',1,0,0),
  'zakupki'=>array('Госзакупки',1,0,1),
  'rkn'=>array('Роскомнадзор',1,0,0),
  'proverki'=>array('Проверки',1,0,1),
  '2gis'=>array('2ГИС',1,1,1),
);

if (!isset($_REQUEST['inn'])) $_REQUEST['inn']='';
if (!isset($_REQUEST['ogrn'])) $_REQUEST['ogrn']='';
if (!isset($_REQUEST['name'])) $_REQUEST['name']='';
if (!isset($_REQUEST['address'])) $_REQUEST['address']='';
if (!isset($_REQUEST['region_id'])) $_REQUEST['region_id']=0;
if (!isset($_REQUEST['bik'])) $_REQUEST['bik']='';
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
<h1>Проверка организации</h1><hr/><a href="admin.php">Назад</a><br/><br/>
<form id="checkform" method="POST">
    <table>
        <tr>
            <td></td>
            <td>
                <button type="button" id="clear">Очистить</button>
	    </td>
        </tr>
        <tr>
            <td>ИНН<span class="req"></span></td>
            <td>
                <input type="text" name="inn" value="<?=$_REQUEST['inn']?>" maxlength="20" />
            </td>
        </tr>
        <tr>
            <td>ОГРН</td>
            <td>
                <input type="text" name="ogrn" value="<?=$_REQUEST['ogrn']?>" maxlength="20" />
            </td>
        </tr>
        <tr>
            <td>Название</td>
            <td>
                <input type="text" name="name" value="<?=htmlentities($_REQUEST['name'])?>" maxlength="500" />
            </td>
        </tr>
        <tr>
            <td>Адрес</td>
            <td>
                <input type="text" name="address" value="<?=htmlentities($_REQUEST['address'])?>" maxlength="500" />
            </td>
        </tr>
        <tr>
            <td>Регион:</td>
            <td>
            <select name="region_id">
                <option value="" selected>Все регионы</option>
                <option value="77" >Москва</option>
                <option value="22" >Алтайский край</option>
                <option value="28" >Амурская область</option>
                <option value="29" >Архангельская область</option>
                <option value="30" >Астраханская область</option>
                <option value="31" >Белгородская область</option>
                <option value="32" >Брянская область</option>
                <option value="33" >Владимирская область</option>
                <option value="34" >Волгоградская область</option>
                <option value="35" >Вологодская область</option>
                <option value="36" >Воронежская область</option>
                <option value="79" >Еврейская АО</option>
                <option value="75" >Забайкальский край</option>
                <option value="37" >Ивановская область</option>
                <option value="38" >Иркутская область</option>
                <option value="07" >Кабардино-Балкария</option>
                <option value="39" >Калининградская область</option>
                <option value="40" >Калужская область</option>
                <option value="41" >Камчатский край</option>
                <option value="09" >Карачаево-Черкессия</option>
                <option value="42" >Кемеровская область</option>
                <option value="43" >Кировская область</option>
                <option value="44" >Костромская область</option>
                <option value="23" >Краснодарский край</option>
                <option value="24" >Красноярский край</option>
                <option value="45" >Курганская область</option>
                <option value="46" >Курская область</option>
                <option value="47" >Ленинградская область</option>
                <option value="48" >Липецкая область</option>
                <option value="49" >Магаданская область</option>
                <option value="50" >Московская область</option>
                <option value="51" >Мурманская область</option>
                <option value="83" >Ненецкий АО</option>
                <option value="52" >Нижегородская область</option>
                <option value="53" >Новгородская область</option>
                <option value="54" >Новосибирская область</option>
                <option value="55" >Омская область</option>
                <option value="56" >Оренбургская область</option>
                <option value="57" >Орловская область</option>
                <option value="58" >Пензенская область</option>
                <option value="59" >Пермский край</option>
                <option value="25" >Приморский край</option>
                <option value="60" >Псковская область</option>
                <option value="01" >Республика Адыгея</option>
                <option value="04" >Республика Алтай</option>
                <option value="02" >Республика Башкортостан</option>
                <option value="03" >Республика Бурятия</option>
                <option value="05" >Республика Дагестан</option>
                <option value="06" >Республика Ингушетия</option>
                <option value="08" >Республика Калмыкия</option>
                <option value="10" >Республика Карелия</option>
                <option value="11" >Республика Коми</option>
                <option value="91" >Республика Крым</option>
                <option value="12" >Республика Марий-Эл</option>
                <option value="13" >Республика Мордовия</option>
                <option value="14" >Республика Саха (Якутия)</option>
                <option value="16" >Республика Татарстан</option>
                <option value="17" >Республика Тыва</option>
                <option value="19" >Республика Хакасия</option>
                <option value="61" >Ростовская область</option>
                <option value="62" >Рязанская область</option>
                <option value="63" >Самарская область</option>
                <option value="78" >Санкт-Петербург</option>
                <option value="64" >Саратовская область</option>
                <option value="65" >Сахалинская область</option>
                <option value="66" >Свердловская область</option>
                <option value="92" >Севастополь</option>
                <option value="15" >Северная Осетия-Алания</option>
                <option value="67" >Смоленская область</option>
                <option value="26" >Ставропольский край</option>
                <option value="68" >Тамбовская область</option>
                <option value="69" >Тверская область</option>
                <option value="70" >Томская область</option>
                <option value="71" >Тульская область</option>
                <option value="72" >Тюменская область</option>
                <option value="18" >Удмуртская Республика</option>
                <option value="73" >Ульяновская область</option>
                <option value="27" >Хабаровский край</option>
                <option value="86" >Ханты-Мансийский АО</option>
                <option value="74" >Челябинская область</option>
                <option value="20" >Чеченская Республика</option>
                <option value="21" >Чувашская Республика</option>
                <option value="87" >Чукотский АО</option>
                <option value="89" >Ямало-Ненецкий АО</option>
                <option value="76" >Ярославская область</option>
            </select>
            </td>
        </tr>
        <tr>
            <td>БИК</td>
            <td>
                <input type="text" name="bik" value="<?=$_REQUEST['bik']?>" maxlength="9" />
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
        <requestType>checkorg</requestType>
        <sources>".implode(',',array_keys($_REQUEST['sources']))."</sources>
        <timeout>".$form_timeout."</timeout>
        <recursive>".($_REQUEST['recursive']?'1':'0')."</recursive>
        <async>".($_REQUEST['async']?'1':'0')."</async>
        <OrgReq>"
. (!$_REQUEST['inn'] ? "" : "
            <inn>{$_REQUEST['inn']}</inn>"
) . (!$_REQUEST['ogrn'] ? "" : "
            <ogrn>{$_REQUEST['ogrn']}</ogrn>"
) . (!$_REQUEST['name'] ? "" : "
            <name>{$_REQUEST['name']}</name>"
) . (!$_REQUEST['address'] ? "" : "
            <address>{$_REQUEST['address']}</address>"
) . (!$_REQUEST['region_id'] ? "" : "
            <region_id>{$_REQUEST['region_id']}</region_id>"
) . (!$_REQUEST['bik'] ? "" : "
            <bik>{$_REQUEST['bik']}</bik>"
) . "
        </OrgReq>
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

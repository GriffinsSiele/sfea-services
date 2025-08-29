<?php

include('config_new.php');
include('auth_new.php');
include("xml.php");


$user_access = get_user_access($mysqli);
if (!$user_access['check']) {
    echo 'У вас нет доступа к этой странице';
    exit;
}

set_time_limit($form_timeout+30);

$user_level = get_user_level($mysqli);
$user_sources = get_user_sources($mysqli);
$user_rules = get_user_rules($mysqli);

echo '<link rel="stylesheet" type="text/css" href="main.css"/>';
$user_message = get_user_message($mysqli);
if ($user_message) {
    echo '<span class="message">'.$user_message.'</span><hr/>';
}

// Источники (название,выбран,рекурсивный,конец строки)
$check_sources = array(
  'test'=>array('Тест',0,0,0),
  'testr'=>array('Тест RabbitMQ',0,0,1),
  'fssp'=>array('ФССП',1,0,0),
  'fsspapi'=>array('ФССП (API)',1,0,0),
  'fsspsite'=>array('ФССП (сайт)',1,0,0),
  'fssp_suspect'=>array('ФССП розыск',1,0,1),
  'fms'=>array('ФМС',1,0,0),
  'fmsdb'=>array('ФМС БД',1,0,0),
  'mvd'=>array('МВД',1,0,0),
  'fsin'=>array('ФСИН',1,0,1),
//  'gosuslugi'=>array('Госуслуги',1,0,0),
  'gosuslugi_passport'=>array('Госуслуги паспорт',1,0,0),
  'gosuslugi_snils'=>array('Госуслуги СНИЛС',1,0,0),
  'gosuslugi_inn'=>array('Госуслуги ИНН',1,0,1),
  'gosuslugi_phone'=>array('Госуслуги телефон',1,0,0),
  'gosuslugi_email'=>array('Госуслуги e-mail',1,0,1),
  'fns'=>array('ФНС',1,0,0),
//  'fns_inn'=>array('ФНС ИНН',1,0,0),
  'pfr'=>array('ПФР',1,0,0),
  'gisgmp'=>array('ГИС ГМП',1,0,0),
  'notariat'=>array('Нотариат',1,0,1),
  'bankrot'=>array('Банкроты',1,0,0),
  'cbr'=>array('ЦБ РФ',1,0,0),
  'minjust'=>array('Иноагенты',1,0,0),
  'terrorist'=>array('Террористы',1,0,1),
//  'rz'=>array('Реестр залогов',1,0,0),
  'reestrzalogov'=>array('Реестр залогов',1,0,0),
  'avtokod'=>array('Автокод',0,0,0),
  'rsa_kbm'=>array('РСА КБМ',0,0,1),
  'gibdd_fines'=>array('ГИБДД штрафы',0,0,0),
  'gibdd_driver'=>array('ГИБДД права',0,0,1),
//  'people'=>array('Соцсети',1,0,0),
//  'beholder'=>array('Beholder',1,1,0),
  'vk'=>array('VK',1,1,0),
  'vk_person'=>array('VK',1,1,0),
  'ok'=>array('OK',1,1,0),
  'ok_person'=>array('OK',1,1,0),
  'fotostrana'=>array('Фотострана',1,1,0),
  'mailru'=>array('Mail.Ru',1,1,1),
  'twitter'=>array('Twitter',1,1,0),
  'facebook'=>array('Facebook',1,1,0),
  'instagram'=>array('Instagram',1,1,1),
  'rossvyaz'=>array('Россвязь',1,1,0),
  'hlr'=>array('HLR',1,1,0),
//  'infobip'=>array('Infobip',1,1,0),
  'smsc'=>array('SMSC',1,1,1),
  'hh'=>array('HH',1,1,0),
//  'commerce'=>array('Commerce',1,1,0),
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
  'lenovo'=>array('Lenovo',1,1,0),
  'samsung'=>array('Samsung',1,1,0),
  'apple'=>array('Apple',1,1,1),
  'whatsapp'=>array('WhatsApp',1,1,0),
  'telegram'=>array('Telegram',1,1,0),
//  'telegramweb'=>array('Telegram',1,1,0),
//  'icq'=>array('ICQ',1,1,0),
  'viber'=>array('Viber',1,1,1),
  'yamap'=>array('Яндекс.Карты',1,1,0),
  '2gis'=>array('2ГИС',1,1,0),
  'egrul'=>array('ЕГРЮЛ',1,1,1),
  'kad'=>array('Арбитражный суд',1,0,0),
  'zakupki'=>array('Госзакупки',1,0,1),
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
//  'banks'=>array('Банки',0,0,0),
//  'sbertest'=>array('Сбербанк тест',0,1,0),
//  'sberbank'=>array('Сбербанк',0,1,1),
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
//  'visa'=>array('VISA',1,1,0),
//  'webmoney'=>array('WebMoney',1,1,0),
//  'qiwi'=>array('Qiwi',1,1,0),
//  'yamoney'=>array('Яндекс.Деньги',1,1,1),
//  'elecsnet'=>array('Элекснет',1,1,1),
  'pochta'=>array('Почта',1,1,0),
  'yoomoney'=>array('ЮMoney',1,1,0),
//  'domclick'=>array('Домклик',1,1,0),
  'sber'=>array('Сбер',1,1,0),
  'rzd'=>array('РЖД',1,1,0),
//  'uralair'=>array('Уральские авиалинии ',1,1,1),
  'aeroflot'=>array('Аэрофлот',1,1,1),
//  'biglion'=>array('Биглион',1,1,0),
  'rosneft'=>array('Роснефть',1,1,0),
  'papajohns'=>array('Папа Джонс',1,1,0),
  'litres'=>array('Литрес',1,1,0),
  'duolingo'=>array('Duolingo',1,1,0),
  'avito'=>array('Авито',1,1,1),
  'domru'=>array('Дом.ру',1,1,0),
  'krasnoebeloe'=>array('Красное Белое',1,1,0),
  'winelab'=>array('Винлаб',1,1,0),
  'petrovich'=>array('Петрович',1,1,1),
);

// Правила (название,выбран,конец строки)
$check_rules = array(
  'fms_passport_decline_not_valid'=>array('Отказ при недействительном паспорте',1,1),
  'fns_inn_approve_found'=>array('Одобрение при найденном ИНН',1,1),
  'vk_person_approve_found'=>array('Одобрение при найденном профиле VK',1,1),
  'ok_person_approve_found'=>array('Одобрение при найденном профиле OK',1,1),
  'fssp_person_approve_found'=>array('Одобрение при найденном ИП в ФССП',1,1),
  'decline_other'=>array('Отказ в остальных случаях',1,1),
);

if (!isset($_REQUEST['last_name'])) $_REQUEST['last_name']='';
if (!isset($_REQUEST['first_name'])) $_REQUEST['first_name']='';
if (!isset($_REQUEST['patronymic'])) $_REQUEST['patronymic']='';
if (!isset($_REQUEST['date'])) $_REQUEST['date']='';
if (!isset($_REQUEST['passport_series'])) $_REQUEST['passport_series']='';
if (!isset($_REQUEST['passport_number'])) $_REQUEST['passport_number']='';
if (!isset($_REQUEST['issueDate'])) $_REQUEST['issueDate']='';
if (!isset($_REQUEST['inn'])) $_REQUEST['inn']='';
if (!isset($_REQUEST['snils'])) $_REQUEST['snils']='';
if (!isset($_REQUEST['driver_number'])) $_REQUEST['driver_number']='';
if (!isset($_REQUEST['driver_date'])) $_REQUEST['driver_date']='';
if (!isset($_REQUEST['mobile_phone'])) $_REQUEST['mobile_phone']='';
if (!isset($_REQUEST['home_phone'])) $_REQUEST['home_phone']='';
if (!isset($_REQUEST['work_phone'])) $_REQUEST['work_phone']='';
if (!isset($_REQUEST['additional_phone'])) $_REQUEST['additional_phone']='';
if (!isset($_REQUEST['email'])) $_REQUEST['email']='';
if (!isset($_REQUEST['additional_email'])) $_REQUEST['additional_email']='';
if (!isset($_REQUEST['skype'])) $_REQUEST['skype']='';
if (!isset($_REQUEST['region_id'])) $_REQUEST['region_id']=0;
if (!isset($_REQUEST['reqdate'])) $_REQUEST['reqdate']='';
if (!isset($_REQUEST['sources'])) $_REQUEST['sources']=array();
if (!isset($_REQUEST['rules'])) $_REQUEST['rules']=array();
if (!isset($_REQUEST['recursive'])) $_REQUEST['recursive']=0;
if (!isset($_REQUEST['async'])) $_REQUEST['async']=($_SERVER['REQUEST_METHOD']=='POST'?0:1);
if (!isset($_REQUEST['request_id'])) $_REQUEST['request_id']=time();

foreach ($_REQUEST as $rn => $rv) $_REQUEST[$rn] = preg_replace("/[<>\/]/","",$rv);

foreach ($check_sources as $k => $s) {
    if (/*($user_level<0) || */(isset($user_sources[$k]) && $user_sources[$k])) {
        if (!isset($_REQUEST['mode']) && !isset($_REQUEST['sources'][$k])) $_REQUEST['sources'][$k] = 0; //$s[1];
//        if ($_REQUEST['recursive'] && $s[2]) $_REQUEST['sources'][$k] = 1;
    }
}

foreach ($check_rules as $k => $r) {
    if (/*($user_level<0) || */(isset($user_rules[$k]) && $user_rules[$k])) {
        if (!isset($_REQUEST['mode']) && !isset($_REQUEST['rules'][$k])) $_REQUEST['rules'][$k] = $r[1];
    }
}

?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="main_new.js"></script>
<h1>Проверка физ.лица</h1><hr/><a href="admin.php">Назад</a><br/><br/>
<form id="checkform" method="POST">
    <table>
        <tr>
            <td></td>
            <td>
                <button type="button" id="clear">Очистить</button>
	    </td>
        </tr>
        <tr>
            <td>Фамилия</td>
            <td>
                <input type="text" name="last_name" value="<?=$_REQUEST['last_name']?>" maxlength="500" />
            </td>
        </tr>
        <tr>
            <td>Имя</td>
            <td>
                <input type="text" name="first_name" value="<?=$_REQUEST['first_name']?>" maxlength="500" />
            </td>
        </tr>
        <tr>
            <td>Отчество</td>
            <td>
                <input type="text" name="patronymic" value="<?=$_REQUEST['patronymic']?>" maxlength="500" />
            </td>
        </tr>
        <tr>
            <td>Дата рождения</td>
            <td>
                <input type="date" id="date" name="date" value="<?=$_REQUEST['date']?>" data-type="date" maxlength="50" />
            </td>
        </tr>

        <tr>
            <td>Серия паспорта</td>
            <td>
                <input type="text" name="passport_series" value="<?=$_REQUEST['passport_series']?>" maxlength="5" />
            </td>
        </tr>
        <tr>
            <td>Номер паспорта</td>
            <td>
                <input type="text" name="passport_number" value="<?=$_REQUEST['passport_number']?>" maxlength="6" />
            </td>
        </tr>
        <tr>
	     <td>Дата выдачи паспорта</td>
	     <td>
	         <input type="date" name="issueDate" value="<?=$_REQUEST['issueDate']?>" data-type="date" maxlength="10" />
	     </td>
        </tr>
        <tr>
            <td>ИНН</td>
            <td>
                <input type="text" name="inn" value="<?=$_REQUEST['inn']?>" maxlength="12" />
            </td>
        </tr>
        <tr>
            <td>СНИЛС</td>
            <td>
                <input type="text" name="snils" value="<?=$_REQUEST['snils']?>" maxlength="14" />
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
            <td>Мобильный телефон<span class="req"></span></td>
            <td>
                <input type="text" name="mobile_phone" value="<?=$_REQUEST['mobile_phone']?>" maxlength="50" />
            </td>
        </tr>
        <tr>
            <td>Домашний телефон<span class="req"></span></td>
            <td>
                <input type="text" name="home_phone" value="<?=$_REQUEST['home_phone']?>" maxlength="50" />
            </td>
        </tr>
        <tr>
            <td>Рабочий телефон<span class="req"></span></td>
            <td>
                <input type="text" name="work_phone" value="<?=$_REQUEST['work_phone']?>" maxlength="50" />
            </td>
        </tr>
        <tr>
            <td>Дополнительный телефон<span class="req"></span></td>
            <td>
                <input type="text" name="additional_phone" value="<?=$_REQUEST['additional_phone']?>" maxlength="50" />
            </td>
        </tr>
        <tr>
            <td>Email<span class="req"></span></td>
            <td>
                <input type="text" name="email" value="<?=$_REQUEST['email']?>" maxlength="100" />
            </td>
        </tr>
        <tr>
            <td>Дополнительный Email<span class="req"></span></td>
            <td>
                <input type="text" name="additional_email" value="<?=$_REQUEST['additional_email']?>" maxlength="100" />
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
                <option value="92" >Республика Крым</option>
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
            <td>Дата запроса в РСА</td>
            <td>
                <input type="date" id="reqdate" name="reqdate" value="<?=$_REQUEST['reqdate']?>" data-type="date" maxlength="50" />
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
<?php
if (sizeof($user_rules)) {
        echo '
        <tr>
            <td>Правила</td>
            <td>';
}
$line = false;
foreach ($check_rules as $k => $r) {
    if (/*($user_level<0) || */(isset($user_rules[$k]) && $user_rules[$k])) {
        echo '
               <input type="checkbox" class="rule" '.(isset($_REQUEST['rules'][$k]) && $_REQUEST['rules'][$k] ? 'checked': '').' name="rules['.$k.']"> '.$r[0];
        $line = true;
    }
    if ($line && $r[2]) {
        echo '
               <br/>';
        $line = false;
    }
}
if (sizeof($user_rules)) {
         echo '
               <button type="button" id="selectallrules">Выбрать все</button>
               <button type="button" id="clearallrules">Снять все</button>
	    </td>
        </tr>';
}
?>
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
                <option value="json">JSON</option>
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
        <requestType>check</requestType>
        <sources>".implode(', ',array_keys($_REQUEST['sources']))."</sources>"
. (!isset($_REQUEST['rules']) || !sizeof($_REQUEST['rules'])? "" : "
        <rules>".implode(', ',array_keys($_REQUEST['rules']))."</rules>"
) . "
        <timeout>".$form_timeout."</timeout>
        <recursive>".($_REQUEST['recursive']?'1':'0')."</recursive>
        <async>".($_REQUEST['async']?'1':'0')."</async>"
. (!$_REQUEST['last_name'] && !$_REQUEST['passport_number'] && !$_REQUEST['inn'] && !$_REQUEST['snils'] && !$_REQUEST['driver_number'] ? "" : "
        <PersonReq>"
. (!$_REQUEST['last_name'] ? "" : "
            <first>{$_REQUEST['first_name']}</first>
            <middle>{$_REQUEST['patronymic']}</middle>
            <paternal>{$_REQUEST['last_name']}</paternal>"
) . (!$_REQUEST['date'] ? "" : "
            <birthDt>{$_REQUEST['date']}</birthDt>"
) . (!$_REQUEST['passport_number'] ? "" : "
            <passport_series>{$_REQUEST['passport_series']}</passport_series>
            <passport_number>{$_REQUEST['passport_number']}</passport_number>"
) . (!$_REQUEST['issueDate'] ? "" : "
            <issueDate>{$_REQUEST['issueDate']}</issueDate>"
) . (!$_REQUEST['inn'] ? "" : "
            <inn>{$_REQUEST['inn']}</inn>"
) . (!$_REQUEST['snils'] ? "" : "
            <snils>{$_REQUEST['snils']}</snils>"
) . (!$_REQUEST['driver_number'] ? "" : "
            <driver_number>{$_REQUEST['driver_number']}</driver_number>"
) . (!$_REQUEST['driver_date'] ? "" : "
            <driver_date>{$_REQUEST['driver_date']}</driver_date>"
) . (!$_REQUEST['region_id'] ? "" : "
            <region_id>{$_REQUEST['region_id']}</region_id>"
) . (!$_REQUEST['reqdate'] ? "" : "
            <reqdate>{$_REQUEST['reqdate']}</reqdate>"
) . "
        </PersonReq>"
) . (!$_REQUEST['mobile_phone'] ? "" : "
        <PhoneReq>
            <phone>{$_REQUEST['mobile_phone']}</phone>
        </PhoneReq>"
) . (!$_REQUEST['home_phone'] ? "" : "
        <PhoneReq>
            <phone>{$_REQUEST['home_phone']}</phone>
        </PhoneReq>"
) . (!$_REQUEST['work_phone'] ? "" : "
        <PhoneReq>
            <phone>{$_REQUEST['work_phone']}</phone>
        </PhoneReq>"
) . (!$_REQUEST['additional_phone'] ? "" : "
        <PhoneReq>
            <phone>{$_REQUEST['additional_phone']}</phone>
        </PhoneReq>"
) . (!$_REQUEST['email'] ? "" : "
        <EmailReq>
            <email>{$_REQUEST['email']}</email>
        </EmailReq>"
) . (!$_REQUEST['additional_email'] ? "" : "
        <EmailReq>
            <email>{$_REQUEST['additional_email']}</email>
        </EmailReq>"
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
}elseif ($_REQUEST['mode']=='json') {
    $xml = simplexml_load_string($answer);
    $xml['result'] = strtr($xml['result'],array('mode=xml'=>'mode=json'));
    $json = json_encode($xml, true);
    print 'Ответ JSON: <textarea style="width:100%;height:70%">';
    print $json;
    print '</textarea>';
} else {
    $answer = substr($answer,strpos($answer,'<?xml'));
    $doc = xml_transform($answer, 'isphere_view_new.xslt');
    if ($doc) {
        $servicename = isset($servicenames[$_SERVER['HTTP_HOST']])?'платформой '.$servicenames[$_SERVER['HTTP_HOST']]:'';
        $login = $_SERVER['PHP_AUTH_USER'];
        $html = strtr($doc->saveHTML(),array('___servicename___'=>$servicename,'___login___'=>$login));
        echo $html;
    } else {
        echo $answer?'Некорректный ответ сервиса':'Нет ответа от сервиса';
    }
}
print '</div>';

if (preg_match("/<Response id=\"([\d]+)\" status=\"([\d]+)\" datetime=\"[^\"]+\" result=\"([^\"]+)\" view=\"([^\"]+)\"/",$answer,$matches)) {
    $id = $matches[1];
    $status = $matches[2];
    $url = ($_REQUEST['mode']=='xml'||$_REQUEST['mode']=='json')?$matches[3]:$matches[4];
} else {
    $id = 0;
    $status = 1;
    $url = '';
}

print '<input type="hidden" id="id" value="'.$id.'"/>';
print '<input type="hidden" id="status" value="'.$status.'"/>';
print '<input type="hidden" id="url" value="'.$url.'"/>';

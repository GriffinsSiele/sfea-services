<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Gate;

class CheckController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $timeout = env('CHECK_TIMEOUT');

        $xmlRequest = null;
        $response = null;

        $sources = $this->getUserSources();

        if($request->isMethod('post')) {
            $xmlRequest = $this->compileRequest('user','password', $sources, $timeout);
            $response = $this->sendRequest($xmlRequest, $timeout + 10);
        }


        return view('private.common.check')
            ->with('request', $request)
            ->with('xmlRequest', $xmlRequest)
            ->with('response', $response)
            ->with('sources', $sources)
            ->with('regions', $this->regions);
    }

    protected function getUserSources()
    {
        $sources = [];
        $user = Auth::user();

        $access = $user->access;

        if($access) {

            if($access->accessSources)
                foreach ($access->accessSources as $source)
                    if($source->allowed && isset($this->sources[$source->source_name]))
                        $sources[] = $this->sources[$source->source_name];
        }

        return $sources;
    }

    protected function compileRequest($userId, $password, $userSources, $timeout)
    {
        if(!isset($_REQUEST['recursive']))
            $_REQUEST['recursive'] = 0;

        $sources = array_intersect($_REQUEST['sources'], array_keys($userSources));

        $xml ="
<Request>
        <UserIP>{$_SERVER['REMOTE_ADDR']}</UserIP>
        <UserID>{$userId}</UserID>
        <Password>{$password}</Password>"
            . (!isset($_REQUEST['request_id']) || !$_REQUEST['request_id']? "" : "
        <requestId>{$_REQUEST['request_id']}</requestId>"
            ) . "
        <requestType>check</requestType>
        <sources>".implode(', ',$sources)."</sources>"
            . (!isset($_REQUEST['rules']) || !sizeof($_REQUEST['rules'])? "" : "
        <rules>".implode(', ',array_keys($_REQUEST['rules']))."</rules>"
            ) . "
        <timeout>".$timeout."</timeout>
        <recursive>".($_REQUEST['recursive']?'1':'0')."</recursive>
        <async>".(isset($_REQUEST['async'])?'1':'0')."</async>"
            . (!$_REQUEST['last_name'] && !$_REQUEST['passport_number'] && !$_REQUEST['inn'] && !$_REQUEST['driver_number'] ? "" : "
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
                ) . (!$_REQUEST['driver_number'] ? "" : "
            <driver_number>{$_REQUEST['driver_number']}</driver_number>"
                ) . (!$_REQUEST['driver_date'] ? "" : "
            <driver_date>{$_REQUEST['driver_date']}</driver_date>"
                ) . (empty($_REQUEST['region_id']) ? "" : "
            <region_id>{$_REQUEST['region_id']}</region_id>"
                ) . (!isset($_REQUEST['reqdate']) ? "" : "

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

        return $xml;
    }

    protected function sendRequest($request, $timeout)
    {
        $serviceurl = env('CHECK_URL');

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $serviceurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_POST, 1);

        $answer = curl_exec($ch);
        curl_close($ch);

        return $answer;
    }

    // Источники (название,выбран,рекурсивный,конец строки)
    protected $sources = [
        'fssp'=>['ФССП',1,0,0],
//  'fsspapi'=>['ФССП (API)',1,0,0],
//  'fsspsite'=>['ФССП (сайт)',1,0,1],
        'fms'=>['ФМС',1,0,0],
        'fmsdb'=>['ФМС БД',1,0,0],
        'mvd'=>['МВД',1,0,1],
//  'gosuslugi'=>['Госуслуги',1,0,0],
        'gosuslugi_passport'=>['Госуслуги паспорт',1,0,0],
        'gosuslugi_inn'=>['Госуслуги ИНН',1,0,1],
        'gosuslugi_phone'=>['Госуслуги телефон',1,0,0],
        'gosuslugi_email'=>['Госуслуги e-mail',1,0,1],
        'fns'=>['ФНС',1,0,0],
//  'fns_inn'=>['ФНС ИНН',1,0,0],
        'gisgmp'=>['ГИС ГМП',1,0,0],
        'notariat'=>['Нотариат',1,0,1],
        'bankrot'=>['Банкроты',1,0,0],
        'cbr'=>['ЦБ РФ',1,0,0],
        'terrorist'=>['Террористы',1,0,1],
//  'rz'=>['Реестр залогов',1,0,0],
        'reestrzalogov'=>['Реестр залогов',1,0,0],
//  'avtokod'=>['Автокод',0,0,0],
        'rsa_kbm'=>['РСА КБМ',0,0,1],
        'gibdd_fines'=>['ГИБДД штрафы',0,0,0],
        'gibdd_driver'=>['ГИБДД права',0,0,1],
//  'people'=>['Соцсети',1,0,0],
//  'beholder'=>['Beholder',1,1,0],
        'vk'=>['VK',1,1,0],
        'vk_person'=>['VK',1,1,0],
        'ok'=>['OK',1,1,0],
        'ok_person'=>['OK',1,1,0],
        'mailru'=>['Mail.Ru',1,1,1],
        'twitter'=>['Twitter',1,1,0],
        'facebook'=>['Facebook',1,1,0],
        'instagram'=>['Instagram',1,1,1],
        'rossvyaz'=>['Россвязь',1,1,0],
        'hlr'=>['HLR',1,1,0],
//  'infobip'=>['Infobip',1,1,0],
        'smsc'=>['SMSC',1,1,1],
        'hh'=>['HH',1,1,0],
//  'commerce'=>['Commerce',1,1,0],
        'announcement'=>['Объявления',1,1,0],
        'boards'=>['Boards',1,1,1],
        'skype'=>['Skype',1,1,0],
        'google'=>['Google',1,1,0],
        'google_name'=>['Google имя',1,1,0],
        'googleplus'=>['Google+',1,1,0],
        'apple'=>['Apple',1,1,1],
        'whatsapp'=>['WhatsApp',1,1,0],
        'telegram'=>['Telegram',1,1,0],
//  'telegramweb'=>['Telegram',1,1,0],
        'viber'=>['Viber',1,1,1],
//  'yamap'=>['Яндекс.Карты',1,1,0],
        '2gis'=>['ДубльГИС',1,1,0],
        'egrul'=>['ЕГРЮЛ',1,1,1],
//  'kad'=>['Арбитражный суд',1,0,0],
        'zakupki'=>['Госзакупки',1,0,1],
        'getcontact'=>['GetContact',1,1,0],
        'truecaller'=>['TrueCaller',1,1,0],
        'emt'=>['EmobileTracker',1,1,1],
        'numbuster'=>['NumBuster',1,1,0],
//  'numbusterapp'=>['NumBuster',1,2,0],
        'names'=>['Имена',1,1,0],
        'phones'=>['Телефоны',1,1,1],
//  'avinfo'=>['AvInfo',1,1,0)],
//  'phonenumber'=>['PhoneNumber',1,1,0],
//  'banks'=>['Банки',0,0,0],
        'tinkoff'=>['Тинькофф',0,1,0],
        'alfabank'=>['Альфа-Банк',0,1,0],
//  'vtb'=>['ВТБ',0,1,0],
//  'openbank'=>['Открытие',0,1,1],
//  'psbank'=>['Промсвязьбанк',0,1,0],
//  'rosbank'=>['Росбанк',0,1,0],
//  'unicredit'=>['Юникредит',0,1,0],
//  'raiffeisen'=>['Райффайзен',0,1,1],
//  'sovcombank'=>['Совкомбанк',0,1,0],
//  'gazprombank'=>['Газпромбанк',0,1,0],
//  'mkb'=>['МКБ',0,1,0],
//  'rsb'=>['Русский стандарт',0,1,1],
//  'avangard'=>['Авангард',0,1,0],
//  'qiwibank'=>['КИВИ Банк',0,1,0],
//  'rnko'=>['РНКО Платежный центр',0,1,1],
//  'visa'=>['VISA',1,1,0],
//  'webmoney'=>['WebMoney',1,1,0],
//  'sber'=>['Сбер Онлайн',0,0,0],
//  'sbertest'=>['Сбербанк тест',0,1,0],
        'sberbank'=>['Сбербанк',0,1,1],
//  'qiwi'=>['Qiwi',1,1,0],
        'yamoney'=>['Яндекс.Деньги',1,1,1],
//  'elecsnet'=>['Элекснет',1,1,1],
        'rzd'=>['РЖД',1,1,0],
        'aeroflot'=>['Аэрофлот',1,1,1],
//  'uralair'=>['Уральские авиалинии ',1,1,1],
        'avito'=>['Авито',1,1,0],
//  'biglion'=>['Биглион',1,1,0],
        'papajohns'=>['Папа Джонс',1,1,1],
    ];

    protected $regions = [
        ""=>"Все регионы",
        "77"=>"Москва",
        "22"=>"Алтайский край",
        "28"=>"Амурская область",
        "29"=>"Архангельская область",
        "30"=>"Астраханская область",
        "31"=>"Белгородская область",
        "32"=>"Брянская область",
        "33"=>"Владимирская область",
        "34"=>"Волгоградская область",
        "35"=>"Вологодская область",
        "36"=>"Воронежская область",
        "79"=>"Еврейская АО",
        "75"=>"Забайкальский край",
        "37"=>"Ивановская область",
        "38"=>"Иркутская область",
        "07"=>"Кабардино-Балкария",
        "39"=>"Калининградская область",
        "40"=>"Калужская область",
        "41"=>"Камчатский край",
        "09"=>"Карачаево-Черкессия",
        "42"=>"Кемеровская область",
        "43"=>"Кировская область",
        "44"=>"Костромская область",
        "23"=>"Краснодарский край",
        "24"=>"Красноярский край",
        "45"=>"Курганская область",
        "46"=>"Курская область",
        "47"=>"Ленинградская область",
        "48"=>"Липецкая область",
        "49"=>"Магаданская область",
        "50"=>"Московская область",
        "51"=>"Мурманская область",
        "83"=>"Ненецкий АО",
        "52"=>"Нижегородская область",
        "53"=>"Новгородская область",
        "54"=>"Новосибирская область",
        "55"=>"Омская область",
        "56"=>"Оренбургская область",
        "57"=>"Орловская область",
        "58"=>"Пензенская область",
        "59"=>"Пермский край",
        "25"=>"Приморский край",
        "60"=>"Псковская область",
        "01"=>"Республика Адыгея",
        "04"=>"Республика Алтай",
        "02"=>"Республика Башкортостан",
        "03"=>"Республика Бурятия",
        "05"=>"Республика Дагестан",
        "06"=>"Республика Ингушетия",
        "08"=>"Республика Калмыкия",
        "10"=>"Республика Карелия",
        "11"=>"Республика Коми",
        "91"=>"Республика Крым",
        "12"=>"Республика Марий-Эл",
        "13"=>"Республика Мордовия",
        "14"=>"Республика Саха (Якутия)",
        "16"=>"Республика Татарстан",
        "17"=>"Республика Тыва",
        "19"=>"Республика Хакасия",
        "61"=>"Ростовская область",
        "62"=>"Рязанская область",
        "63"=>"Самарская область",
        "78"=>"Санкт-Петербург",
        "64"=>"Саратовская область",
        "65"=>"Сахалинская область",
        "66"=>"Свердловская область",
        "92"=>"Севастополь",
        "15"=>"Северная Осетия-Алания",
        "67"=>"Смоленская область",
        "26"=>"Ставропольский край",
        "68"=>"Тамбовская область",
        "69"=>"Тверская область",
        "70"=>"Томская область",
        "71"=>"Тульская область",
        "72"=>"Тюменская область",
        "18"=>"Удмуртская Республика",
        "73"=>"Ульяновская область",
        "27"=>"Хабаровский край",
        "86"=>"Ханты-Мансийский АО",
        "74"=>"Челябинская область",
        "20"=>"Чеченская Республика",
        "21"=>"Чувашская Республика",
        "87"=>"Чукотский АО",
        "89"=>"Ямало-Ненецкий АО",
        "76"=>"Ярославская область",
    ];
}

<?php

class InfobipPlugin implements PluginInterface
{
    private $login = 'sphere';
    private $password = 'ULFGnj5!';

    public function __construct($login = '', $password = '')
    {
    }

    public function getName()
    {
        return 'HLR-2';
    }

    public function getTitle()
    {
        return 'Проверка доступности абонента мобильной связи';
    }

    private $hlrErrors = array(
        1 => 'Не существует',
        6 => 'Недоступен',
        11 => 'Услуга не поддерживается',
        13 => 'Заблокирован',
        248 => 'Не существует',
        249 => 'Не существует',
        250 => 'Услуга не поддерживается',
        252 => 'Услуга не поддерживается',
        253 => 'Услуга не поддерживается',
        255 => 'Не существует',
    );

    private $regions = array(
        '01' => 'Республика Адыгея (Адыгея)',
        '02' => 'Республика Башкортостан',
        '03' => 'Республика Бурятия',
        '04' => 'Республика Алтай',
        '05' => 'Республика Дагестан',
        '06' => 'Республика Ингушетия',
        '07' => 'Кабардино-Балкарская Республика',
        '08' => 'Республика Калмыкия',
        '09' => 'Карачаево-Черкесская Республика',
        '10' => 'Республика Карелия',
        '11' => 'Республика Коми',
        '12' => 'Республика Марий Эл',
        '13' => 'Республика Мордовия',
        '14' => 'Республика Саха (Якутия)',
        '15' => 'Республика Северная Осетия - Алания',
        '16' => 'Республика Татарстан',
        '17' => 'Республика Тыва',
        '18' => 'Удмуртская Республика',
        '19' => 'Республика Хакасия',
        '20' => 'Чеченская Республика',
        '21' => 'Чувашская Республика',
        '22' => 'Алтайский край',
        '23' => 'Краснодарский край',
        '24' => 'Красноярский край',
        '25' => 'Приморский край',
        '26' => 'Ставропольский край',
        '27' => 'Хабаровский край',
        '28' => 'Амурская область',
        '29' => 'Архангельская область',
        '30' => 'Астраханская область',
        '31' => 'Белгородская область',
        '32' => 'Брянская область',
        '33' => 'Владимирская область',
        '34' => 'Волгоградская область',
        '35' => 'Вологодская область',
        '36' => 'Воронежская область',
        '37' => 'Ивановская область',
        '38' => 'Иркутская область',
        '39' => 'Калининградская область',
        '40' => 'Калужская область',
        '41' => 'Камчатская область',
        '42' => 'Кемеровская область',
        '43' => 'Кировская область',
        '44' => 'Костромская область',
        '45' => 'Курганская область',
        '46' => 'Курская область',
        '47' => 'Ленинградская область',
        '48' => 'Липецкая область',
        '49' => 'Магаданская область',
        '50' => 'Московская область',
        '51' => 'Мурманская область',
        '52' => 'Нижегородская область',
        '53' => 'Новгородская область',
        '54' => 'Новосибирская область',
        '55' => 'Омская область',
        '56' => 'Оренбургская область',
        '57' => 'Орловская область',
        '58' => 'Пензенская область',
        '59' => 'Пермский край',
        '60' => 'Псковская область',
        '61' => 'Ростовская область',
        '62' => 'Рязанская область',
        '63' => 'Самарская область',
        '64' => 'Саратовская область',
        '65' => 'Сахалинская область',
        '66' => 'Свердловская область',
        '67' => 'Смоленская область',
        '68' => 'Тамбовская область',
        '69' => 'Тверская область',
        '70' => 'Томская область',
        '71' => 'Тульская область',
        '72' => 'Тюменская область',
        '73' => 'Ульяновская область',
        '74' => 'Челябинская область',
        '75' => 'Читинская область',
        '76' => 'Ярославская область',
        '77' => 'Москва',
        '78' => 'Санкт - Петербург',
        '79' => 'Еврейская автономная область',
        '80' => 'Агинский Бурятский автономный округ',
        '81' => 'Коми-Пермяцкий автономный округ',
        '82' => 'Корякский автономный округ',
        '83' => 'Ненецкий автономный округ',
        '84' => 'Таймырский (Долгано-Ненецкий) автономный округ',
        '85' => 'Усть-Ордынский Бурятский автономный округ',
        '86' => 'Ханты-Мансийский автономный округ (Югра)',
        '87' => 'Чукотский автономный округ',
        '88' => 'Эвенкийский автономный округ',
        '89' => 'Ямало-Ненецкий автономный округ',
        '99' => 'Иные территории, Байконур',
    );

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if(!isset($initData['phone']))
        {
            $rContext->setFinished();
            $rContext->setError('Не задан номер телефона');

            return false;
        }

        if (strlen($initData['phone'])==10)
            $initData['phone']='7'.$initData['phone'];
        if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
            $initData['phone']='7'.substr($initData['phone'],1);

        if(substr($initData['phone'],0,2)!='79')
        {
            $rContext->setFinished();
            $rContext->setError('Поиск производится только по мобильным телефонам в коде 9xx');

            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $url = 'https://api.infobip.com/number/1/query';
        $params = array('to' => $initData['phone']);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); 
        curl_setopt($ch, CURLOPT_USERPWD, $this->login . ":" . $this->password);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);      

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $error = ($swapData['iteration']>5) && curl_error($rContext->getCurlHandler());
        if (!$error) {
            $content = curl_multi_getcontent($rContext->getCurlHandler());
            $ares = json_decode($content, true);

            $data['json_result'] = new ResultDataField('string','JSON', $content, 'Ответ JSON', 'Ответ JSON');
            if ($ares && array_key_exists('results',$ares)) {
                $res = $ares['results'][0];
                $data['phone'] = new ResultDataField('string','PhoneNumber', $res['to'], 'Номер', 'Номер телефона');
                if (array_key_exists('mccMnc',$res))
                    $data['mcc'] = new ResultDataField('string','MCC', $res['mccMnc'], 'MCC', 'MCC');
                if (array_key_exists('imsi',$res))  
                    $data['imsi'] = new ResultDataField('string','IMSI', $res['imsi'], 'IMSI', 'IMSI');
                if (array_key_exists('originalNetwork',$res)) {
                    $data['operator'] = new ResultDataField('string','Operator', $res['originalNetwork']['networkName'], 'Оператор', 'Наименование оператора связи');
                    $data['prefix'] = new ResultDataField('string','Prefix', $res['originalNetwork']['networkPrefix'], 'Прекфикс', 'Префикс оператора связи');
                }
                if (array_key_exists('ported',$res))
                    $data['ported'] = new ResultDataField('string','Ported', $res['ported']?'Да':'Нет', 'Ported', 'Ported');
                if (array_key_exists('roaming',$res))
                    $data['roaming'] = new ResultDataField('string','Roaming', $res['roaming']?'Да':'Нет', 'Roaming', 'Roaming');
                if (array_key_exists('status',$res)) {
                    $data['hlr_status'] = new ResultDataField('string','HLRStatus', $res['status']['name'], 'Код статуса', 'Код статуса абонента');
                    $data['hlr_statustext'] = new ResultDataField('string','HLRStatusText', $res['status']['description'], 'Статус', 'Статус абонента');
                }
                if (array_key_exists('error', $res)) {
                    $error = $res['error']['description'];
                }
                $rContext->setResultData($data);
                $rContext->setFinished();
                return true;
            }
            else
            {
                $error = 'Некорректный ответ сервиса';
            }
        }

        $rContext->setSwapData($swapData);

        if(isset($swapData['iteration']) && $swapData['iteration']>3)
        {
            $rContext->setFinished();
            $rContext->setError($error==''?'Превышено количество попыток получения ответа':$error);

            return false;
        }

        return true;
    }
}

?>
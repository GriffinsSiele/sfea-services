<?php

class MVDPlugin implements PluginInterface
{
    private $passport_region_series = array(
        '00' => '00',
        '79' => '01',
        '84' => '02',
        '80' => '03',
        '81' => '04',
        '82' => '05',
        '26' => '06',
        '83' => '07',
        '85' => '08',
        '91' => '09',
        '86' => '10',
        '87' => '11',
        '88' => '12',
        '89' => '13',
        '98' => '14',
        '90' => '15',
        '92' => '16',
        '93' => '17',
        '94' => '18',
        '95' => '19',
        '96' => '20',
        '97' => '21',
        '01' => '22',
        '03' => '23',
        '04' => '24',
        '05' => '25',
        '07' => '26',
        '08' => '27',
        '10' => '28',
        '11' => '29',
        '12' => '30',
        '14' => '31',
        '15' => '32',
        '17' => '33',
        '18' => '34',
        '19' => '35',
        '20' => '36',
        '24' => '37',
        '25' => '38',
        '27' => '39',
        '29' => '40',
        '30' => '41',
        '32' => '42',
        '33' => '43',
        '34' => '44',
        '37' => '45',
        '38' => '46',
        '41' => '47',
        '42' => '48',
        '44' => '49',
        '46' => '50',
        '47' => '51',
        '22' => '52',
        '49' => '53',
        '50' => '54',
        '52' => '55',
        '53' => '56',
        '54' => '57',
        '56' => '58',
        '57' => '59',
        '58' => '60',
        '60' => '61',
        '61' => '62',
        '36' => '63',
        '63' => '64',
        '64' => '65',
        '65' => '66',
        '66' => '67',
        '68' => '68',
        '28' => '69',
        '69' => '70',
        '70' => '71',
        '71' => '72',
        '73' => '73',
        '75' => '74',
        '76' => '75',
        '78' => '76',
        '45' => '77',
        '40' => '78',
        '99' => '79',
        '43' => '80',
        '48' => '81',
        '51' => '41',
        '55' => '83',
        '59' => '84',
        '62' => '85',
        '67' => '86',
        '77' => '87',
        '72' => '88',
        '74' => '89',
        '39' => '82',
        '09' => '92',
    );

    private $regions = array(
        '00' => 'Не определен',
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
        '41' => 'Камчатский край',
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
        '82' => 'Республика Крым',
        '83' => 'Ненецкий автономный округ',
        '84' => 'Таймырский (Долгано-Ненецкий) автономный округ',
        '85' => 'Усть-Ордынский Бурятский автономный округ',
        '86' => 'Ханты-Мансийский автономный округ (Югра)',
        '87' => 'Чукотский автономный округ',
        '88' => 'Эвенкийский автономный округ',
        '89' => 'Ямало-Ненецкий автономный округ',
        '92' => 'Севастополь',
        '99' => 'Иные территории, Байконур',
    );

    public function getName()
    {
        return 'MVD';
    }

    public function getTitle()
    {
        return 'Проверка адреса регистрации в ГУ МВД РФ';
    }

    public function getRegionCode($code)
    {
        return array_key_exists($code,$this->passport_region_series) ? $this->passport_region_series[$code] : '';
    }

    public function getRegionName($code)
    {
        return array_key_exists($code,$this->passport_region_series) ? $this->regions[$this->passport_region_series[$code]] : '';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token FROM isphere.session WHERE sessionstatusid=2 AND sourceid=10 ORDER BY lasttime limit 1");

        if($result)
        {
            $row = $result->fetch_object();

            if ($row)
            {
                $sessionData = new \StdClass;

                $sessionData->id = $row->id;
                $sessionData->code = $row->captcha;
                $sessionData->token = $row->token;
                $sessionData->starttime = $row->starttime;
                $sessionData->lasttime = $row->lasttime;
                $sessionData->cookies = $row->cookies;

                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1 WHERE id=".$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if(!isset($initData['passport_series']) || !isset($initData['passport_number'])) {
            $rContext->setFinished();
            $rContext->setError('Не указаны серия и номер паспорта');

            return false;
        }

        if (!preg_match("/^\d{4}$/", $initData['passport_series']) || !preg_match("/^\d{6}$/", $initData['passport_number'])){
            $rContext->setFinished();
            $rContext->setError('Некорректные значения серии или номера паспорта');

            return false;
        }

        $swapData['session'] = $this->getSessionData();

        $rContext->setSwapData($swapData);

        if(!$swapData['session'])
        {
//            $rContext->setFinished();
//            $rContext->setError('Нет актуальных сессий');
            $rContext->setSleep(3);
            return false;
        }

        $params = array(
            'sid'=>'2160',
            'form_name' => 'form',
            'DOC_SERIE' => $initData['passport_series'],
            'DOC_NUMBER' => $initData['passport_number'],
            'DOC_ISSUEDATE' => $initData['issueDate'],
            'REGISTRATION_TYPE' => '03',
            'REGION':34000000000,
            'DISTRICT':'Костромской',
            'CITY':'Кострома',
            'STREET':'Сусанина Ивана',
            'HOUSE':37,
            'BUILDING':'',
            'FLAT':84,
            'captcha-input' => $swapData['session']->code,
        );

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $url = 'http://xn--b1afk4ade4e.xn--b1ab2a0a.xn--b1aew.xn--p1ai/info-service.htm';
        
        curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        global $http_connecttimeout, $http_timeout;
//        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, $http_connecttimeout);
//        curl_setopt($ch,CURLOPT_TIMEOUT, $http_timeout);
    
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $rContext->setSwapData($swapData);

        $error = false; //$swapData['session'] ? curl_error($rContext->getCurlHandler()) : 'Нет актуальных сессий';

        if(!$error) {
            $content = curl_multi_getcontent($rContext->getCurlHandler());
//            file_put_contents('./logs/mvd/mvd_'.time().'.html',$content);
            $start = strpos($content,'<div class="c-block '); // Ищем блок ответа

            if ($start!==false) {
                $start = strpos($content,'<em>',$start); // Ищем результат
                $finish = strpos($content,'</em>',$start);
                $content = substr($content,$start,$finish - $start);
                $content = trim(strip_tags($content));

                $data['Result'] = new ResultDataField('string','Result', trim($content), 'Результат', 'Результат проверки адреса');
                $data['ResultCode'] = new ResultDataField('string','ResultCode', strpos($content,'не значится')?'VALID':'NOT_VALID', 'Код результата', 'Код результата проверки адреса');

                $resultData = new ResultDataList();
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            } else {
                $start = strpos($content,'<form method="post" id="form"');
                if ($start!==false) {
                    $start = strpos($content,'data-errortip=',$start);
                }
                if ($start!==false) {
                    $start = strpos($content,'"',$start)+1;
                    $finish = strpos($content,'"',$start);
                    $content = trim(substr($content,$start,$finish - $start));

                    if ((strpos($content,'картин')!==false) || (strpos($content,'Код')!==false)) {
                        if (isset($swapData['session']))
                            $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=4,statuscode='invalidcaptcha' WHERE id=" . $swapData['session']->id);
                        $rContext->setSleep(3);
                        return true;
                    } else {
                        $error = trim($content);
                    }
                } else {
                    $error = 'Некорректный ответ ФМС';
                    if (isset($swapData['session']))
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3 WHERE id=" . $swapData['session']->id);
                }
            }
        }

        if(!$error && $swapData['iteration']>10)
            $error='Превышено количество попыток получения ответа';

        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();
            return false;
        }

        return true;
    }
}

?>
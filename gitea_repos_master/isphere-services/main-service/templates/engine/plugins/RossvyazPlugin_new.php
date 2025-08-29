<?php

class RossvyazPlugin_new implements PluginInterface
{
    public function __construct()
    {
    }

    public function getName()
    {
        return 'Rossvyaz';
    }

    public function getTitle()
    {
        return 'Поиск по номерной емкости Россвязи и БДПН НИИР';
    }

    private $regions = [
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
    ];

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=36 ORDER BY lasttime limit 1");

        if ($result) {
            $row = $result->fetch_object();

            if ($row) {
                $sessionData = new \stdClass();

                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = \strlen($row->proxy_auth) > 1 ? $row->proxy_auth : false;

                $sessionData->id = $row->id;
                $sessionData->code = $row->captcha;
                $sessionData->token = $row->token;
                $sessionData->starttime = $row->starttime;
                $sessionData->lasttime = $row->lasttime;
                $sessionData->cookies = $row->cookies;
                $sessionData->proxyid = $row->proxyid;
                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = \strlen($row->proxy_auth) > 1 ? $row->proxy_auth : false;

                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1,statuscode='used' WHERE id=".$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if (!isset($initData['phone'])) {
            $rContext->setFinished();
            $rContext->setError('Не задан номер телефона');

            return false;
        }

        if ('7' != \substr($initData['phone'], 0, 1)) {
            $rContext->setFinished();
            //            $rContext->setError('Поиск производится только по российским телефонам');
            return false;
        }
        /*
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
                return false;
        */
        // //////////////////////////////////////////////////////////////////////////////////////////////////

        (!isset($swapData['iteration'])) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
        //        if (!isset($swapData['session'])) {
        $swapData['session'] = $this->getSessionData();
        if (!$swapData['session']) {
            if (isset($swapData['iteration']) && ($swapData['iteration'] >= 10)) {
                //                    $rContext->setFinished();
                //                    $rContext->setError('Сервис временно недоступен');
                //                    return false;
            } else {
                $rContext->setSwapData($swapData);
                $rContext->setSleep(1);

                return false;
            }
        }
        //            $swapData['iteration']=1;
        $rContext->setSwapData($swapData);
        //        }

        $ch = $rContext->getCurlHandler();

        if (!$swapData['session']) {
            $url = 'https://i-sphere.ru/';
        } else {
            $url = 'https://www.niir.ru/bdpn/bdpn-proverka-nomera/';

            $params['num'] = \substr($initData['phone'], 1);
            \curl_setopt($ch, \CURLOPT_POST, true);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, \http_build_query($params));

            \curl_setopt($ch, \CURLOPT_SSL_VERIFYPEER, false);
            \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
            if ($swapData['session']->proxy) {
                \curl_setopt($ch, \CURLOPT_PROXY, $swapData['session']->proxy);
                if ($swapData['session']->proxy_auth) {
                    \curl_setopt($ch, \CURLOPT_PROXYUSERPWD, $swapData['session']->proxy_auth);
                    \curl_setopt($ch, \CURLOPT_PROXYAUTH, \CURLAUTH_ANY);
                }
            }
        }

        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_TIMEOUT, 15);

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(array $params, &$rContext)
    {
        global $mysqli;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        //        if (!array_key_exists('number',$swapData)) {
        $phone_number = $initData['phone'];
        if ($phone_number) {
            $phone_number = \preg_replace("/\D/", '', $phone_number);
        }
        if ($phone_number && (11 == \strlen($phone_number)) && (('7' == \substr($phone_number, 0, 1)) || ('8' == \substr($phone_number, 0, 1)))) {
            $phone_number = \substr($phone_number, 1);
        }
        $swapData['number'] = $phone_number;

        if ((10 == \strlen($phone_number)) && (('3' == \substr($phone_number, 0, 1)) || ('4' == \substr($phone_number, 0, 1)) || ('8' == \substr($phone_number, 0, 1)) || ('9' == \substr($phone_number, 0, 1)))) {
            $data['phone'] = new ResultDataField('string', 'PhoneNumber', '7'.$phone_number, 'Номер', 'Номер телефона');
            $phone_code = \substr($phone_number, 0, 3);
            $phone_shortnumber = \substr($phone_number, 3);

            //                $data['phone_code'] = new ResultDataField('string','PhoneCode', $phone_code, 'Код', 'Код телефона');
            //                $data['phone_shortnumber'] = new ResultDataField('string','PhoneShortNumber', $phone_shortnumber, 'Номер без кода', 'Номер телефона без кода');

            $phone_standart = ('9' == \substr($phone_number, 0, 1)) ? 'Мобильный' : 'Стационарный';
            $result = $mysqli->query("SELECT p.*,ps.name phone_standart FROM rossvyaz.phoneinfo p, rossvyaz.phonestandart ps WHERE p.code=$phone_code AND p.start<=$phone_shortnumber AND p.end>=$phone_shortnumber AND p.phonestandartid=ps.id");
            if ($result) {
                $row = $result->fetch_object();
                if ($row) {
                    $phone_standart = $row->phone_standart;
                    if (null != $row->otherstart) {
                        $other = \sprintf('%07d', $phone_shortnumber - $row->start + $row->otherstart);
                        //                            $data['phone_othercode'] = new ResultDataField('string','PhoneOtherCode', $row->othercode, 'Альтернативный код', 'Альтернативный код телефона');
                        //                            $data['phone_othershortnumber'] = new ResultDataField('string','PhoneOtherShortNumber', $other, 'Альтернативный номер без кода', 'Альтернативный номер телефона без кода');
                        $data['phone_othernumber'] = new ResultDataField('phone', 'PhoneOtherNumber', '7'.$row->othercode.$other, 'Альтернативный номер', 'Альтернативный номер телефона');
                    }
                }
                $result->close();
            }

            $result = $mysqli->query("SELECT * FROM rossvyaz.phoneinfo WHERE othercode=$phone_code AND otherstart IS NOT NULL AND otherstart<=$phone_shortnumber AND otherend>=$phone_shortnumber");
            if ($result) {
                $row = $result->fetch_object();
                if ($row) {
                    if (null != $row->start) {
                        $other = \sprintf('%07d', $phone_shortnumber - $row->otherstart + $row->start);
                        //                            $data['phone_othercode'] = new ResultDataField('string','PhoneOtherCode', $row->code, 'Альтернативный код', 'Альтернативный код телефона');
                        //                            $data['phone_othershortnumber'] = new ResultDataField('string','PhoneOtherShortNumber', $other, 'Альтернативный номер без кода', 'Альтернативный номер телефона без кода');
                        $data['phone_othernumber'] = new ResultDataField('phone', 'PhoneOtherNumber', '7'.$row->code.$other, 'Альтернативный номер', 'Альтернативный номер телефона');
                    }
                }
                $result->close();
            }

            $result = $mysqli->query("SELECT * FROM rossvyaz.rossvyaz
                    WHERE abcdef='{$phone_code}' AND phone_poolstart<='{$phone_shortnumber}' AND phone_poolend>='{$phone_shortnumber}'");

            if ($result && ($row = $result->fetch_object())) {
                $op = $row->operator;
                if (\preg_match('/"([^"]+)"/', $op, $matches)) {
                    $op = $matches[1];
                }
                $data['phone_operator'] = new ResultDataField('string', 'PhoneOperator', $op, 'Оператор номера', 'Оператор номера');
                $data['phone_region'] = new ResultDataField('string', 'PhoneRegion', \trim($row->region1), 'Регион', 'Регион регистрации номера');
                if ($row->region2) {
                    $data['phone_regiondetails'] = new ResultDataField('string', 'PhoneRegionDetails', \trim($row->region2.' '.$row->region3), 'Район', 'Район регистрации номера');
                }
                if ($row->regioncode) {
                    $data['phone_regioncode'] = new ResultDataField('string', 'PhoneRegionCode', $row->regioncode, 'Код региона', 'Код региона регистрации номера');
                }
            } else {
                $phone_standart = 'Номер не существует';
            }
        } else {
            $rContext->setFinished();
            //                $rContext->setError('Поиск производится только по российским номерам');
            return false;
        }
        $data['phone_standart'] = new ResultDataField('string', 'PhoneStandart', $phone_standart, 'Стандарт', 'Стандарт телефона');
        $swapData['data'] = $data;
        //        } else
        //            $data = $swapData['data'];

        $swapData['iteration'] = (!isset($swapData['iteration'])) ? 1 : $swapData['iteration'] + 1;
        $error = ($swapData['iteration'] > 5) && \curl_error($rContext->getCurlHandler());
        $content = \curl_multi_getcontent($rContext->getCurlHandler());

        if (!$swapData['session']) {
            $resultData = new ResultDataList();
            $resultData->addResult($data);
            $rContext->setResultData($resultData);
            $rContext->setFinished();

            return true;
        } elseif (!$error && $content && \preg_match('/Номер: /', $content, $matches)) {
            \file_put_contents('./logs/rossvyaz/bdpn_'.\time().'.txt', $content);

            if (\preg_match('/Оператор: <a[^>]+>([^<]+)/', $content, $matches) || \preg_match('/Оператор: ([^<]+)/', $content, $matches)) {
                /*
                                $op = trim($matches[1]);
                                if(preg_match("/\"([^\"]+)\"/", $op, $matches)){
                                    $op = $matches[1];
                                }
                */
                $data['operator'] = new ResultDataField('string', 'Operator', \trim($matches[1]), 'Обслуживающий оператор', 'Обслуживающий оператор');
            }
            if (\preg_match('/Регион: ([^<]+)/', $content, $matches)) {
                $data['region'] = new ResultDataField('string', 'Region', \trim($matches[1]), 'Регион', 'Регион');
            }
            $resultData = new ResultDataList();
            $resultData->addResult($data);
            $rContext->setResultData($resultData);
            $rContext->setFinished();
            $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);

            return true;
        } else {
            \file_put_contents('./logs/rossvyaz/bdpn_err_'.\time().'.txt', $content);
            $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,statuscode='error',endtime=now() WHERE statuscode='used' AND id=".$swapData['session']->id);
            unset($swapData['session']);
        }

        $rContext->setSwapData($swapData);

        if (!$error && isset($swapData['iteration']) && $swapData['iteration'] > 5) {
            $error = 'Превышено количество попыток получения ответа';
        }

        if ($error || '9' != \substr($swapData['number'], 0, 1)) {
            $resultData = new ResultDataList();
            $resultData->addResult($data);
            $rContext->setResultData($resultData);
            $rContext->setFinished();
        }

        return true;
    }
}

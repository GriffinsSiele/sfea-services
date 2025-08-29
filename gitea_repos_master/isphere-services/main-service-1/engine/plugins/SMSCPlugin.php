<?php

class SMSCPlugin implements PluginInterface
{
    private $login = 'kotiphones';
    private $password = '6367863Qq';
//    private $login = 'isphere';
//    private $password = '6644283';
//    private $login = 'cartarget';
//    private $password = 'sm143960';

    public function getName()
    {
        return 'SMSC';
    }

    public function getTitle()
    {
        return 'Проверка доступности абонента мобильной связи (SMSC)';
    }

    private $hlrErrors = array(
        0 => 'Доступен',
        1 => 'Не существует',
        6 => 'Не в сети',
        11 => 'Нет услуги SMS',
        13 => 'Заблокирован',
        21 => 'Не принимает SMS',
        99 => 'Неизвестная ошибка',
        248 => 'Неизвестный оператор',
        249 => 'Неверный номер',
        250 => 'Ограничен доступ',
        251 => 'Превышен лимит',
        252 => 'Номер запрещен',
        253 => 'Услуга не поддерживается',
        255 => 'Запрос отклонен',
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

//        if (strlen($initData['phone'])==10)
//            $initData['phone']='7'.$initData['phone'];
//        if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
//            $initData['phone']='7'.substr($initData['phone'],1);
/*
        if(substr($initData['phone'],0,2)!='79')
        {
            $rContext->setFinished();
            $rContext->setError('Поиск производится только по мобильным телефонам в коде 9xx');

            return false;
        }
*/
        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();
        $host = 'https://smsc.ru';

        if(isset($swapData['mode']) && $swapData['mode'] == 'status')
        {
            $params = array(
                'phone' => $initData['phone'],
                'login' => $this->login,
                'psw' => $this->password,
                'id' => $swapData['smsc_id'],
                'fmt' => 3,
                'over' => 1,
            );

            $url = $host.'/sys/status.php';
        }
        else
        {
            $params = array(
                'phones' => $initData['phone'],
                'login' => $this->login,
                'psw' => $this->password,
                'hlr' => 1,
            );

            $url = $host.'/sys/send.php';
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration']))?0:$swapData['iteration'];
        $error = ($swapData['iteration']>5) && false; //curl_error($rContext->getCurlHandler());
        if(!$error)
        {
            $content = curl_multi_getcontent($rContext->getCurlHandler());
//            file_put_contents('./logs/smsc/smsc_' . time() . '.json', $content);
            $content = iconv('windows-1251', 'utf-8//ignore', $content);
            $ares = json_decode($content, true);

            if(isset($swapData['mode']) && $swapData['mode'] == 'status')
            {
                $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;

                if ($ares && array_key_exists('status',$ares))
                {
                    $data['phone'] = new ResultDataField('string','PhoneNumber', $initData['phone'], 'Номер', 'Номер телефона');

                    if ($ares['status']>0)
                    {
                        if ($ares['status']==1)
                        {
                            $data['hlr_status'] = new ResultDataField('string','HLRStatus', $ares['status'], 'Код статуса', 'Код статуса абонента');
                            $data['hlr_statustext'] = new ResultDataField('string','HLRStatusText', 'Доступен', 'Статус', 'Статус абонента');
                        }
                        else
                        {
                            if (array_key_exists('err',$ares))
                            {
                                $data['hlr_status']= -$ares['err'];
                                $data['hlr_status'] = new ResultDataField('string','HLRStatus', -$ares['err'], 'Код статуса', 'Код статуса абонента');
                                $data['hlr_statustext'] = new ResultDataField('string','HLRStatusText', array_key_exists($ares['err'], $this->hlrErrors) ? $this->hlrErrors[$ares['err']] : 'Ошибка обработки запроса', 'Статус', 'Статус абонента');
                            }
                            else
                            {
                                $data['hlr_status'] = new ResultDataField('string','HLRStatus', -99, 'Код статуса', 'Код статуса абонента');
                                $data['hlr_statustext'] = new ResultDataField('string','HLRStatusText', 'Услуга не поддерживается', 'Статус', 'Статус абонента');
                            }
                        }

                        if (array_key_exists('net',$ares)) {
                            $data['hlr_operator'] = new ResultDataField('string','Operator', $ares['net'], 'Оператор', 'Наименование оператора связи');
                        }
                        if (array_key_exists('cn',$ares)) {
                            $data['hlr_country'] = new ResultDataField('string','Country', $ares['cn'], 'Страна', 'Страна оператора связи');
                        }
                        if (array_key_exists('imsi',$ares)) {
                            $data['imsi'] = new ResultDataField('string','IMSI', $ares['imsi'], 'IMSI', 'Уникальный номер SIM-карты');
                        }
                        if (array_key_exists('msc',$ares)) {
                            $data['msc'] = new ResultDataField('string','MSC', substr($ares['msc'] . 'xxxxx',0,11), 'MSC', 'Номер узла оператора');
/*
                            if (substr($ares['msc'],0,1)=='7') {
                                $msc_code = substr($ares['msc'],1,3);
                                $msc_number = substr($ares['msc'] . '00000',4,7);
                                $result = $mysqli->query("SELECT * FROM rossvyaz.rossvyaz_pool
                                    WHERE abcdef='{$msc_code}' AND phone_poolstart<='{$msc_number}' AND phone_poolend>='{$msc_number}'");		
                                if ($result && ($row = $result->fetch_object())) {
//                                    $data['msc_operator'] = new ResultDataField('string','MSCOperator', $row->operator, 'Обслуживающий оператор', 'Наименование обслуживающего оператора связи');
                                    $data['region'] = new ResultDataField('string','Region', $row->region1, 'Регион', 'Регион местоположения абонента');
//                                    if ($row->region2)
//                                        $data['msc_regiondetails'] = new ResultDataField('string','RegionDetails', trim($row->region2 . ' ' . $row->region3), 'Район', 'Район регистрации номера');
                                    if ($row->regioncode)
                                        $data['regioncode'] = new ResultDataField('string','RegionCode', $row->regioncode, 'Код региона', 'Код региона местоположения абонента');
                                }
                                $result->close();
                            } else { //Роуминг
                            }
*/
                        }

                        $resultData = new ResultDataList();
                        $resultData->addResult($data);
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
                        return true;
                    }
                    else
                    {
                        $rContext->setSleep(3);
                    }
                }
                else
                {
                    if ($ares && array_key_exists('error', $ares)) {
                        if (!strpos($ares['error'],'wait a minute'))
                            $error = $ares['error'];
                    } else {
                        if ($ares) $error = 'Некорректный ответ сервиса';
                    }

                    if ($error) {
                        $rContext->setError($error);
                        $rContext->setFinished();
                    } else {
                        $rContext->setSleep(3);
                    }
                }
            }
            else
            {
                if (substr($content,0,2)=='OK')
                {
                    //$swapData['smsc_id'] = str_between($content . ',','ID - ',',');
                    $swapData['smsc_id'] = substr($content, 17);
                    $swapData['mode'] = 'status';

                    $rContext->setSleep(3);
                }
                else
                {
                    if(strpos($content,'ERROR')!==false)
                        $error = preg_replace("/,\sID\s-\s[\d]+$/","",$content);
                    else
                        if ($ares) $error = "Невозможно обработать ответ сервиса";

                    if ($error) {
                        $rContext->setError($error);
                        $rContext->setFinished();
                    }

                    return false;
                }
            }


        }

        $rContext->setSwapData($swapData);
/*
        if(isset($swapData['iteration']) && $swapData['iteration']>30)
        {
            $data['hlr_status'] = new ResultDataField('string','HLRStatus', -1, 'Код статуса', 'Код статуса абонента');
            $data['hlr_statustext'] = new ResultDataField('string','HLRStatusText', 'Абонент не зарегистрирован в сети', 'Статус', 'Статус абонента');

            $resultData = new ResultDataList();
            $resultData->addResult($data);
            $rContext->setResultData($resultData);
            $rContext->setFinished();

            return false;
        }
*/
        return true;
    }
}

?>
<?php

class InfobipPlugin implements PluginInterface
{
    private $login = 'sphere';
    private $password = '1882234Qq';

    public function getName()
    {
        return 'Infobip';
    }

    public function getTitle()
    {
        return 'Проверка доступности абонента мобильной связи (Infobip)';
    }

    private $hlrStatus = array(
        'DELIVRD' => 'Доступен',
        'UNDELIV' => 'Недоступен',
        'UNKNOWN' => 'Ошибка',
        'REJECTD' => 'Не существует',
        'EXPIRED' => 'Истек срок',
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

        $url = 'https://api.infobip.com/api/hlr/sync?output=json&user='.$this->login.'&pass='.$this->password.'&destination='.$initData['phone'];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);      

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $error = ($swapData['iteration']>5) && curl_error($rContext->getCurlHandler());
        if (!$error) {
            $content = curl_multi_getcontent($rContext->getCurlHandler());
            file_put_contents('./logs/infobip/'.time().'.txt', $content."\n\n".$swapData['iteration']);
            $ares = json_decode($content, true);

//            $data['json_result'] = new ResultDataField('string','JSON', $content, 'Ответ JSON', 'Ответ JSON');
            if ($ares && array_key_exists('destination',$ares)) {
                $data['phone'] = new ResultDataField('string','PhoneNumber', $ares['destination'], 'Номер', 'Номер телефона');
                if (array_key_exists('stat',$ares))
                    $data['hlr_status'] = new ResultDataField('string','HLRStatus', $this->hlrStatus[$ares['stat']], 'Код статуса', 'Код статуса абонента');
                if (array_key_exists('mccmnc',$ares))
                    $data['mcc'] = new ResultDataField('string','MCC', $ares['mccmnc'], 'MCC', 'MCC');
                if (array_key_exists('IMSI',$ares))  
                    $data['imsi'] = new ResultDataField('string','IMSI', $ares['IMSI'], 'IMSI', 'IMSI');
                if (array_key_exists('orn',$ares))
                    $data['phoneoperator'] = new ResultDataField('string','PhoneOperator', $ares['orn'], 'Оператор номера', 'Оператор номера');
                if (array_key_exists('is_ported',$ares))
                    $data['ported'] = new ResultDataField('string','Ported', $ares['is_ported'], 'Перенос номера', 'Перенос номера');
                if (array_key_exists('pon',$ares))
                    $data['operator'] = new ResultDataField('string','Operator', $ares['pon'], 'Оператор абонента', 'Оператор абонента (SIM)');
                if (array_key_exists('ocn',$ares))
                    $data['country'] = new ResultDataField('string','Country', $ares['ocn'], 'Страна абонента', 'Страна абонента');
                if (array_key_exists('is_roaming',$ares))
                    $data['roaming'] = new ResultDataField('string','Roaming', $ares['is_roaming'], 'В роуминге', 'В роуминге');
                if (array_key_exists('ron',$ares))
                    $data['roamingoperator'] = new ResultDataField('string','RoamingOperator', $ares['ron'], 'Сеть регистрации', 'Сеть регистрации абонента (роуминг)');
                if (array_key_exists('rcn',$ares))
                    $data['roamingcountry'] = new ResultDataField('string','RoamingCountry', $ares['rcn'], 'Страна регистрации', 'Страна регистрации абонента (роуминг)');
                if (array_key_exists('MSC',$ares)) {
                    $data['msc'] = new ResultDataField('string','MSC', $ares['MSC'], 'MSC', 'MSC');
/*
                    if (substr($ares['MSC'],0,1)=='7') {
                        $msc_code = substr($ares['MSC'],1,3);
                        $msc_number = substr($ares['MSC'] . '00000',4,7);
                        $result = $mysqli->query("SELECT * FROM cron.rossvyaz
                            WHERE abcdef='{$msc_code}' AND phone_poolstart<='{$msc_number}' AND phone_poolend>='{$msc_number}'");		
                        if ($result && ($row = $result->fetch_object())) {
                            $data['region'] = new ResultDataField('string','Region', $row->region1, 'Регион', 'Регион местоположения абонента');
//                            $data['msc_operator'] = new ResultDataField('string','MSCOperator', $row->operator, 'Обслуживающий оператор', 'Наименование обслуживающего оператора связи');
//                            if ($row->region2)
//                                $data['msc_regiondetails'] = new ResultDataField('string','RegionDetails', trim($row->region2 . ' ' . $row->region3), 'Район', 'Район регистрации номера');
                            if ($row->regioncode)
                                $data['regioncode'] = new ResultDataField('string','RegionCode', $row->regioncode, 'Код региона', 'Код региона местоположения абонента');
                        }
                    } else { //Роуминг
                    }
*/
                }
                if (array_key_exists('error', $ares)) {
                    $error = $ares['error']['description'];
                }

                $resultData = new ResultDataList();
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                return true;
            }
            else
            {
                if (strpos($content,'FAILED')===0)
                    $error = 'Ошибка при выполнении запроса';
                else
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
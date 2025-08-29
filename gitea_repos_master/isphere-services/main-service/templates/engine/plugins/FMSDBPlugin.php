<?php

class FMSDBPlugin implements PluginInterface
{
    private $passport_region_series = ['00' => '00', '79' => '01', '84' => '04', '80' => '02', '81' => '03', '82' => '05', '26' => '06', '83' => '07', '85' => '08', '91' => '09', '86' => '10', '87' => '11', '88' => '12', '89' => '13', '98' => '14', '90' => '15', '92' => '16', '93' => '17', '94' => '18', '95' => '19', '96' => '20', '97' => '21', '01' => '22', '03' => '23', '04' => '24', '05' => '25', '07' => '26', '08' => '27', '10' => '28', '11' => '29', '12' => '30', '14' => '31', '15' => '32', '17' => '33', '18' => '34', '19' => '35', '20' => '36', '24' => '37', '25' => '38', '27' => '39', '29' => '40', '30' => '41', '32' => '42', '33' => '43', '34' => '44', '37' => '45', '38' => '46', '41' => '47', '42' => '48', '44' => '49', '46' => '50', '47' => '51', '22' => '52', '49' => '53', '50' => '54', '52' => '55', '53' => '56', '54' => '57', '56' => '58', '57' => '59', '58' => '60', '60' => '61', '61' => '62', '36' => '63', '63' => '64', '64' => '65', '65' => '66', '66' => '67', '68' => '68', '28' => '69', '69' => '70', '70' => '71', '71' => '72', '73' => '73', '75' => '74', '76' => '75', '78' => '76', '45' => '77', '40' => '78', '99' => '79', '43' => '80', '48' => '81', '51' => '41', '55' => '83', '59' => '84', '62' => '85', '67' => '86', '77' => '87', '72' => '88', '74' => '89', '39' => '82', '09' => '92'];
    private $regions = ['00' => 'Не определен', '01' => 'Республика Адыгея (Адыгея)', '02' => 'Республика Башкортостан', '03' => 'Республика Бурятия', '04' => 'Республика Алтай', '05' => 'Республика Дагестан', '06' => 'Республика Ингушетия', '07' => 'Кабардино-Балкарская Республика', '08' => 'Республика Калмыкия', '09' => 'Карачаево-Черкесская Республика', '10' => 'Республика Карелия', '11' => 'Республика Коми', '12' => 'Республика Марий Эл', '13' => 'Республика Мордовия', '14' => 'Республика Саха (Якутия)', '15' => 'Республика Северная Осетия - Алания', '16' => 'Республика Татарстан', '17' => 'Республика Тыва', '18' => 'Удмуртская Республика', '19' => 'Республика Хакасия', '20' => 'Чеченская Республика', '21' => 'Чувашская Республика', '22' => 'Алтайский край', '23' => 'Краснодарский край', '24' => 'Красноярский край', '25' => 'Приморский край', '26' => 'Ставропольский край', '27' => 'Хабаровский край', '28' => 'Амурская область', '29' => 'Архангельская область', '30' => 'Астраханская область', '31' => 'Белгородская область', '32' => 'Брянская область', '33' => 'Владимирская область', '34' => 'Волгоградская область', '35' => 'Вологодская область', '36' => 'Воронежская область', '37' => 'Ивановская область', '38' => 'Иркутская область', '39' => 'Калининградская область', '40' => 'Калужская область', '41' => 'Камчатский край', '42' => 'Кемеровская область', '43' => 'Кировская область', '44' => 'Костромская область', '45' => 'Курганская область', '46' => 'Курская область', '47' => 'Ленинградская область', '48' => 'Липецкая область', '49' => 'Магаданская область', '50' => 'Московская область', '51' => 'Мурманская область', '52' => 'Нижегородская область', '53' => 'Новгородская область', '54' => 'Новосибирская область', '55' => 'Омская область', '56' => 'Оренбургская область', '57' => 'Орловская область', '58' => 'Пензенская область', '59' => 'Пермский край', '60' => 'Псковская область', '61' => 'Ростовская область', '62' => 'Рязанская область', '63' => 'Самарская область', '64' => 'Саратовская область', '65' => 'Сахалинская область', '66' => 'Свердловская область', '67' => 'Смоленская область', '68' => 'Тамбовская область', '69' => 'Тверская область', '70' => 'Томская область', '71' => 'Тульская область', '72' => 'Тюменская область', '73' => 'Ульяновская область', '74' => 'Челябинская область', '75' => 'Читинская область', '76' => 'Ярославская область', '77' => 'Москва', '78' => 'Санкт - Петербург', '79' => 'Еврейская автономная область', '80' => 'Агинский Бурятский автономный округ', '81' => 'Коми-Пермяцкий автономный округ', '82' => 'Республика Крым', '83' => 'Ненецкий автономный округ', '84' => 'Таймырский (Долгано-Ненецкий) автономный округ', '85' => 'Усть-Ордынский Бурятский автономный округ', '86' => 'Ханты-Мансийский автономный округ (Югра)', '87' => 'Чукотский автономный округ', '88' => 'Эвенкийский автономный округ', '89' => 'Ямало-Ненецкий автономный округ', '92' => 'Севастополь', '99' => 'Иные территории, Байконур'];

    public function getName()
    {
        return 'FMSDB';
    }

    public function getTitle()
    {
        return 'Проверка паспорта по списку недействительных паспортов ГУВМ МВД РФ (ранее ФМС)';
    }

    public function getRegionCode($code)
    {
        return \array_key_exists($code, $this->passport_region_series) ? $this->passport_region_series[$code] : '';
    }

    public function getRegionName($code)
    {
        return \array_key_exists($code, $this->passport_region_series) ? $this->regions[$this->passport_region_series[$code]] : '';
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        if (!isset($initData['passport_series']) || !isset($initData['passport_number'])) {
            $rContext->setFinished();
            //            $rContext->setError('Не указаны серия и номер паспорта');
            return false;
        }
        if (!\preg_match('/^\\d{4}$/', $initData['passport_series']) || !\preg_match('/^\\d{6}$/', $initData['passport_number'])) {
            $rContext->setFinished();
            $rContext->setError('Некорректные значения серии или номера паспорта');

            return false;
        }
        $swapData['iteration'] = !isset($swapData['iteration']) ? 1 : $swapData['iteration'] + 1;
        $rContext->setSwapData($swapData);
        // //////////////////////////////////////////////////////////////////////////////////////////////////
        $ch = $rContext->getCurlHandler();
        //        $url = 'https://dev.i-sphere.ru/pass/pass.php?p='.$initData['passport_series'].$initData['passport_number'];
        //        $url = 'http://172.16.12.4:8000/items/'.$initData['passport_series'].','.$initData['passport_number'];
        //        $url = 'http://172.16.12.6:8080/passport?series='.$initData['passport_series'].'&number='.$initData['passport_number'];
        //        $url = 'http://10.10.10.1:8080/passport?series='.$initData['passport_series'].'&number='.$initData['passport_number'];
        $url = 'http://172.16.1.25'.(3 + $swapData['iteration'] % 1).':8080/passport?series='.$initData['passport_series'].'&number='.$initData['passport_number'];
        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_TIMEOUT, 5);
        $rContext->setCurlHandler($ch);
        //        print($swapData['iteration'].' '.$url."\n");
        return true;
    }

    public function computeRequest(array $params, &$rContext)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        $content = \curl_multi_getcontent($rContext->getCurlHandler());
        $error = $content ? false : \curl_error($rContext->getCurlHandler());
        /*
                if ($content) {
                    $res = json_decode($content,true);
                    $content = $res['count'];
                }
        */
        if (!$error) {
            if (\strpos($content, 'valid')) {
                $not_valid = \strpos($content, 'non') > 0;
                $data['Result'] = new ResultDataField('string', 'Result', $not_valid ? 'Не действителен' : 'Среди недействительных не значится', 'Результат', 'Результат проверки паспорта');
                $data['ResultCode'] = new ResultDataField('string', 'ResultCode', $not_valid ? 'NOT_VALID' : 'VALID', 'Код результата', 'Код результата проверки паспорта');
                $data['Region'] = new ResultDataField('string', 'Region', $this->getRegionName(\substr($initData['passport_series'], 0, 2)), 'Регион', 'Регион выдачи паспорта');
                $data['RegionCode'] = new ResultDataField('string', 'RegionCode', $this->getRegionCode(\substr($initData['passport_series'], 0, 2)), 'Код региона', 'Код региона выдачи паспорта');
                $data['DataSource'] = new ResultDataField('string', 'DataSource', 'База данных недействительных паспортов', 'Источник информации', 'Источник информации');
                $data['DataSourceCode'] = new ResultDataField('string', 'DataSourceCode', 'DATABASE', 'Код источника информации', 'Код источника информации');
                $resultData = new ResultDataList();
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                return true;
            } else {
                //                    $error = 'Список недействительных паспортов недоступен';
            }
        }
        /*
                if ($error && $swapData['iteration']>3) {
                    $result = $mysqli->query("SELECT passport FROM big.PassportsExpired WHERE passport='".$initData['passport_series'].$initData['passport_number']."'");
                    if ($result) {
                        $not_valid = $result->rowCount();
                        $data['Result'] = new ResultDataField('string','Result', $not_valid?'Не действителен':'Среди недействительных не значится', 'Результат', 'Результат проверки паспорта');
                        $data['ResultCode'] = new ResultDataField('string','ResultCode', $not_valid?'NOT_VALID':'VALID', 'Код результата', 'Код результата проверки паспорта');
                        $data['Region'] = new ResultDataField('string','Region', $this->getRegionName(substr($initData['passport_series'],0,2)), 'Регион', 'Регион выдачи паспорта');
                        $data['RegionCode'] = new ResultDataField('string','RegionCode', $this->getRegionCode(substr($initData['passport_series'],0,2)), 'Код региона', 'Код региона выдачи паспорта');
                        $data['DataSource'] = new ResultDataField('string','DataSource', 'База данных недействительных паспортов', 'Источник информации', 'Источник информации');
                        $data['DataSourceCode'] = new ResultDataField('string','DataSourceCode', 'DATABASE', 'Код источника информации', 'Код источника информации');

                        $resultData = new ResultDataList();
                        $resultData->addResult($data);
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
                        return true;
                    } else {
                        $error = 'Ошибка обращения к БД недействительных паспортов';
                    }
                }
        */
        $rContext->setSwapData($swapData);
        if ($swapData['iteration'] > 5) {
            $error = 'Превышено количество попыток получения ответа';
        }
        if ($error && $swapData['iteration'] >= 3) {
            $rContext->setError($error);
            $rContext->setFinished();

            return false;
        }
        $rContext->setSleep(1);

        return true;
    }
}

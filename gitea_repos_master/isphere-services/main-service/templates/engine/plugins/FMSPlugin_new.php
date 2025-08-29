<?php

class FMSPlugin_new implements PluginInterface
{
    private $passport_region_series = [
        '00' => '00',
        '79' => '01',
        '84' => '04',
        '80' => '02',
        '81' => '03',
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
    ];

    private $regions = [
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
    ];

    public function getName()
    {
        return 'FMS';
    }

    public function getTitle()
    {
        return 'Проверка паспорта в ФМС РФ';
    }

    public function getRegionCode($code)
    {
        return \array_key_exists($code, $this->passport_region_series) ? $this->passport_region_series[$code] : '';
    }

    public function getRegionName($code)
    {
        return \array_key_exists($code, $this->passport_region_series) ? $this->regions[$this->passport_region_series[$code]] : '';
    }

    public function getSessionData($sourceid = 1)
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

        if ($sourceid) {
            $mysqli->query("UPDATE isphere.session s SET lasttime=now(),request_id=$reqId WHERE request_id IS NULL AND sessionstatusid=2 AND sourceid=$sourceid ORDER BY lasttime limit 1");
            $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sourceid=$sourceid AND request_id=$reqId ORDER BY lasttime limit 1");
        } else {
            $result = $mysqli->query("SELECT 0 id,'' cookies,now() starttime,now() lasttime,'' captcha,'' token,id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE status=1 AND proxygroup=1 ORDER BY lasttime limit 1");
        }

        if ($result) {
            $row = $result->fetch_object();

            if ($row) {
                $sessionData = new \stdClass();

                $sessionData->id = $row->id;
                $sessionData->code = $row->captcha;
                $sessionData->starttime = $row->starttime;
                $sessionData->lasttime = $row->lasttime;
                $sessionData->cookies = $row->cookies;
                $sessionData->token = $row->token;
                $sessionData->proxyid = $row->proxyid;
                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = \strlen($row->proxy_auth) > 1 ? $row->proxy_auth : false;

                if ($sourceid) {
                    $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1,statuscode='used',request_id=NULL".($row->captcha && 0 ? ',endtime=now(),sessionstatusid=3' : '').' WHERE id='.$sessionData->id);
                } else {
                    //                    $mysqli->query("UPDATE isphere.proxy SET lasttime=now() WHERE id=".$sessionData->proxyid);
                }
            }
        }

        return $sessionData;
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

        if (!\preg_match("/^\d{4}$/", $initData['passport_series']) || !\preg_match("/^\d{6}$/", $initData['passport_number'])/* || !intval($initData['passport_series']) */) {
            $rContext->setFinished();
            $rContext->setError('Некорректные значения серии или номера паспорта');

            return false;
        }

        // //////////////////////////////////////////////////////////////////////////////////////////////////

        (!isset($swapData['iteration'])) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
        if (!isset($swapData['db']) && !isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData();
            if (!$swapData['session']) {
                if (isset($swapData['iteration']) && ($swapData['iteration'] >= 10)) {
                    //                    $rContext->setFinished();
                    //                    $rContext->setError('Сервис временно недоступен');
                    //                    return false;
                    $swapData['db'] = true;
                    $swapData['iteration'] = 0;
                } else {
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);

                    return false;
                }
            }
        }
        $rContext->setSwapData($swapData);

        // //////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        if (!isset($swapData['db'])) {
            $params = [
                'sid' => '2000',
                'form_name' => 'form',
                'DOC_SERIE' => $initData['passport_series'],
                'DOC_NUMBER' => $initData['passport_number'],
                'captcha-input' => $swapData['session']->code,
            ];
            $url = 'http://services.fms.gov.ru/info-service.htm';
            \curl_setopt($ch, \CURLOPT_URL, $url);
            //            curl_setopt($ch, CURLOPT_HEADER, true);
            //            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            \curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, 1);
            \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
            \curl_setopt($ch, \CURLOPT_REFERER, $url.'?sid=2000');
            \curl_setopt($ch, \CURLOPT_POST, true);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, $params);
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 10);

            if ($swapData['session']->proxy) {
                \curl_setopt($ch, \CURLOPT_PROXY, $swapData['session']->proxy);
                if ($swapData['session']->proxy_auth) {
                    \curl_setopt($ch, \CURLOPT_PROXYUSERPWD, $swapData['session']->proxy_auth);
                    \curl_setopt($ch, \CURLOPT_PROXYAUTH, \CURLAUTH_ANY);
                }
            }
        } else {
            //            $url = 'https://dev.i-sphere.ru/pass/pass.php?p='.$initData['passport_series'].$initData['passport_number'];
            //            $url = 'http://172.16.12.4:8000/items/'.$initData['passport_series'].','.$initData['passport_number'];
            //            $url = 'http://172.16.12.6:8080/passport?series='.$initData['passport_series'].'&number='.$initData['passport_number'];
            //            $url = 'http://10.10.10.1:8080/passport?series='.$initData['passport_series'].'&number='.$initData['passport_number'];
            $url = 'http://172.16.1.25'.(3 + $swapData['iteration'] % 1).':8080/passport?series='.$initData['passport_series'].'&number='.$initData['passport_number'];
            \curl_setopt($ch, \CURLOPT_URL, $url);
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 3);
        }

        $rContext->setCurlHandler($ch);
        echo $swapData['iteration'].' '.$url."\n";

        return true;
    }

    public function computeRequest(array $params, &$rContext)
    {
        global $mysqli;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        //        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        //        $rContext->setSwapData($swapData);

        $content = \curl_multi_getcontent($rContext->getCurlHandler());
        $error = $content ? false : \curl_error($rContext->getCurlHandler());

        \file_put_contents('./logs/fms/fms_'.\time().'.html', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content."\n\n".$swapData['iteration']);

        if (!$error) {
            if (isset($swapData['db'])) {
                /*
                                if ($content) {
                                    $res = json_decode($content,true);
                                    $content = $res['count'];
                                }
                */
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

            $start = \strpos($content, '<div class="m_b35">'); // Ищем результат запроса

            if (false !== $start) {
                $start += 19;
                $finish = \strpos($content, '</div>', $start);
                $content = \substr($content, $start, $finish - $start);
                $content = \trim(\strip_tags($content));

                $start = \strpos($content, '«') + 2;
                $finish = \strpos($content, '»');
                $content = \substr($content, $start, $finish - $start);

                $data['Result'] = new ResultDataField('string', 'Result', \strtr(\trim($content), ['C' => 'С']), 'Результат', 'Результат проверки паспорта');
                $data['ResultCode'] = new ResultDataField('string', 'ResultCode', \strpos($content, 'не значится') ? 'VALID' : 'NOT_VALID', 'Код результата', 'Код результата проверки паспорта');
                $data['Region'] = new ResultDataField('string', 'Region', $this->getRegionName(\substr($initData['passport_series'], 0, 2)), 'Регион', 'Регион выдачи паспорта');
                $data['RegionCode'] = new ResultDataField('string', 'RegionCode', $this->getRegionCode(\substr($initData['passport_series'], 0, 2)), 'Код региона', 'Код региона выдачи паспорта');
                $data['DataSource'] = new ResultDataField('string', 'DataSource', 'Онлайн-проверка на сайте ФМС', 'Источник информации', 'Источник информации');
                $data['DataSourceCode'] = new ResultDataField('string', 'DataSourceCode', 'ONLINE', 'Код источника информации', 'Код источника информации');

                $resultData = new ResultDataList();
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                if (isset($swapData['session'])) {
                    $mysqli->query("UPDATE isphere.session SET statuscode='success',success=ifnull(success,0)+1 WHERE statuscode='used' AND id=".$swapData['session']->id);
                }
            } else {
                $start = \strpos($content, '<form method="post" id="form"');
                if (false !== $start) {
                    $start = \strpos($content, 'data-errortip=', $start);
                }
                if (false !== $start) {
                    $start = \strpos($content, '"', $start) + 1;
                    $finish = \strpos($content, '"', $start);
                    $content = \trim(\substr($content, $start, $finish - $start));

                    if ((false !== \strpos($content, 'картин')) || (false !== \strpos($content, 'Код'))) {
                        if (isset($swapData['session'])) {
                            $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=4,statuscode='invalidcaptcha' WHERE id=".$swapData['session']->id);
                        }
                    } else {
                        //                        $error = trim($content);
                    }
                    unset($swapData['session']);
                } elseif (\strpos($content, 'unexpected error')) {
                    \file_put_contents('./logs/fms/fms_err_'.\time().'.html', $content);
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='unexpected' WHERE id=".$swapData['session']->id);
                    }
                } elseif (\strpos($content, 'internal error') || \strpos($content, 'nginx')) {
                    \file_put_contents('./logs/fms/fms_err_'.\time().'.html', $content);
                //                    if (isset($swapData['session']))
                //                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='invalidanswer' WHERE id=" . $swapData['session']->id);
                } elseif ($content) {
                    \file_put_contents('./logs/fms/fms_err_'.\time().'.html', $content);
                    //                    $error = 'Некорректный ответ ФМС';
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='invalidanswer' WHERE id=".$swapData['session']->id);
                    }
                    unset($swapData['session']);
                } else {
                    if (isset($swapData['session'])) {
                        //                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='empty' WHERE id=" . $swapData['session']->id);
                        $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 hour),sessionstatusid=6,statuscode='empty' WHERE id=".$swapData['session']->id);
                    }
                    unset($swapData['session']);
                }
            }
        }

        /*
                if ($error && $swapData['iteration']>3) {
                    $result = $mysqli->query("SELECT passport FROM big.PassportsExpired WHERE passport='".$initData['passport_series'].$initData['passport_number']."'");
                    if ($result) {
                        $not_valid = $result->num_rows;
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

        if (isset($swapData['db']) && $swapData['iteration'] > 5) {
            $error = 'Превышено количество попыток получения ответа';
        }

        if ($error && $swapData['iteration'] >= 5) {
            $rContext->setError($error);
            $rContext->setFinished();

            return false;
        }

        if (!isset($swapData['db']) && $swapData['iteration'] >= 3) {
            $swapData['db'] = true;
        }

        $rContext->setSleep(1);
        $rContext->setSwapData($swapData);

        return true;
    }
}

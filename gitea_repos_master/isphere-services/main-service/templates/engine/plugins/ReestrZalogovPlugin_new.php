<?php

class ReestrZalogovPlugin_new implements PluginInterface
{
    private $googlekey = '6LdKJhMaAAAAAIfeHC6FZc-UVfzDQpiOjaJUWoxr';
    private $captcha_service = [
//        array('host' => 'api.capmonster.cloud', 'key' => 'afb26cbb248d650ea8b8d88822984242'),
        ['host' => 'api.anti-captcha.com', 'key' => '63def0a149a147e0d13e409cc8318fc3'],
        ['host' => 'rucaptcha.com', 'key' => 'd167c71a9278312f184f17caa4e71050'],
    ];
    private $minscore = 0.9;
    private $captcha_threads = 1;

    private $names = [
                           'VIN' => ['vin', 'VIN', 'VIN'],
                           'PIN' => ['pin', 'PIN', 'PIN'],
                           'Номер кузова' => ['body', 'Номер кузова', 'Номер кузова'],
                           'Номер шасси (рамы)' => ['chassis', 'Номер шасси (рамы)', 'Номер шасси (рамы)'],
                           'Описание транспортного средства' => ['description', 'Описание', 'Описание'],
                           'Описание иного имущества' => ['description', 'Описание', 'Описание'],
                           'ID' => ['id', 'Идентификатор', 'Идентификатор'],

                           'Фамилия' => ['lastname', 'Фамилия', 'Фамилия'],
                           'Имя' => ['firstname', 'Имя', 'Имя'],
                           'Отчество' => ['middlename', 'Отчество', 'Отчество'],
                           'Дата рождения' => ['birth', 'Дата рождения', 'Дата рождения'],
                           'Документ, удостоверяющий личность' => ['doc', 'Удостоверение личности', 'Удостоверение личности'],
                           'Адрес фактического места жительства в Российской Федерации' => ['region', 'Регион', 'Регион'],
                           'Полное наименование' => ['orgname', 'Наименование организации', 'Наименование организации'],
                           'Полное наименование (латинскими буквами)' => ['foreignorgname', 'Наименование иностранной организации', 'Наименование иностранной организации'],
                           'ИНН' => ['inn', 'ИНН', 'ИНН'],
                           'ОГРН' => ['ogrn', 'ОГРН', 'ОГРН'],
                           'Регистрационный номер юридического лица в стране его регистрации' => ['regnumber', 'Регистрационный номер юридического лица', 'Регистрационный номер юридического лица'],
                           'Место нахождения' => ['region', 'Регион', 'Регион'],
                           'Адрес для направления корреспонденции' => ['country', 'Страна', 'Страна'],

                           'Наименование' => ['doc_name', 'Наименование документа', 'Наименование документа'],
                           'Дата договора' => ['doc_date', 'Дата документа', 'Дата документа'],
                           'Номер договора' => ['doc_num', 'Номер документа', 'Номер документа'],
                           'Срок исполнения обязательства, обеспеченного залогом движимого имущества' => ['doc_enddate', 'Срок действия', 'Срок действия'],
    ];

    public function getName()
    {
        return 'reestr-zalogov';
    }

    public function getTitle($checktype = '')
    {
        $title = [
            '' => 'Поиск в реестре залогов федеральной нотариальной палаты',
            'reestrzalogov_person' => 'Реестр залогов ФНП - поиск заложенного имущества физлица',
            'reestrzalogov_org' => 'Реестр залогов ФНП - поиск заложенного имущества организации',
            'reestrzalogov_auto' => 'Реестр залогов ФНП - проверка автомобиля',
        ];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск в реестре залогов федеральной нотариальной палаты';
    }

    public function getSessionData($sourceid = 60, $nocaptcha = 0)
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

        $result = $mysqli->query('UPDATE isphere.session s SET request_id='.$reqId." WHERE sessionstatusid=2 AND sourceid=$sourceid AND (captcha='' OR unix_timestamp(now())-unix_timestamp(captchatime)<110) ORDER BY lasttime limit 1");
        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sourceid=$sourceid AND request_id=".$reqId.' ORDER BY lasttime limit 1');

        if ($result && 0 == $result->num_rows && $nocaptcha) {
            $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,'' captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid IN (1,2,3,4,5) AND sourceid=$sourceid ORDER BY lasttime limit 1");
        } else {
            $nocaptcha = false;
        }

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
                $sessionData->nocaptcha = $nocaptcha || !$row->captcha;

                $mysqli->query('UPDATE isphere.session SET '.($sessionData->nocaptcha ? '' : 'sessionstatusid=3,endtime=now(),')."lasttime=now(),statuscode='used',used=ifnull(used,0)+1,request_id=NULL WHERE id=".$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = \substr($initData['checktype'], \strpos($initData['checktype'], '_') + 1);

        if (('person' == $checktype) && (!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date']) || !\strtotime($initData['date']))) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (ФИО, дата рождения)');

            return false;
        }

        if (('org' == $checktype) && (!isset($initData['inn']) || 10 != \strlen($initData['inn']))) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (ИНН организации)');

            return false;
        }

        if (('auto' == $checktype) && !isset($initData['vin']) && !isset($initData['bodynum']) && !isset($initData['chassis'])) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (VIN, номер кузова или номер шасси)');

            return false;
        }

        if (('auto' == $checktype) && isset($initData['vin']) && !\preg_match('/[A-HJ-NPR-Z0-9]{17}/i', $initData['vin'])) {
            $rContext->setFinished();
            $rContext->setError('VIN должен состоять из 17 латинских букв или цифр кроме I,O,Q');

            return false;
        }

        if (isset($initData['last_name']) && isset($initData['first_name']) && \preg_match("/[^А-Яа-яЁё\s\-\.]/ui", $initData['last_name'].' '.$initData['first_name'].(isset($initData['patronymic']) ? ' '.$initData['patronymic'] : ''))) {
            $rContext->setFinished();
            $rContext->setError('Имя может содержать только русские буквы');

            return false;
        }
        /*
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
                return false;
        */
        // //////////////////////////////////////////////////////////////////////////////////////////////////

        if (!isset($swapData['num'])) {
            $swapData['num'] = 1;
            $rContext->setSwapData($swapData);
        }

        (!isset($swapData['iteration'])) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
        if (!isset($swapData['session'])) {
            //            unset($swapData['captcha_id'.$swapData['num']]);
            //            unset($swapData['captcha_token']);
            $swapData['session'] = $this->getSessionData(60/* ,$swapData['iteration']>30 */);
            $rContext->setSwapData($swapData);
            if (!$swapData['session']) {
                if (isset($swapData['iteration']) && ($swapData['iteration'] >= 10)) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                } else {
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(3);
                }

                return false;
            }
        }

        if (!isset($swapData['captcha_token'])) {
            $token = neuro_token('v3', $this->googlekey, isset($swapData['row']) ? 'extract_actual_notification' : 'search_notary');
            if (\strlen($token) > 30) {
                $swapData['captcha_token'] = $token;
                $swapData['captcha_service'] = 'queue';
                $swapData['captcha_id'] = 0;
                echo 'Queue token ('.(isset($swapData['row']) ? 'extract_actual_notification' : 'search_notary').'): '.\substr($token, 0, 5).'...'.\substr($token, \strlen($token) - 5, 5)."\n";
            } else {
                echo 'Queue token not ready ('.(isset($swapData['row']) ? 'extract_actual_notification' : 'search_notary').")\n";
            }
        }

        if (!isset($swapData['captcha_token']) && !isset($swapData['captcha_id'.$swapData['num']])) {
            $swapData['captcha_session'] = $this->getSessionData(isset($swapData['row']) ? 59 : 5);
            if ($swapData['captcha_session'] && $swapData['captcha_session']->code) {
                $token = $swapData['captcha_session']->code;
                $swapData['captcha_token'] = $token;
                unset($swapData['captcha_id']);
                echo 'Daemon token ('.(isset($swapData['row']) ? 'extract_actual_notification' : 'search_notary').'): '.\substr($token, 0, 5).'...'.\substr($token, \strlen($token) - 5, 5)."\n";
            } else {
                echo 'Daemon token not ready ('.(isset($swapData['row']) ? 'extract_actual_notification' : 'search_notary').")\n";
            }
        }

        $rContext->setSwapData($swapData);

        $ch = $rContext->getCurlHandler();

        $site = 'https://reestr-zalogov.ru';
        $page = $site.'/search/index';

        if (!isset($swapData['captcha_token'])) {
            if (!isset($swapData['captcha_id'.$swapData['num']])) {
                $swapData['captcha_service'.$swapData['num']] = (int) (($swapData['iteration'] - 1) / 2) % \count($this->captcha_service);
                $rContext->setSwapData($swapData);
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']) {
                    $params = [
                        'key' => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'],
                        'method' => 'userrecaptcha',
                        'googlekey' => $this->googlekey,
                        'pageurl' => $page,
                        'version' => 'v3',
                        'action' => isset($swapData['row']) ? 'extract_actual_notification' : 'search_notary',
                        'min_score' => $this->minscore,
                    ];
                    /*
                                        if ($swapData['session']->proxy) {
                                            $params['proxytype'] = 'http';
                                            $params['proxy'] = ($swapData['session']->proxy_auth ? $swapData['session']->proxy_auth.'@' : '').$swapData['session']->proxy;
                                        }
                    */
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'].'/in.php?'.\http_build_query($params);
                } else {
                    $params = [
                        'clientKey' => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'],
                        'task' => [
                            'type' => 'RecaptchaV3TaskProxyless',
//                            "type" => "NoCaptchaTask",
                            'websiteURL' => $page,
                            'websiteKey' => $this->googlekey,
                            'minScore' => $this->minscore,
                            'pageAction' => isset($swapData['row']) ? 'extract_actual_notification' : 'search_notary',
/*
                            "proxyType" => "http",
                            "proxyAddress" => "8.8.8.8",
                            "proxyPort" => 8080,
                            "proxyLogin" => "proxyLoginHere",
                            "proxyPassword" => "proxyPasswordHere",
                            "userAgent" => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36",
*/
                        ],
                    ];
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'].'/createTask';
                    \curl_setopt($ch, \CURLOPT_POST, true);
                    \curl_setopt($ch, \CURLOPT_POSTFIELDS, \json_encode($params, \JSON_UNESCAPED_UNICODE));
                }
            } else {
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']) {
                    $params = [
                        'key' => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'],
                        'action' => 'get',
                        'id' => $swapData['captcha_id'.$swapData['num']],
                    ];
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'].'/res.php?'.\http_build_query($params);
                } else {
                    $params = [
                        'clientKey' => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'],
                        'taskId' => $swapData['captcha_id'.$swapData['num']],
                    ];
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'].'/getTaskResult';
                    \curl_setopt($ch, \CURLOPT_POST, true);
                    \curl_setopt($ch, \CURLOPT_POSTFIELDS, \json_encode($params, \JSON_UNESCAPED_UNICODE));
                    \curl_setopt($ch, \CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json;charset=UTF-8',
                        'Accept: application/json;charset=UTF-8']);
                }
            }
            \curl_setopt($ch, \CURLOPT_URL, $url);
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 2);
        //            echo "$url\n";
        //            var_dump($params);
        //            echo "\n";
        } else {
            $params = false;
            if (isset($swapData['row'])) {
                $url = $site.$swapData['data'][$swapData['row']]['link'].'?token='.$swapData['captcha_token'];
            } else {
                //            } elseif (!isset($swapData['key'])) {
                //                $url = $site.'/api/search/cacheRequest?token='.$swapData['captcha_token'];
                $url = $site.'/api/search/notary?token='.$swapData['captcha_token'];
                $params = [
                    'mode' => 'allChanges',
//                    'mode' => 'onlyActual',
                    'filter' => [],
                    'limit' => 100,
                    'offset' => 0,
                ];
                if ('auto' == $checktype) {
                    $params['filter']['property']['vehicleProperty'] = [
                        'vin' => isset($initData['vin']) ? $initData['vin'] : '',
                        'bodyNum' => !isset($initData['vin']) && isset($initData['bodynum']) ? $initData['bodynum'] : '',
                        'chassis' => !isset($initData['vin']) && !isset($initData['bodyNum']) && isset($initData['chassis']) ? $initData['chassis'] : '',
                    ];
                } elseif ('org' == $checktype) {
                    $params['filter']['pledgor']['russianOrganization'] = [
                        'name' => '-',
                        'ogrn' => '',
                        'inn' => $initData['inn'],
                    ];
                } else {
                    $params['filter']['pledgor']['privatePerson'] = [
                        'firstName' => $initData['first_name'],
                        'lastName' => $initData['last_name'],
                        'middleName' => isset($initData['patronymic']) ? $initData['patronymic'] : '',
                        'birthday' => isset($initData['date']) ? \date('d.m.Y', \strtotime($initData['date'])) : '',
                    ];
                }
                /*
                            } else {
                                $url = $site.'/api/search/notary';
                                $params = array(
                                    'limit' => 100,
                                    'offset' => 0,
                                    'key' => $swapData['key'],
                                );
                */
            }

            \curl_setopt($ch, \CURLOPT_URL, $url);
            //            echo "$url\n";
            //            var_dump($params);
            //            echo "\n";

            if ($params) {
                \curl_setopt($ch, \CURLOPT_POST, true);
                \curl_setopt($ch, \CURLOPT_POSTFIELDS, \json_encode($params, \JSON_UNESCAPED_UNICODE));
                \curl_setopt($ch, \CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json;charset=UTF-8',
                    'Origin: '.$site,
                    'Referer: '.$page,
                    'X-Requested-With: XMLHttpRequest']);
            }
            \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
            //            echo "Cookie: ".$swapData['session']->cookies."\n";
            if ($swapData['session']->proxy) {
                \curl_setopt($ch, \CURLOPT_PROXY, $swapData['session']->proxy);
                if ($swapData['session']->proxy_auth) {
                    \curl_setopt($ch, \CURLOPT_PROXYUSERPWD, $swapData['session']->proxy_auth);
                    \curl_setopt($ch, \CURLOPT_PROXYAUTH, \CURLAUTH_ANY);
                }
            }
        }

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(array $params, &$rContext)
    {
        global $mysqli;
        global $serviceurl;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        //        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        $error = ($swapData['iteration'] > 5) ? \curl_error($rContext->getCurlHandler()) : false;
        $content = \curl_multi_getcontent($rContext->getCurlHandler());

        if (!isset($swapData['captcha_token'])) {
            //            echo "$content\n\n";
            $res = \json_decode($content, true);
            if (!isset($swapData['captcha_id'.$swapData['num']])) {
                //                echo "Thread ".$swapData['num']."  Getting new captcha\n";
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']) {
                    if (false !== \strpos($content, 'OK|')) {
                        $swapData['captcha_id'.$swapData['num']] = \substr($content, 3);
                    } elseif ($swapData['iteration'] > 10) {
                        //                        $rContext->setFinished();
                        //                        $rContext->setError('Ошибка получения капчи');
                        \file_put_contents('./logs/rz/'.$initData['checktype'].'_captcha_err_'.\time().'.txt', /* curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n". */ $content."\r\n".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']);
                    }
                } else {
                    if (isset($res['taskId'])) {
                        $swapData['captcha_id'.$swapData['num']] = $res['taskId'];
                    } elseif ($swapData['iteration'] > 10) {
                        //                        $rContext->setFinished();
                        //                        $rContext->setError('Ошибка получения капчи');
                        \file_put_contents('./logs/rz/'.$initData['checktype'].'_captcha_err_'.\time().'.txt', /* curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n". */ $content."\r\n".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']);
                    }
                }
            } else {
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']) {
                    if ('CAPCHA_NOT_READY' == $content) {
                    } else {
                        if (false !== \strpos($content, 'OK|')) {
                            $swapData['captcha_token'] = \substr($content, 3);
                            $swapData['captcha_id'] = $swapData['captcha_id'.$swapData['num']];
                            $swapData['captcha_service'] = $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'];
                        //                            echo "Thread ".$swapData['num']."  Received captcha ID ".$swapData['captcha_id'.$swapData['num']]."\n";
                        } elseif ($swapData['iteration'] > 10) {
                            //                            $rContext->setFinished();
                            //                            $rContext->setError('Ошибка распознавания капчи');
                        }
                        unset($swapData['captcha_id'.$swapData['num']]);
                    }
                } else {
                    if (!$content) {
                    } elseif (isset($res['status']) && 'ready' !== $res['status']) {
                    } else {
                        if (isset($res['solution']['gRecaptchaResponse'])) {
                            $swapData['captcha_token'] = $res['solution']['gRecaptchaResponse'];
                            $swapData['captcha_id'] = $swapData['captcha_id'.$swapData['num']];
                            $swapData['captcha_service'] = $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'];
                        //                            echo "Thread ".$swapData['num']."  Received captcha ID ".$swapData['captcha_id'.$swapData['num']]."\n";
                        } elseif ($swapData['iteration'] > 10) {
                            //                            $rContext->setFinished();
                            //                            $rContext->setError('Ошибка распознавания капчи');
                        }
                        unset($swapData['captcha_id'.$swapData['num']]);
                    }
                }
                --$swapData['iteration'];
            }
            if (++$swapData['num'] > $this->captcha_threads) {
                $swapData['num'] = 1;
            }
            $rContext->setSwapData($swapData);
            if (!isset($swapData['captcha_token']) && isset($swapData['captcha_id'.$swapData['num']])) {
                $rContext->setSleep(1);
            }

            return true;
        }
        /*
                $start = strpos($content,'{');
                $finish = strrpos($content,'}');
                if ($start!==false && $finish!==false) {
                    $content = substr($content,$start,$finish-$start+1);
                }
        */
        if (isset($swapData['row']) && '%PDF' == \substr($content, 0, 4)) {
            $filename = $swapData['data'][$swapData['row']]['id'].'_'.\time();
            \file_put_contents("./logs/rz/$filename.pdf", $content);

            $descriptorspec = [
//                0 => ['pipe', 'r'], //stdin
                1 => ['pipe', 'w'], // stdout
                2 => ['pipe', 'w'], // stderr
            ];
            $process = \proc_open("pdftohtml -xml ./logs/rz/$filename.pdf ./logs/rz/$filename.xml", $descriptorspec, $pipes);
            if (\is_resource($process)) {
                //                fclose($pipes[0]);
                // Читаем xml из stdout
                $out = \stream_get_contents($pipes[1]);
                \fclose($pipes[1]);
                // Читаем ошибки из stderr
                $err = \stream_get_contents($pipes[2]);
                \fclose($pipes[2]);

                $exitCode = \proc_close($process);
                $xml = \simplexml_load_file("./logs/rz/$filename.xml");
                if ($xml) {
                    $field_top = 0;
                    $last_top = 0;
                    $last_left = 0;
                    foreach ($xml->page as $page) {
                        foreach ($page->text as $text) {
                            $top = (int) ($page['number'] - 1) * 10000 + (int) $text['top'];
                            $left = (int) $text['left'];
                            $text = \trim(isset($text->b) ? $text->b : $text);
                            if ($top - $last_top < 20 && $left == $last_left) {
                                $t[$field_top][$left] .= ' '.$text;
                            } else {
                                $t[$top][$left] = $text;
                                $field_top = $top;
                            }
                            $last_left = $left;
                            $last_top = $top;
                        }
                    }
                    \ksort($t);
                    $data = [];
                    $d = 0;
                    $i = 0;
                    $field = false;
                    foreach ($t as $top => $row) {
                        foreach ($row as $left => $text) {
                            if ($top < 140 && $text) {
                                $data['title'][] = $text;
                            } elseif (85 == $left && $text && \preg_match("/^([\d]+)\.[\d]*\s(.*?)$/", $text, $matches)) {
                                if ($d == (int) $matches[1]) {
                                    if (\count($data[$d][$i]) > 1) {
                                        ++$i;
                                    }
                                } else {
                                    $i = 0;
                                    $d = (int) $matches[1];
                                }
                                $data[$d][$i] = ['type' => $matches[2]];
                                $field = false;
                            } elseif ($left < 100 && ($d < 4) && (int) $text && $field) {
                                $data[$d][$i + 1] = ['type' => $data[$d][$i]['type']];
                                ++$i;
                                $field = false;
                            } elseif ($left > 90 && $left < 200) {
                                $field = $text;
                            } elseif ($left > 200 && $field && $text) {
                                $data[$d][$i][$field] = (isset($data[$d][$i][$field]) ? $data[$d][$i][$field].' ' : '').$text;
                            }
                        }
                    }
                    $swapData['data'][$swapData['row']]['pdf'] = $serviceurl."logs/rz/$filename.pdf";
                    $swapData['data'][$swapData['row']]['pdfdata'] = $data;
                }
            }

            if (++$swapData['row'] >= \count($swapData['data'])) {
                $resultData = new ResultDataList();
                $i = 0;
                $obj = 0;
                $vin = false;
                foreach ($swapData['data'] as $item) {
                    $data = [];
                    if (isset($item['pdfdata']['title'][0])) {
                        $item['referenceTitle'] = $item['pdfdata']['title'][0];
                        $data['referenceTitle'] = new ResultDataField('string', 'referenceTitle', $item['referenceTitle'], 'Тип уведомления', 'Тип уведомления');
                    }
                    if (isset($item['id'])) {
                        $item['referenceNumber'] = $item['id'];
                        $data['referenceNumber'] = new ResultDataField('string', 'referenceNumber', $item['id'], 'Регистрационный номер', 'Регистрационный номер');
                    }
                    if (isset($item['regDate'])) {
                        $data['registerDate'] = new ResultDataField('string', 'registerDate', $item['regDate'], 'Дата регистрации залога', 'Дата регистрации залога');
                    }
                    if (isset($item['pdfdata']['title'][2]) && \preg_match('/Состояние: (.*?)$/', $item['pdfdata']['title'][2], $matches)) {
                        $item['referenceStatus'] = $matches[1];
                        $data['referenceStatus'] = new ResultDataField('string', 'referenceStatus', $item['referenceStatus'], 'Статус', 'Статус');
                    }

                    if (isset($item['pdfdata'][4][0])) {
                        foreach ($item['pdfdata'][4][0] as $key => $value) {
                            if (isset($this->names[$key])) {
                                $field = $this->names[$key];
                                $data[$field[0]] = new ResultDataField(isset($field[3]) ? $field[3] : 'string', $field[0], $value, $field[1], $field[2]);
                            }
                        }
                    }

                    $change = [
                        'CREATION' => ['name' => 'start', 'title' => 'Возникновение'],
                        'CHANGE' => ['name' => 'change', 'title' => 'Изменение'],
                        'EXCLUSION' => ['name' => 'end', 'title' => 'Исключение'],
                        'JUDICIAL_ACT_EXCLUSION' => ['name' => 'end', 'title' => 'Исключение по суду'],
                    ];
                    if (isset($item['history'])) {
                        foreach ($item['history'] as $i => $history) {
                            if (isset($change[$history['type']]) && isset($history['regDate'])) {
                                $field = $change[$history['type']]['name'].'time';
                                if (!isset($data[$field])) {
                                    $data[$field.$i] = new ResultDataField('string', $field, $history['regDate'], $change[$history['type']]['title'], $change[$history['type']]['title']);
                                }
                            }
                        }
                    }

                    if (isset($item['pdf'])) {
                        $data['pdf'] = new ResultDataField('url', 'PDF', $item['pdf'], 'PDF', 'PDF');
                    }
                    $data['type'] = new ResultDataField('string', 'Type', 'reference', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);

                    $rows = 0;
                    if (isset($item['pdfdata'][2])) {
                        foreach ($item['pdfdata'][2] as $row) {
                            $rowdata = [];
                            if (false !== \strpos($row['type'], 'Сведения')) {
                                $row['type'] = 'Скрыт';
                                $rowdata['pledgors_type'] = new ResultDataField('string', 'pledgors_type', 'hidden', 'Тип залогодателя (код)', 'Тип залогодателя (код)');
                                $rowdata['pledgors_hidden'] = new ResultDataField('string', 'pledgors_hidden', 'Да', 'Залогодатель скрыт', 'Залогодатель скрыт');
                            } elseif (isset($row['Фамилия']) && isset($row['Имя'])) {
                                $rowdata['pledgors_type'] = new ResultDataField('string', 'pledgors_type', 'person', 'Тип залогодателя (код)', 'Тип залогодателя (код)');
                                $rowdata['pledgors_name'] = new ResultDataField('string', 'pledgors_name', $row['Фамилия'].' '.$row['Имя'].(isset($row['Отчество']) ? ' '.$row['Отчество'] : ''), 'Залогодатель', 'Залогодатель');
                            } elseif (isset($row['Полное наименование'])) {
                                $rowdata['pledgors_type'] = new ResultDataField('string', 'pledgors_type', 'org', 'Тип залогодателя (код)', 'Тип залогодателя (код)');
                                $rowdata['pledgors_name'] = new ResultDataField('string', 'pledgors_name', $row['Полное наименование'], 'Залогодатель', 'Залогодатель');
                            }
                            $rowdata['pledgors_typetext'] = new ResultDataField('string', 'pledgors_typetext', $row['type'], 'Тип залогодателя', 'Тип залогодателя');
                            foreach ($row as $key => $value) {
                                if ($value) {
                                    if (isset($this->names[$key])) {
                                        $field = $this->names[$key];
                                        if (isset($rowdata['pledgors_'.$field[0]])) {
                                            $rowdata['pledgors_'.$field[0]] = new ResultDataField(isset($field[3]) ? $field[3] : 'string', 'pledgors_'.$field[0], \trim($rowdata['pledgors_'.$field[0]]->getValue().' '.$value), $field[1].' залогодателя', $field[2].' залогодателя');
                                        } else {
                                            $rowdata['pledgors_'.$field[0]] = new ResultDataField(isset($field[3]) ? $field[3] : 'string', 'pledgors_'.$field[0], \trim($value), $field[1].' залогодателя', $field[2].' залогодателя');
                                        }
                                        //                                } elseif ($key!='type') {
                                        //                                    file_put_contents('./logs/fields/rz_'.time().'_'.$key.'.txt', $value);
                                    }
                                }
                            }
                            if (isset($item['referenceNumber'])) {
                                $rowdata['referenceNumber'] = new ResultDataField('string', 'referenceNumber', $item['referenceNumber'], 'Регистрационный номер уведомления', 'Регистрационный номер уведомления');
                            }
                            //                            if (isset($item['referenceStatus']))
                            //                                $rowdata['pledgorsStatus'] = new ResultDataField('string', 'pledgorsStatus', $item['referenceStatus'], 'Статус', 'Статус');
                            $rowdata['type'] = new ResultDataField('string', 'Type', 'pledgors', 'Тип записи', 'Тип записи');
                            $resultData->addResult($rowdata);
                            ++$rows;
                        }
                    }
                    //                    $data['pledgorsAmount'] = new ResultDataField('string', 'pledgorsAmount', $rows, 'Всего залогодателей', 'Всего залогодателей');

                    $rows = 0;
                    if (isset($item['pdfdata'][3])) {
                        foreach ($item['pdfdata'][3] as $row) {
                            $rowdata = [];
                            if (false !== \strpos($row['type'], 'Сведения')) {
                                $row['type'] = 'Скрыт';
                                $rowdata['pledgees_type'] = new ResultDataField('string', 'pledgees_type', 'hidden', 'Тип залогодержателя (код)', 'Тип залогодержателя (код)');
                                $rowdata['pledgees_hidden'] = new ResultDataField('string', 'pledgees_hidden', 'Да', 'Залогодержатель скрыт', 'Залогодержатель скрыт');
                            } elseif (isset($row['Фамилия']) && isset($row['Имя'])) {
                                $rowdata['pledgees_type'] = new ResultDataField('string', 'pledgees_type', 'person', 'Тип залогодержателя (код)', 'Тип залогодержателя (код)');
                                $rowdata['pledgees_name'] = new ResultDataField('string', 'pledgees_name', $row['Фамилия'].' '.$row['Имя'].(isset($row['Отчество']) ? ' '.$row['Отчество'] : ''), 'Залогодержатель', 'Залогодержатель');
                            } elseif (isset($row['Полное наименование'])) {
                                $rowdata['pledgees_type'] = new ResultDataField('string', 'pledgees_type', 'org', 'Тип залогодержателя (код)', 'Тип залогодержателя (код)');
                                $rowdata['pledgees_name'] = new ResultDataField('string', 'pledgees_name', $row['Полное наименование'], 'Залогодержатель', 'Залогодержатель');
                            }
                            $rowdata['pledgees_typetext'] = new ResultDataField('string', 'pledgees_typetext', $row['type'], 'Тип залогодержателя', 'Тип залогодержателя');
                            foreach ($row as $key => $value) {
                                if ($value) {
                                    if (isset($this->names[$key])) {
                                        $field = $this->names[$key];
                                        if (isset($rowdata['pledgees_'.$field[0]])) {
                                            $rowdata['pledgees_'.$field[0]] = new ResultDataField(isset($field[3]) ? $field[3] : 'string', 'pledgees_'.$field[0], \trim($rowdata['pledgees_'.$field[0]]->getValue().' '.$value), $field[1].' залогодержателя', $field[2].' залогодержателя');
                                        } else {
                                            $rowdata['pledgees_'.$field[0]] = new ResultDataField(isset($field[3]) ? $field[3] : 'string', 'pledgees_'.$field[0], $value, $field[1].' залогодержателя', $field[2].' залогодержателя');
                                        }
                                        //                                } elseif ($key!='type') {
                                        //                                    file_put_contents('./logs/fields/rz_'.time().'_'.$key.'.txt', $value);
                                    }
                                }
                            }
                            if (isset($item['referenceNumber'])) {
                                $rowdata['referenceNumber'] = new ResultDataField('string', 'referenceNumber', $item['referenceNumber'], 'Регистрационный номер уведомления', 'Регистрационный номер уведомления');
                            }
                            //                            if (isset($item['referenceStatus']))
                            //                                $rowdata['pledgeesStatus'] = new ResultDataField('string', 'pledgeesStatus', $item['referenceStatus'], 'Статус', 'Статус');
                            $rowdata['type'] = new ResultDataField('string', 'Type', 'pledgees', 'Тип записи', 'Тип записи');
                            $resultData->addResult($rowdata);
                            ++$rows;
                        }
                    }
                    //                    $data['pledgeesAmount'] = new ResultDataField('string', 'pledgeesAmount', $rows, 'Всего залогодержателей', 'Всего залогодержателей');

                    $rows = 0;
                    if (isset($item['pdfdata'][1])) {
                        foreach ($item['pdfdata'][1] as $row) {
                            if (\count($row) > 1 && // isset($item['referenceNumber']) && ($item['referenceStatus']=='Актуальное') &&
                              (!isset($initData['vin']) || (isset($row['VIN']) && $row['VIN'] == $initData['vin']) || (isset($row['ID']) && $row['ID'] == $initData['vin']))) {
                                //                            echo $initData['vin']." ".$row['VIN']."\n";
                                $rowdata = [];
                                $rowdata['properties_type'] = new ResultDataField('string', 'properties_type', 'Транспортное средство' == $row['type'] ? 'auto' : 'other', 'Тип имущества (код)', 'Тип имущества (код)');
                                $rowdata['properties_typetext'] = new ResultDataField('string', 'properties_typetext', $row['type'], 'Тип имущества', 'Тип имущества');
                                foreach ($row as $key => $value) {
                                    if ($value) {
                                        $value = \iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', \strtr($value, ['
' => ''])));
                                        if (isset($this->names[$key])) {
                                            $field = $this->names[$key];
                                            if (isset($rowdata['properties_'.$field[0]])) {
                                                $rowdata['properties_'.$field[0]] = new ResultDataField(isset($field[3]) ? $field[3] : 'string', 'properties_'.$field[0], \trim($rowdata['properties_'.$field[0]]->getValue().' '.$value), $field[1], $field[2]);
                                            } else {
                                                $rowdata['properties_'.$field[0]] = new ResultDataField(isset($field[3]) ? $field[3] : 'string', 'properties_'.$field[0], $value, $field[1], $field[2]);
                                            }
                                        } elseif ('type' != $key) {
                                            //                                    $counter++;
                                            //                                    $data['other'.$counter] = new ResultDataField('string', 'other'.$counter, $text, $title, $title);
                                            \file_put_contents('./logs/fields/rz_properties_'.\time().'_'.$key.'.txt', $value);
                                        }
                                    }
                                }
                                if (isset($item['referenceStatus'])) {
                                    $rowdata['properties_status'] = new ResultDataField('string', 'properties_status', $item['referenceStatus'], 'Статус залога имущества', 'Статус залога имущества');
                                }
                                if (isset($item['referenceNumber'])) {
                                    $rowdata['referenceNumber'] = new ResultDataField('string', 'referenceNumber', $item['referenceNumber'], 'Регистрационный номер уведомления', 'Регистрационный номер уведомления');
                                }
                                $rowdata['type'] = new ResultDataField('string', 'Type', 'properties', 'Тип записи', 'Тип записи');
                                $resultData->addResult($rowdata);
                                ++$rows;
                            }
                        }
                    }

                    if (isset($initData['vin']) && (0 == $rows)) {
                        $rowdata = [];
                        $rowdata['properties_type'] = new ResultDataField('string', 'properties_type', 'auto', 'Тип имущества (код)', 'Тип имущества (код)');
                        $rowdata['properties_typetext'] = new ResultDataField('string', 'properties_typetext', 'Транспортное средство', 'Тип имущества', 'Тип имущества');
                        $rowdata['properties_vin'] = new ResultDataField('string', 'properties_vin', $initData['vin'], 'VIN-код', 'VIN-код');
                        $rowdata['properties_status'] = new ResultDataField('string', 'properties_status', 'Имущество исключено', 'Статус залога имущества', 'Статус залога имущества');
                        if (isset($item['referenceNumber'])) {
                            $rowdata['referenceNumber'] = new ResultDataField('string', 'referenceNumber', $item['referenceNumber'], 'Регистрационный номер уведомления', 'Регистрационный номер уведомления');
                        }
                        $rowdata['type'] = new ResultDataField('string', 'Type', 'properties', 'Тип записи', 'Тип записи');
                        $resultData->addResult($rowdata);
                        ++$rows;
                    }
                    //                    $data['propertiesAmount'] = new ResultDataField('string', 'propertiesAmount', $rows, 'Всего объектов залога', 'Всего объектов залога');
                    /*
                                        if (isset($item['properties'])) {
                                            foreach($item['properties'] as $i => $prop) {
                                                if (isset($prop['vehicleProperty'])) {
                                                    if (isset($prop['vehicleProperty']['vin']) && $prop['vehicleProperty']['vin']!=$vin) {
                                                        $data['properties_'.$i.'_vin'] = new ResultDataField('string', 'properties_vin', $vin=$prop['vehicleProperty']['vin'], 'VIN', 'VIN');
                                                        $obj++;
                                                    }
                                                }
                                            }
                                            $data['objects'] = new ResultDataField('string', 'objects', $obj, 'Всего объектов залога', 'Всего объектов залога');
                                        }
                                        if (isset($item['pledgors'])) {
                                            foreach($item['pledgors'] as $i => $pledgor) {
                                                if (isset($pledgee['publicationDisclaimer']) && $pledgee['publicationDisclaimer']) {
                                                    $data['pledgors_'.$i.'_hidden'] = new ResultDataField('string', 'pledgors_hidden', 'Да', 'Залогодатель скрыт', 'Залогодатель скрыт');
                                                }
                                                if (isset($pledgor['organization'])) {
                                                    $data['pledgors_'.$i.'_type'] = new ResultDataField('string', 'pledgors_type', 'org', 'Тип залогодателя', 'Тип залогодателя');
                                                    $data['pledgors_'.$i.'_name'] = new ResultDataField('string', 'pledgors_name', $pledgor['organization'], 'Залогодатель', 'Залогодатель');
                                                }
                                                if (isset($pledgor['privatePerson'])) {
                                                    $data['pledgors_'.$i.'_type'] = new ResultDataField('string', 'pledgors_type', 'person', 'Тип залогодателя', 'Тип залогодателя');
                                                    if (isset($pledgor['privatePerson']['name'])) {
                                                        $data['pledgors_'.$i.'_name'] = new ResultDataField('string', 'pledgors_name', $pledgor['privatePerson']['name'], 'Залогодатель', 'Залогодатель');
                                                    }
                                                    if (isset($pledgor['privatePerson']['birthday'])) {
                                                        $data['pledgors_'.$i.'_birth'] = new ResultDataField('string', 'pledgors_birth', date('d.m.Y',strtotime(substr($pledgor['privatePerson']['birthday'],0,10))), 'Дата рождения залогодателя', 'Дата рождения залогодателя');
                                                    }
                                                }
                                            }
                                        }
                                        if (isset($item['pledgees'])) {
                                            foreach($item['pledgees'] as $i => $pledgee) {
                                                if (isset($pledgee['publicationDisclaimer']) && $pledgee['publicationDisclaimer']) {
                                                    $data['pledgees_'.$i.'_hidden'] = new ResultDataField('string', 'pledgees_hidden', 'Да', 'Залогодержатель скрыт', 'Залогодержатель скрыт');
                                                }
                                                if (isset($pledgee['organization'])) {
                                                    $data['pledgees_'.$i.'_type'] = new ResultDataField('string', 'pledgees_type', 'org', 'Тип залогодержателя', 'Тип залогодержателя');
                                                    if (isset($pledgee['organization']['name'])) {
                                                        $data['pledgees_'.$i.'_name'] = new ResultDataField('string', 'pledgees_name', $pledgee['organization']['name'], 'Залогодержатель', 'Залогодержатель');
                                                    }
                                                }
                                                if (isset($pledgee['privatePerson'])) {
                                                    $data['pledgees_'.$i.'_type'] = new ResultDataField('string', 'pledgees_type', 'person', 'Тип залогодержателя', 'Тип залогодержателя');
                                                    if (isset($pledgee['privatePerson']['name'])) {
                                                        $data['pledgees_'.$i.'_name'] = new ResultDataField('string', 'pledgees_name', $pledgee['privatePerson']['name'], 'Залогодержатель', 'Залогодержатель');
                                                    }
                                                    if (isset($pledgee['privatePerson']['birthday'])) {
                                                        $data['pledgees_'.$i.'_birth'] = new ResultDataField('string', 'pledgees_birth', date('d.m.Y',strtotime(substr($pledgee['privatePerson']['birthday'],0,10))), 'Дата рождения залогодержателя', 'Дата рождения залогодержателя');
                                                    }
                                                }
                                            }
                                        }
                    */
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
            }

            if (isset($swapData['captcha_id']) && isset($swapData['captcha_token']) && isset($swapData['captcha_service'])) {
                $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),59,3,'success','".$swapData['captcha_token']."','".$swapData['captcha_service']."','".$swapData['captcha_id']."')");
                //                echo "Captcha ID ".$swapData['captcha_id']." from ".$swapData['captcha_service']." reported as good with result $res\n";
            }

            if (isset($swapData['captcha_session'])) {
                $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['captcha_session']->id);
                unset($swapData['captcha_session']);
            }
            unset($swapData['captcha_id']);
            unset($swapData['captcha_token']);
            --$swapData['iteration'];
            $rContext->setSwapData($swapData);
            $rContext->setSleep(1);

            return true;
        } elseif (isset($swapData['row']) && \preg_match('/Доступ запрещен/', $content)) {
            \file_put_contents('./logs/rz/'.$initData['checktype'].'_denied_'./* (isset($swapData['key'])?'':'key_'). */ \time().'.txt', $content);
            if (isset($swapData['captcha_id']) && isset($swapData['captcha_token']) && isset($swapData['captcha_service'])) {
                $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),59,4,'forbidden','".$swapData['captcha_token']."','".$swapData['captcha_service']."','".$swapData['captcha_id']."')");
                //                    echo "Captcha ID ".$swapData['captcha_id']." from ".$swapData['captcha_service']." reported as bad with result $res\n";
            }

            if (isset($swapData['captcha_session'])) {
                $mysqli->query("UPDATE isphere.session SET sessionstatusid=4,statuscode='forbidden' WHERE statuscode='used' AND id=".$swapData['captcha_session']->id);
                unset($swapData['captcha_session']);
            }
            unset($swapData['captcha_id']);
            unset($swapData['captcha_token']);
            //                unset($swapData['key']);
            $rContext->setSwapData($swapData);
            $rContext->setSleep(1);

            return true;
        }

        $res = \json_decode($content, true);
        if (!empty(\trim($content))) {
            \file_put_contents('./logs/rz/'.$initData['checktype'].'_'./* (isset($swapData['key'])?'':'key_'). */ \time().'.txt', $content);
        }
        /*
                if (!isset($swapData['key']) && is_array($res) && isset($res['key'])) {
                    $swapData['key'] = $res['key'];
                    $rContext->setSwapData($swapData);
                    return true;
                } else
        */
        if (\is_array($res) && isset($res['data']) && \is_array($res['data'])) {
            if (0 == \count($res['data'])) {
                $resultData = new ResultDataList();
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
            } else {
                $swapData['data'] = $res['data'];
                $swapData['row'] = 0;
            }

            if (isset($swapData['captcha_id']) && isset($swapData['captcha_token']) && isset($swapData['captcha_service'])) {
                $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),5,3,'success','".$swapData['captcha_token']."','".$swapData['captcha_service']."','".$swapData['captcha_id']."')");
                //                echo "Captcha ID ".$swapData['captcha_id']." from ".$swapData['captcha_service']." reported as good with result $res\n";
            }

            if (isset($swapData['captcha_session'])) {
                $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['captcha_session']->id);
                unset($swapData['captcha_session']);
            }

            unset($swapData['captcha_id']);
            unset($swapData['captcha_token']);
            $rContext->setSwapData($swapData);
            $rContext->setSleep(1);

            return true;
        } elseif (\is_array($res) && \array_key_exists('message', $res) && $res['message']) {
            if (\strpos($res['message'], 'token') || \strpos($res['message'], 'капча')) {
                if (isset($swapData['captcha_id']) && isset($swapData['captcha_token']) && isset($swapData['captcha_service'])) {
                    $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),5,4,'invalidcaptcha','".$swapData['captcha_token']."','".$swapData['captcha_service']."','".$swapData['captcha_id']."')");
                    //                    echo "Captcha ID ".$swapData['captcha_id']." from ".$swapData['captcha_service']." reported as bad with result $res\n";
                }

                if (isset($swapData['captcha_session'])) {
                    $mysqli->query("UPDATE isphere.session SET sessionstatusid=4,statuscode='invalidcaptcha' WHERE statuscode='used' AND id=".$swapData['captcha_session']->id);
                    unset($swapData['captcha_session']);
                }
                unset($swapData['captcha_id']);
                unset($swapData['captcha_token']);
                //                unset($swapData['key']);
                $rContext->setSwapData($swapData);
            } else {
                \file_put_contents('./logs/rz/'.$initData['checktype'].'_err_'.\time().'.txt', $content);
                $error = \trim($res['message']);
                $mysqli->query('UPDATE isphere.session SET '.($swapData['session']->nocaptcha ? '' : 'sessionstatusid=3,endtime=now(),')."success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
            }
        } elseif (\preg_match('/Доступ запрещен/', $content)) {
            \file_put_contents('./logs/rz/'.$initData['checktype'].'_denied_'./* (isset($swapData['key'])?'':'key_'). */ \time().'.txt', $content);
            /*
                            $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 hour),sessionstatusid=6,statuscode='forbidden' WHERE id=" . $swapData['session']->id);
                            unset($swapData['session']);
                            unset($swapData['captcha_token']);
                            $rContext->setSwapData($swapData);
                            return true;
            */
            if (isset($swapData['captcha_id']) && isset($swapData['captcha_token']) && isset($swapData['captcha_service'])) {
                $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),5,4,'forbidden','".$swapData['captcha_token']."','".$swapData['captcha_service']."','".$swapData['captcha_id']."')");
                //                    echo "Captcha ID ".$swapData['captcha_id']." from ".$swapData['captcha_service']." reported as bad with result $res\n";
            }

            if (isset($swapData['captcha_session'])) {
                $mysqli->query("UPDATE isphere.session SET sessionstatusid=4,statuscode='forbidden' WHERE statuscode='used' AND id=".$swapData['captcha_session']->id);
                unset($swapData['captcha_session']);
            }
            unset($swapData['captcha_id']);
            unset($swapData['captcha_token']);
            //                unset($swapData['key']);
            $rContext->setSwapData($swapData);
        } elseif ((\is_array($res) && \array_key_exists('error', $res) && 500 == $res['error']) || \preg_match('/временно недоступен/', $content) || \preg_match('/Server Error/', $content)) {
            if ($content) {
                \file_put_contents('./logs/rz/'.$initData['checktype'].'_err_'.\time().'.txt', $content);
            }
            $error = 'Сервис временно недоступен';
            //            if (!$swapData['session']->nocaptcha)
            $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,endtime=now(),statuscode='unavailable' WHERE statuscode='used' AND id=".$swapData['session']->id);
            unset($swapData['session']);
        } elseif (\preg_match('/Ошибка сервиса/', $content)) {
            if ($content) {
                \file_put_contents('./logs/rz/'.$initData['checktype'].'_err_'.\time().'.txt', $content);
            }
            if ($swapData['iteration'] > 5) {
                $error = 'Внутренняя ошибка источника';
            }
            //            if (!$swapData['session']->nocaptcha)
            $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,endtime=now(),statuscode='error' WHERE statuscode='used' AND id=".$swapData['session']->id);
            unset($swapData['session']);
        } else {
            if ($content) {
                \file_put_contents('./logs/rz/'.$initData['checktype'].'_err_'.\time().'.txt', $content);
            }
            if ($content) {
                //                if (!$swapData['session']->nocaptcha)
                $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,endtime=now(),statuscode='invalidanswer' WHERE statuscode='used' AND id=".$swapData['session']->id);
                $error = 'Некорректный ответ сервиса';
            } else {
                //                if (!$swapData['session']->nocaptcha)
                $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,endtime=now(),statuscode='empty' WHERE statuscode='used' AND id=".$swapData['session']->id);
            }
            unset($swapData['session']);
        }
        $rContext->setSwapData($swapData);

        if ($error || $swapData['iteration'] > 20) {
            $rContext->setFinished();
            $rContext->setError($error ?: 'Превышено количество попыток получения ответа');
        }

        $rContext->setSleep(1);

        return false;
    }
}

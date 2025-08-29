<?php

class RZPlugin_new implements PluginInterface
{
    private $googlekey = '6LdKJhMaAAAAAIfeHC6FZc-UVfzDQpiOjaJUWoxr';
    private $captcha_service = [
//        array('host' => 'api.capmonster.cloud', 'key' => 'afb26cbb248d650ea8b8d88822984242'),
        ['host' => 'api.anti-captcha.com', 'key' => '63def0a149a147e0d13e409cc8318fc3'],
        ['host' => 'rucaptcha.com', 'key' => 'd167c71a9278312f184f17caa4e71050'],
    ];
    private $minscore = 0.9;
    private $captcha_threads = 1;

    public function getName()
    {
        return 'reestr-zalogov';
    }

    public function getTitle($checktype = '')
    {
        $title = [
            '' => 'Поиск в реестре залогов федеральной нотариальной палаты',
            'rz_person' => 'Реестр залогов ФНП - поиск заложенного имущества физлица',
            'rz_org' => 'Реестр залогов ФНП - поиск заложенного имущества организации',
            'rz_auto' => 'Реестр залогов ФНП - проверка автомобиля',
        ];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск в реестре залогов федеральной нотариальной палаты';
    }

    public function getSessionData($nocaptcha)
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=5 AND unix_timestamp(now())-unix_timestamp(captchatime)<110 ORDER BY lasttime limit 1");

        if ($result && 0 == $result->num_rows && $nocaptcha) {
            $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,'' captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid IN (3,4,5) AND sourceid=5 ORDER BY lasttime limit 1");
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
                $sessionData->nocaptcha = $nocaptcha;

                $mysqli->query('UPDATE isphere.session SET '.($nocaptcha ? '' : "sessionstatusid=3,statuscode='used',endtime=now(),").'lasttime=now(),used=ifnull(used,0)+1 WHERE id='.$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = \substr($initData['checktype'], 3);

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
            unset($swapData['captcha']);
            //            unset($swapData['captcha_id'.$swapData['num']]);
            //            unset($swapData['captcha_token']);
            $swapData['session'] = $this->getSessionData($swapData['iteration'] > 30);
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

        if (!isset($swapData['captcha_token']) && $swapData['session']->code) {
            $swapData['captcha_token'] = $swapData['session']->code;
            $swapData['session']->code = '';
        }
        $rContext->setSwapData($swapData);

        $ch = $rContext->getCurlHandler();

        $site = 'https://reestr-zalogov.ru';
        $page = $site.'/search/index';

        if (!isset($swapData['captcha_token'])) {
            if (!isset($swapData['captcha_id'.$swapData['num']])) {
                $swapData['captcha_service'.$swapData['num']] = (int) (($swapData['iteration'] - 1) / 2) % \count($this->captcha_service);
                $rContext->setSwapData($swapData);
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'] || true) {
                    $params = [
                        'key' => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'],
                        'method' => 'userrecaptcha',
                        'googlekey' => $this->googlekey,
                        'pageurl' => $page,
                        'version' => 'v3',
                        'action' => 'search_notary',
                        'min_score' => $this->minscore,
                    ];
                    if ('1' == \substr($this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'], 0, 1)) {
                        $params['cmbrowsertype'] = 'Chrome';
                    }
                    /*
                                        if ($swapData['session']->proxy) {
                                            $params['proxytype'] = 'http';
                                            $params['proxy'] = ($swapData['session']->proxy_auth ? $swapData['session']->proxy_auth.'@' : '').$swapData['session']->proxy;
                                        }
                    */
                    $url = 'http://'.$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'].'/in.php?'.\http_build_query($params);
                } else {
                    $params = [
                        'clientKey' => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'],
                        'task' => [
                            'type' => 'RecaptchaV3TaskProxyless',
//                            "type" => "NoCaptchaTask",
                            'websiteURL' => $page,
                            'websiteKey' => $this->googlekey,
                            'minScore' => $this->minscore,
                            'pageAction' => 'search_notary',
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
                    $url = 'http://'.$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'].'/createTask';
                    \curl_setopt($ch, \CURLOPT_POST, true);
                    \curl_setopt($ch, \CURLOPT_POSTFIELDS, \json_encode($params, \JSON_UNESCAPED_UNICODE));
                }
            } else {
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'] || true) {
                    $params = [
                        'key' => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'],
                        'action' => 'get',
                        'id' => $swapData['captcha_id'.$swapData['num']],
                    ];
                    $url = 'http://'.$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'].'/res.php?'.\http_build_query($params);
                } else {
                    $params = [
                        'clientKey' => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'],
                        'taskId' => $swapData['captcha_id'.$swapData['num']],
                    ];
                    $url = 'http://'.$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'].'/getTaskResult';
                    \curl_setopt($ch, \CURLOPT_POST, true);
                    \curl_setopt($ch, \CURLOPT_POSTFIELDS, \json_encode($params, \JSON_UNESCAPED_UNICODE));
                }
            }
            \curl_setopt($ch, \CURLOPT_URL, $url);
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 2);
            echo "$url\n";
        //            var_dump($params);
        //            echo "\n";
        } else {
            //            if (!isset($swapData['key'])) {
            //                $url = $site.'/api/search/cacheRequest?token='.$swapData['captcha_token'];
            $url = $site.'/api/search/notary?token='.$swapData['captcha_token'];
            $params = [
//                    'mode' => 'allChanges',
                'mode' => 'onlyActual',
                'filter' => [],
                'limit' => 10,
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
                        }
            */
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
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        //        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        $error = ($swapData['iteration'] > 5) ? \curl_error($rContext->getCurlHandler()) : false;
        $content = \curl_multi_getcontent($rContext->getCurlHandler());

        if (!isset($swapData['captcha_token'])) {
            echo "$content\n";
            $res = \json_decode($content, true);
            if (!isset($swapData['captcha_id'.$swapData['num']])) {
                //                echo "Thread ".$swapData['num']."  Getting new captcha\n";
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'] || true) {
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
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'] || true) {
                    if ('CAPCHA_NOT_READY' == $content) {
                    } else {
                        if (false !== \strpos($content, 'OK|')) {
                            $swapData['captcha_token'] = \substr($content, 3);
                            $swapData['captcha_id'] = $swapData['captcha_id'.$swapData['num']];
                            $swapData['captcha_service'] = $swapData['captcha_service'.$swapData['num']];
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
                            $swapData['captcha_service'] = $swapData['captcha_service'.$swapData['num']];
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
        $res = \json_decode($content, true);
        if (!empty(\trim($content))) {
            \file_put_contents('./logs/rz/rz_'./* (isset($swapData['key'])?'':'key_'). */ \time().'.txt', $content);
        }
        /*
                if (!isset($swapData['key']) && is_array($res) && isset($res['key'])) {
                    $swapData['key'] = $res['key'];
                    $rContext->setSwapData($swapData);
                    return true;
                } else
        */
        if (\is_array($res) && isset($res['data'])) {
            $resultData = new ResultDataList();
            if (\is_array($res['data'])) {
                $i = 0;
                $obj = 0;
                $vin = false;
                foreach ($res['data'] as $item) {
                    $data = [];
                    if (isset($item['regDate'])) {
                        $data['registerDate'] = new ResultDataField('string', 'registerDate', $item['regDate'], 'Дата регистрации залога', 'Дата регистрации залога');
                    }
                    if (isset($item['id'])) {
                        $data['referenceNumber'] = new ResultDataField('string', 'referenceNumber', $item['id'], 'Регистрационный номер', 'Регистрационный номер');
                    }
                    if (isset($item['properties'])) {
                        foreach ($item['properties'] as $i => $prop) {
                            if (isset($prop['vehicleProperty'])) {
                                if (isset($prop['vehicleProperty']['vin']) /* && $prop['vehicleProperty']['vin']!=$vin */) {
                                    $data['properties_'.$i.'_vin'] = new ResultDataField('string', 'properties_VIN', $vin = $prop['vehicleProperty']['vin'], 'VIN', 'VIN');
                                    //                                    $obj++;
                                }
                            }
                            if (isset($prop['otherProperty'])) {
                                if (isset($prop['otherProperty']['id']) /* && $prop['otherProperty']['id']!=$vin */) {
                                    $data['properties_'.$i.'_id'] = new ResultDataField('string', 'properties_ID', $vin = $prop['otherProperty']['id'], 'ID', 'ID');
                                    //                                    $obj++;
                                }
                            }
                        }
                        $data['objects'] = new ResultDataField('string', 'objects', /* $obj */ \count($item['properties']), 'Всего объектов залога', 'Всего объектов залога');
                    }
                    if (isset($item['pledgors'])) {
                        foreach ($item['pledgors'] as $i => $pledgor) {
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
                                    $data['pledgors_'.$i.'_birth'] = new ResultDataField('string', 'pledgors_birth', \date('d.m.Y', \strtotime(\substr($pledgor['privatePerson']['birthday'], 0, 10))), 'Дата рождения залогодателя', 'Дата рождения залогодателя');
                                }
                            }
                        }
                    }
                    if (isset($item['pledgees'])) {
                        foreach ($item['pledgees'] as $i => $pledgee) {
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
                                    $data['pledgees_'.$i.'_birth'] = new ResultDataField('string', 'pledgees_birth', \date('d.m.Y', \strtotime(\substr($pledgee['privatePerson']['birthday'], 0, 10))), 'Дата рождения залогодержателя', 'Дата рождения залогодержателя');
                                }
                            }
                        }
                    }
                    $change = [
                        'CREATION' => ['name' => 'start', 'title' => 'Возникновение'],
                        'CHANGE' => ['name' => 'change', 'title' => 'Изменение'],
                        'EXCLUSION' => ['name' => 'end', 'title' => 'Исключение'],
                    ];
                    if (isset($item['history'])) {
                        foreach ($item['history'] as $i => $history) {
                            if (isset($change[$history['type']]) && isset($history['regDate'])) {
                                $field = $change[$history['type']]['name'].'time';
                                if (!isset($data[$field])) {
                                    $data[$field] = new ResultDataField('string', $field, $history['regDate'], $change[$history['type']]['title'], $change[$history['type']]['title']);
                                }
                            }
                        }
                    }
                    $resultData->addResult($data);
                }
            }
            $rContext->setResultData($resultData);
            $rContext->setFinished();

            if (isset($swapData['captcha_id']) && isset($swapData['captcha_token']) && isset($swapData['captcha_service']) /* && $this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com' || true */) {
                /*
                                $params = array(
                                    'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                                    'action' => 'reportgood',
                                    'id' => $swapData['captcha_id'],
                                );
                                $url = "http://".$this->captcha_service[$swapData['captcha_service']]['host']."/res.php?".http_build_query($params);
                                $res = file_get_contents($url);
                */
                $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),5,3,'success','".$swapData['captcha_token']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
                //                echo "Captcha ID ".$swapData['captcha_id']." reported as good with result $res\n";
            }

            if (!$swapData['session']->nocaptcha) {
                $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
            }
            unset($swapData['captcha_id']);
            $rContext->setSwapData($swapData);

            return true;
        } elseif (\is_array($res) && \array_key_exists('message', $res) && $res['message']) {
            if (\strpos($res['message'], 'token') || \strpos($res['message'], 'капча')) {
                if (isset($swapData['captcha_id']) && isset($swapData['captcha_token']) && isset($swapData['captcha_service']) /* && $this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com' || true */) {
                    /*
                                        $params = array(
                                            'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                                            'action' => 'reportbad',
                                            'id' => $swapData['captcha_id'],
                                        );
                                        $url = "http://".$this->captcha_service[$swapData['captcha_service']]['host']."/res.php?".http_build_query($params);
                                        $res = file_get_contents($url);
                    */
                    $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),5,4,'invalidcaptcha','".$swapData['captcha_token']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
                    //                    echo "Captcha ID ".$swapData['captcha_id']." reported as bad with result $res\n";
                }

                if (!$swapData['session']->nocaptcha) {
                    $mysqli->query("UPDATE isphere.session SET sessionstatusid=4,statuscode='invalidcaptcha' WHERE statuscode='used' AND id=".$swapData['session']->id);
                    unset($swapData['session']);
                }
                unset($swapData['captcha_id']);
                unset($swapData['captcha_token']);
                //                unset($swapData['key']);
                $rContext->setSwapData($swapData);
            } else {
                \file_put_contents('./logs/rz/rz_err_'.\time().'.txt', $content);
                $error = \trim($res['message']);
                if (!$swapData['session']->nocaptcha) {
                    $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
                }
            }
        } elseif (\preg_match('/Доступ запрещен/', $content)) {
            \file_put_contents('./logs/rz/'.$initData['checktype'].'_denied_'./* (isset($swapData['key'])?'':'key_'). */ \time().'.txt', $content);
            if (isset($swapData['captcha_id']) && isset($swapData['captcha_token']) && isset($swapData['captcha_service']) /* && $this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com' || true */) {
                /*
                                    $params = array(
                                        'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                                        'action' => 'reportbad',
                                        'id' => $swapData['captcha_id'],
                                    );
                                    $url = "http://".$this->captcha_service[$swapData['captcha_service']]['host']."/res.php?".http_build_query($params);
                //                    $res = file_get_contents($url);
                */
                $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),5,4,'invalidcaptcha','".$swapData['captcha_token']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
                //                    echo "Captcha ID ".$swapData['captcha_id']." reported as bad with result $res\n";
            }

            if (!$swapData['session']->nocaptcha) {
                //                    $mysqli->query("UPDATE isphere.session SET sessionstatusid=4,statuscode='forbidden' WHERE statuscode='used' AND id=" . $swapData['session']->id);
                //                    unset($swapData['session']);
            }
            unset($swapData['captcha_id']);
            unset($swapData['captcha_token']);
            //                unset($swapData['key']);
            $rContext->setSwapData($swapData);
        } elseif ((\is_array($res) && \array_key_exists('error', $res) && 500 == $res['error']) || \preg_match('/временно недоступен/', $content) || \preg_match('/Server Error/', $content)) {
            $error = 'Сервис временно недоступен';
            if (!$swapData['session']->nocaptcha) {
                $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,statuscode='unavailable' WHERE statuscode='used' AND id=".$swapData['session']->id);
            }
            unset($swapData['session']);
        } elseif (\preg_match('/Ошибка сервиса/', $content)) {
            if ($swapData['iteration'] > 5) {
                $error = 'Внутренняя ошибка источника';
            }
            if (!$swapData['session']->nocaptcha) {
                $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,statuscode='error' WHERE statuscode='used' AND id=".$swapData['session']->id);
            }
            unset($swapData['session']);
        } else {
            if ($content) {
                \file_put_contents('./logs/rz/rz_err_'.\time().'.txt', $content);
            }
            if ($content) {
                if (!$swapData['session']->nocaptcha) {
                    $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,statuscode='invalidanswer' WHERE statuscode='used' AND id=".$swapData['session']->id);
                }
                $error = 'Некорректный ответ сервиса';
            } else {
                if (!$swapData['session']->nocaptcha) {
                    $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,statuscode='empty' WHERE statuscode='used' AND id=".$swapData['session']->id);
                }
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

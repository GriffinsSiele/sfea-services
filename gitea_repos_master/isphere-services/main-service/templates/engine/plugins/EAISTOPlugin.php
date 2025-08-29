<?php

class EAISTOPlugin implements PluginInterface
{
    //    private $googlekey = '6LemMrYUAAAAAEgj7AVh1Cy-av2zYJahbgqBYISZ';
    private $googlekey = '6LdeeqIaAAAAAH8skVBDkNiq-pJ5HW008I8GAdCW';
    private $captcha_service = [['host' => 'api.capmonster.cloud', 'key' => 'afb26cbb248d650ea8b8d88822984242'], ['host' => 'rucaptcha.com', 'key' => 'd167c71a9278312f184f17caa4e71050'], ['host' => 'api.anti-captcha.com', 'key' => '63def0a149a147e0d13e409cc8318fc3']];
    //    private $minscore = 0.7;
    private $captcha_threads = 1;
    private $names = [
        'eaisto' => ['Number', 'Номер карты', 'Номер карты'],
        'grz' => ['RegNum', 'Госномер', 'Государственный номер'],
        'vin' => ['VIN', 'VIN', 'VIN'],
        'body' => ['BodyNum', 'Номер кузова', 'Номер кузова'],
        'chassis' => ['ChassisNum', 'Номер шасси', 'Номер шасси'],
        'mark' => ['Mark', 'Марка', 'Марка авто'],
        'model' => ['Model', 'Модель', 'Модель авто'],
        'oto' => [false],
        // array('Operator', 'Оператор', 'Оператор'),
        'dateFrom' => ['StartDate', 'Дата диагностики', 'Дата диагностики'],
        'dateTo' => ['EndDate', 'Действителен до', 'Действителен до'],
    ];

    public function getName()
    {
        return 'ЕАИСТО';
    }

    public function getTitle()
    {
        return 'Поиск диагностической карты';
    }

    public function getSessionData(array $params)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $sessionData = null;
        $result = $mysqli->executeQuery("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM session s WHERE sessionstatusid=2 AND sourceid=28 ORDER BY lasttime limit 1");
        if ($result) {
            $row = $result->fetchAssociative();
            if ($row) {
                $sessionData = new \stdClass();
                $sessionData->id = $row['id'];
                $sessionData->code = $row['captcha'];
                $sessionData->token = $row['token'];
                $sessionData->starttime = $row['starttime'];
                $sessionData->lasttime = $row['lasttime'];
                $sessionData->cookies = $row['cookies'];
                $sessionData->proxyid = $row['proxyid'];
                $sessionData->proxy = $row['proxy'];
                $sessionData->proxy_auth = \strlen($row['proxy_auth']) > 1 ? $row['proxy_auth'] : false;
                $mysqli->executeStatement('UPDATE session SET lasttime=now(),used=ifnull(used,0)+1 WHERE id='.$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        if (!isset($initData['vin']) && !isset($initData['bodynum']) && !isset($initData['regnum'])) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (VIN или номер кузова или госномер)');
            return false;
        }
        $rContext->setFinished();
        $rContext->setError('Сервис временно недоступен');

        return false;
        // //////////////////////////////////////////////////////////////////////////////////////////////////
        if (!isset($swapData['num'])) {
            $swapData['num'] = 1;
            $rContext->setSwapData($swapData);
        }
        !isset($swapData['iteration']) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
        if (!isset($swapData['session'])) {
            unset($swapData['captcha']);
            //            unset($swapData['captcha_id'.$swapData['num']]);
            //            unset($swapData['captcha_token']);
            $swapData['session'] = $this->getSessionData($params, $swapData['iteration'] > 20);
            $rContext->setSwapData($swapData);
            if (!$swapData['session']) {
                if (isset($swapData['iteration']) && $swapData['iteration'] >= 120) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                } else {
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);
                }

                return false;
            }
        }
        if (!isset($swapData['captcha_token']) && $swapData['session']->code) {
            $swapData['captcha_token'] = $swapData['session']->code;
        }
        /*
                if (!isset($swapData['verify_token']) && !isset($swapData['captcha_token']) && !isset($swapData['captcha_id'.$swapData['num']])) {
                    $token = neuro_token('ealsto.info');
                    if (strlen($token)>30) {
                        $swapData['captcha_token'] = $token;
                    }
        //             echo "Neuro token $token\n";
                }
        */
        $rContext->setSwapData($swapData);
        $ch = $rContext->getCurlHandler();
        //        $host = 'https://eaisto.info';
        $host = 'https://ealsto.info';
        $page = $host.'/';
        if (!isset($swapData['captcha_token'])) {
            if (!isset($swapData['captcha_id'.$swapData['num']])) {
                $swapData['captcha_service'] = (int) (($swapData['iteration'] - 1) / 2) % \count($this->captcha_service);
                $rContext->setSwapData($swapData);
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service']]['host']) {
                    $params = ['key' => $this->captcha_service[$swapData['captcha_service']]['key'], 'method' => 'userrecaptcha', 'googlekey' => $this->googlekey, 'pageurl' => $page];
                    /*
                                        if ($swapData['session']->proxy) {
                                            $params['proxytype'] = 'http';
                                            $params['proxy'] = ($swapData['session']->proxy_auth ? $swapData['session']->proxy_auth.'@' : '').$swapData['session']->proxy;
                                        }
                    */
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service']]['host'].'/in.php?'.\http_build_query($params);
                } else {
                    $params = ['clientKey' => $this->captcha_service[$swapData['captcha_service']]['key'], 'task' => [
                        //                            "type" => "RecaptchaV3TaskProxyless",
                        'type' => 'NoCaptchaTaskProxyless',
                        'websiteKey' => $this->googlekey,
                        'websiteURL' => $page,
                    ]];
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service']]['host'].'/createTask';
                    \curl_setopt($ch, \CURLOPT_POST, true);
                    \curl_setopt($ch, \CURLOPT_POSTFIELDS, \json_encode($params, \JSON_UNESCAPED_UNICODE));
                }
            } else {
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service']]['host']) {
                    $params = ['key' => $this->captcha_service[$swapData['captcha_service']]['key'], 'action' => 'get', 'id' => $swapData['captcha_id'.$swapData['num']]];
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service']]['host'].'/res.php?'.\http_build_query($params);
                } else {
                    $params = ['clientKey' => $this->captcha_service[$swapData['captcha_service']]['key'], 'taskId' => $swapData['captcha_id'.$swapData['num']]];
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service']]['host'].'/getTaskResult';
                    \curl_setopt($ch, \CURLOPT_POST, true);
                    \curl_setopt($ch, \CURLOPT_POSTFIELDS, \json_encode($params, \JSON_UNESCAPED_UNICODE));
                }
            }
            \curl_setopt($ch, \CURLOPT_URL, $url);
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 2);
        //            echo "$url\n";
        //            var_dump($params);
        //            echo "\n";
        } else {
            /*
                        if (!isset($swapData['verified'])) {
                            $url = $host.'/verifyCaptcha.php';
                            $post = array(
                                'token' => $swapData['captcha_token'],
                            );
                        } else {
            */
            $url = $page;
            $post = ['_token' => '', 'vin' => isset($initData['vin']) ? $initData['vin'] : '', 'grz' => isset($initData['regnum']) ? $initData['regnum'] : '', 'body' => isset($initData['bodynum']) ? $initData['bodynum'] : '', 'chassis' => '', 'eaisto' => '', 'g-recaptcha-response' => $swapData['captcha_token']];
            //            }
            \curl_setopt($ch, \CURLOPT_POST, true);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, \http_build_query($post));
            \curl_setopt($ch, \CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded; charset=UTF-8', 'Origin: '.$host, 'X-Requested-With: XMLHttpRequest']);
            \curl_setopt($ch, \CURLOPT_REFERER, $page);
            \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
            if ($swapData['session']->proxy) {
                \curl_setopt($ch, \CURLOPT_PROXY, $swapData['session']->proxy);
                if ($swapData['session']->proxy_auth) {
                    \curl_setopt($ch, \CURLOPT_PROXYUSERPWD, $swapData['session']->proxy_auth);
                    \curl_setopt($ch, \CURLOPT_PROXYAUTH, \CURLAUTH_ANY);
                }
            }
            \curl_setopt($ch, \CURLOPT_URL, $url);
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 90);
        }
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(array $params, &$rContext)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        //        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $error = $swapData['iteration'] > 5 ? \curl_error($rContext->getCurlHandler()) : '';
        if (\strpos($error, 'timed out') || \strpos($error, 'connection')) {
            $error = false;
            //                $mysqli->query("UPDATE session SET unlocktime=date_add(now(),interval 10 minute),sessionstatusid=6 WHERE id=" . $swapData['session']->id);
        }
        $content = \curl_multi_getcontent($rContext->getCurlHandler());
        if (!isset($swapData['captcha_token'])) {
            //            echo "$content\n";
            $res = \json_decode($content, true);
            if (!isset($swapData['captcha_id'.$swapData['num']])) {
                //                echo "Thread ".$swapData['num']."  Getting new captcha\n";
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service']]['host']) {
                    if (false !== \strpos($content, 'OK|')) {
                        $swapData['captcha_id'.$swapData['num']] = \substr($content, 3);
                    } elseif ($swapData['iteration'] > 10) {
                        $rContext->setFinished();
                        $rContext->setError('Ошибка получения капчи');
                        \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                            './logs/eaisto/'.$initData['checktype'].'_captcha_err_'.\time().'.txt',
                            /* curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n". */
                            $content."\r\n".$this->captcha_service[$swapData['captcha_service']]['host']
                        );
                    }
                } else {
                    if (isset($res['taskId'])) {
                        $swapData['captcha_id'.$swapData['num']] = $res['taskId'];
                    } elseif ($swapData['iteration'] > 10) {
                        $rContext->setFinished();
                        $rContext->setError('Ошибка получения капчи');
                        \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                            './logs/eaisto/'.$initData['checktype'].'_captcha_err_'.\time().'.txt',
                            /* curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n". */
                            $content."\r\n".$this->captcha_service[$swapData['captcha_service']]['host']
                        );
                    }
                }
            } else {
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service']]['host']) {
                    if ('CAPCHA_NOT_READY' == $content) {
                    } else {
                        if (false !== \strpos($content, 'OK|')) {
                            $swapData['captcha_token'] = \substr($content, 3);
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
                $rContext->setSleep(5);
            } else {
                $rContext->setSleep(1);
            }

            return true;
        }
        /*
                if (!isset($swapData['verified'])) {
                    if ($content) \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/eaisto/verify_'.time().'.txt',$content);
                    if (strpos($content,'true')) {
                        $swapData['verified'] = true;
                        $rContext->setSwapData($swapData);
                        return true;
                    } else {
                        unset($swapData['captcha_token']);
                    }
                } else {
        */
        if ($content) {
            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/eaisto/eaisto_'.\time().'.txt', $content);
        }
        $res = \json_decode($content, true);
        if (\is_array($res) && !isset($res['message'])) {
            $resultData = new ResultDataList();
            foreach ($res as $elem) {
                if (\is_array($elem)) {
                    $data = [];
                    foreach ($elem as $title => $text) {
                        if (isset($this->names[$title])) {
                            $field = $this->names[$title];
                            if ($text && $field[0]) {
                                $data[$field[0]] = new ResultDataField(isset($field[3]) ? $field[3] : 'string', $field[0], $text, $field[1], $field[2]);
                            }
                        } else {
                            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/fields/eaisto_'.\time().'_'.\strtr($title, ['/' => '_']), $title."\n".$text);
                        }
                    }
                    if (\count($data)) {
                        $resultData->addResult($data);
                    }
                }
            }
            $rContext->setResultData($resultData);
            $rContext->setFinished();

            return true;
        } elseif (\is_array($res) && isset($res['message'])) {
            if (isset($res['errors']['g-recaptcha-response'])) {
                //                    if (isset($swapData['session']))
                //                        $mysqli->query("UPDATE session SET endtime=now(),sessionstatusid=4,statuscode='invalidcaptcha' WHERE id=" . $swapData['session']->id);
                //                    unset($swapData['session']);
                unset($swapData['captcha_token']);
            //                    unset($swapData['verified']);
            } else {
                $error = $res['message'];
            }
        } elseif (empty($content)) {
            //                $mysqli->query("UPDATE session SET unlocktime=date_add(now(),interval 10 minute),sessionstatusid=6,statuscode='empty' WHERE id=" . $swapData['session']->id);
            $mysqli->executeStatement("UPDATE session SET endtime=now(),sessionstatusid=3,statuscode='empty' WHERE id=".$swapData['session']->id);
            unset($swapData['session']);
            if ($swapData['iteration'] >= 5) {
                $error = 'Сервис не отвечает';
            }
        } elseif ($swapData['iteration'] >= 3) {
            $error = 'Некорректный ответ сервиса';
            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/eaisto/eaisto_err_'.\time().'.txt', $content);
        }
        //        }
        if ($error || $swapData['iteration'] > 10) {
            $rContext->setError($error ?: 'Превышено количество попыток получения ответа');
            $rContext->setFinished();

            return false;
        }
        $rContext->setSwapData($swapData);
        $rContext->setSleep(1);

        return true;
    }
}

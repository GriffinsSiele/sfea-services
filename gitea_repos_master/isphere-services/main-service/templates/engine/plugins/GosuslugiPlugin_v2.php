<?php

class GosuslugiPlugin_v2 implements PluginInterface
{
    private $googlekey = '6LdDOgoTAAAAAP7P7kgDGKtblbOlYMgHzqE9UqJs';
    private $captcha_service = [
//        array('host' => 'bak2.i-sphere.ru:8081', 'key' => 'afb26cbb248d650ea8b8d88822984242'),
//        array('host' => 'api.capmonster.cloud', 'key' => 'afb26cbb248d650ea8b8d88822984242'),
        ['host' => 'api.anti-captcha.com', 'key' => '63def0a149a147e0d13e409cc8318fc3'],
        ['host' => 'rucaptcha.com', 'key' => 'd167c71a9278312f184f17caa4e71050'],
    ];
    private $captcha_threads = 1;

    public function getName($checktype = '')
    {
        $name = [
            '' => 'Gosuslugi',
            'gosuslugi_phone' => 'GosuslugiPhone',
            'gosuslugi_email' => 'GosuslugiEmail',
            'gosuslugi_passport' => 'GosuslugiPassport',
            'gosuslugi_inn' => 'GosuslugiINN',
            'gosuslugi_snils' => 'GosuslugiSNILS',
        ];

        return isset($name[$checktype]) ? $name[$checktype] : $name[''];
        //        return 'Gosuslugi';
    }

    public function getTitle($checktype = '')
    {
        $title = [
            '' => 'Поиск учетной записи в сервисе Госуслуги',
            'gosuslugi_phone' => 'Госуслуги - проверка телефона на наличие пользователя',
            'gosuslugi_email' => 'Госуслуги - проверка email на наличие пользователя',
            'gosuslugi_passport' => 'Госуслуги - проверка паспорта',
            'gosuslugi_inn' => 'Госуслуги - проверка ИНН',
            'gosuslugi_snils' => 'Госуслуги - проверка СНИЛС',
        ];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск учетной записи в сервисе Госуслуги';
    }

    public function getSessionData($nocaptcha)
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

        $mysqli->query('UPDATE isphere.session s SET request_id='.$reqId." WHERE sessionstatusid=2 AND sourceid=48 AND cookies>'' AND captcha>'' AND statuscode<>'used' AND unix_timestamp(now())-unix_timestamp(starttime)<110 ORDER BY lasttime limit 1");
        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,captcha_service,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sourceid=48 AND request_id=".$reqId.' ORDER BY lasttime limit 1');

        if ($result && 0 == $result->num_rows && $nocaptcha) {
            $mysqli->query('UPDATE isphere.session s SET request_id='.$reqId." WHERE sessionstatusid IN (2,7) AND sourceid=48 AND cookies>'' AND (statuscode<>'used' OR unix_timestamp(now())-unix_timestamp(lasttime)>600) ORDER BY lasttime limit 1");
            $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,'' captcha,'' captcha_service,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sourceid=48 AND request_id=".$reqId.' ORDER BY lasttime limit 1');
            $nocaptcha = true;
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
                $sessionData->nocaptcha = ('' == $row->captcha); // $nocaptcha;

                //                $mysqli->query("UPDATE isphere.session SET ".($nocaptcha?"":"sessionstatusid=3,statuscode='used',endtime=now(),")."lasttime=now(),used=ifnull(used,0)+1,request_id=NULL WHERE id=".$sessionData->id);
                $mysqli->query('UPDATE isphere.session SET '.($nocaptcha ? "captcha='',captcha_id=NULL," : '')."lasttime=now(),used=ifnull(used,0)+1,used_ext=ifnull(used_ext,0)+1,sessionstatusid=2,statuscode='used',request_id=NULL WHERE id=".$sessionData->id);
                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1,used_ext=ifnull(used_ext,0)+1,sessionstatusid=2,statuscode='used',request_id=NULL WHERE statuscode<>'used' AND id=".$sessionData->id);

                //                echo "Session {$row->id} {$row->captcha_service}\n";
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = \substr($initData['checktype'], 10);

        if ('passport' == $checktype && (!isset($initData['passport_series']) || !isset($initData['passport_number']))) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (серия и номер паспорта)');

            return false;
        }

        if ('passport' == $checktype && (!\preg_match("/^\d{4}$/", $initData['passport_series']) || !\preg_match("/^\d{6}$/", $initData['passport_number'])/* || !intval($initData['passport_series']) */)) {
            $rContext->setFinished();
            $rContext->setError('Некорректные значения серии или номера паспорта');

            return false;
        }

        if ('inn' == $checktype && !isset($initData['inn'])) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (ИНН)');

            return false;
        }

        if ('email' == $checktype && !isset($initData['email'])) {
            $rContext->setFinished();
            //            $rContext->setError('Не указаны параметры для поиска (email)');

            return false;
        }

        if ('phone' == $checktype && !isset($initData['phone'])) {
            $rContext->setFinished();
            //            $rContext->setError('Не указаны параметры для поиска (телефон)');

            return false;
        }

        if (isset($initData['phone'])) {
            //            if (strlen($initData['phone'])==10)
            //                $initData['phone']='7'.$initData['phone'];
            //            if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
            //                $initData['phone']='7'.substr($initData['phone'],1);
        }
        if ('phone' == $checktype && '7' != \substr($initData['phone'], 0, 1)) {
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

        $ch = $rContext->getCurlHandler();

        if (!isset($swapData['mode'])) {
            $swapData['mode'] = $checktype;
        }
        if (!isset($swapData['num'])) {
            $swapData['num'] = 1;
            $rContext->setSwapData($swapData);
        }

        (!isset($swapData['iteration'])) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
        if (!isset($swapData['session'])) {
            unset($swapData['captcha']);
            //            unset($swapData['captcha_id'.$swapData['num']]);
            unset($swapData['captcha_token']);
            unset($swapData['verify_token']);
            unset($swapData['request_id']);
            $swapData['session'] = $this->getSessionData($swapData['iteration'] > 0);
            $rContext->setSwapData($swapData);
            if (!$swapData['session']) {
                if (isset($swapData['iteration']) && ($swapData['iteration'] >= 120)) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                } else {
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);
                }

                return false;
            } else {
                //                $swapData['posted'] = true;
                //                $swapData['captcha'] = true;
            }
        }

        if (!isset($swapData['captcha_token']) && $swapData['session']->code) {
            $swapData['captcha_token'] = $swapData['session']->code;
        }

        if (!isset($swapData['verify_token']) && !isset($swapData['captcha_token'])) {
            $token = neuro_token('gosuslugi.ru');
            if (\strlen($token) > 30) {
                $swapData['captcha_token'] = $token;
            }
            //             echo "Neuro token $token\n";
        }

        $rContext->setSwapData($swapData);

        $site = 'https://esia.gosuslugi.ru';
        $page = $site.'/login/recovery';

        if (!isset($swapData['verify_token']) && !isset($swapData['captcha_token'])) {
            if (!isset($swapData['captcha_id'.$swapData['num']])) {
                $swapData['captcha_service'.$swapData['num']] = (int) (($swapData['iteration'] - 1) / 10) % \count($this->captcha_service);
                //                echo $swapData['iteration'].": New captcha from ".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']."\n";
                $rContext->setSwapData($swapData);
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']) {
                    $params = [
                        'key' => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'],
                        'method' => 'userrecaptcha',
                        'googlekey' => $this->googlekey,
                        'pageurl' => $site.'/captcha/?redirect_uri=https://esia.gosuslugi.ru/recovery/service/captcha/result',
                    ];
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
                            'type' => 'NoCaptchaTaskProxyless',
                            'websiteKey' => $this->googlekey,
                            'websiteURL' => $site.'/captcha/?redirect_uri=https://esia.gosuslugi.ru/recovery/service/captcha/result',
                        ],
                    ];
                    $url = 'http://'.$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'].'/createTask';
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
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 5);
        //            echo $swapData['iteration'].": $url\n";
        //            var_dump($params);
        //            echo "\n";
        } else {
            $cookies = str_cookies($swapData['session']->cookies);
            if (!isset($swapData['verify_token'])) {
                $url = $site.'/captcha/api/public/v1/verify';
                $post = [
                    'captchaType' => 'recaptcha',
                    'captchaResponse' => $swapData['captcha_token'], // .($swapData['iteration']<3?'0':''),
                ];
                $referer = $page;
            } else {
                $post = false;
                $params = [];
                if ('phone' == $swapData['mode']) {
                    $params['mbt'] = '+'.$initData['phone'];
                } elseif ('email' == $swapData['mode']) {
                    $params['eml'] = $initData['email'];
                } elseif ('passport' == $swapData['mode']) {
                    $params['serNum'] = $initData['passport_series'].$initData['passport_number'];
                } elseif ('inn' == $swapData['mode']) {
                    $params['inn'] = $initData['inn'];
                } else {
                }
                if (isset($swapData['request_id'])) {
                    $params['requestId'] = $swapData['request_id'];
                } elseif (isset($swapData['verify_token'])) {
                    $params['verifyToken'] = $swapData['verify_token'];
                }
                $url = $site.'/esia-rs/api/public/v2/recovery/find?'.\http_build_query($params);
                $referer = $page;
                //                echo "$url\n";
            }
            $header[] = 'Accept: */*';
            $header[] = 'Origin: '.$site;
            $header[] = 'Referer: '.$referer;
            if (\is_array($post)) {
                \curl_setopt($ch, \CURLOPT_POST, true);
                \curl_setopt($ch, \CURLOPT_POSTFIELDS, \json_encode($post, \JSON_UNESCAPED_UNICODE));

                $header[] = 'Content-Type: application/json; charset=UTF-8';
            }
            \curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);
            //            curl_setopt($ch, CURLOPT_HEADER, true);
            //            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
            \curl_setopt($ch, \CURLOPT_COOKIEFILE, '');
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 10);
            //            echo $swapData['iteration'].": $url\n";
            //            var_dump($params);
            //            echo "\n";
        }

        \curl_setopt($ch, \CURLOPT_URL, $url);
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(array $params, &$rContext)
    {
        global $mysqli;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        //        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        $checktype = \substr($initData['checktype'], 10);

        $error = ($swapData['iteration'] > 5) ? \curl_error($rContext->getCurlHandler()) : false;
        $content = \curl_multi_getcontent($rContext->getCurlHandler());

        if (!isset($swapData['verify_token']) && !isset($swapData['captcha_token'])) {
            //            echo "$content\n\n";
            $res = \json_decode($content, true);
            if (!isset($swapData['captcha_id'.$swapData['num']])) {
                //                echo "Thread ".$swapData['num']."  Getting new captcha\n";
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']) {
                    if (false !== \strpos($content, 'OK|')) {
                        $swapData['captcha_id'.$swapData['num']] = \substr($content, 3);
                    } elseif ($swapData['iteration'] > 10) {
                        $rContext->setFinished();
                        $rContext->setError('Ошибка получения капчи');
                        \file_put_contents('./logs/gosuslugi/'.$initData['checktype'].'_captcha_err_'.\time().'.txt', /* curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n". */ $content."\r\n".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']);
                    }
                } else {
                    if (isset($res['taskId']) && $res['taskId']) {
                        $swapData['captcha_id'.$swapData['num']] = $res['taskId'];
                    } elseif ($swapData['iteration'] > 10) {
                        $rContext->setFinished();
                        $rContext->setError('Ошибка получения капчи');
                        \file_put_contents('./logs/gosuslugi/'.$initData['checktype'].'_captcha_err_'.\time().'.txt', /* curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n". */ $content."\r\n".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']);
                    }
                }
            } else {
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']) {
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
                $rContext->setSleep(5);
            } else {
                $rContext->setSleep(1);
            }

            return true;
        }

        $cookies = str_cookies($swapData['session']->cookies);
        foreach (\curl_getinfo($rContext->getCurlHandler(), \CURLINFO_COOKIELIST) as $cookie) {
            //            print 'Response cookie '.$cookie."\n";
            $arr = \explode('	', $cookie);
            if (!isset($cookies[$arr[5]]) || $cookies[$arr[5]] != $arr[6]) {
                $cookies[$arr[5]] = $arr[6];
                //                print 'New cookie '.$arr[5].' = '.$arr[6]."\n";
            }
        }
        $new_cookies = cookies_str($cookies);
        $swapData['session']->cookies = $new_cookies;
        $rContext->setSwapData($swapData);
        //        file_put_contents('./logs/gosuslugi/gosuslugi_'.time().'.cookies',$new_cookies);
        $mysqli->query("UPDATE isphere.session SET cookies='$new_cookies' WHERE id=".$swapData['session']->id);
        //        $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->id);

        $start = \strpos($content, '{');
        $jsoncontent = \trim(\substr($content, $start, \strlen($content) - $start + 1));
        $res = \json_decode($jsoncontent, true);

        if (!isset($swapData['verify_token'])) {
            //            if (!empty(trim($content))) file_put_contents('./logs/gosuslugi/'.$swapData['mode'].'_verify_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
            if (isset($res['verify_token'])) {
                $swapData['verify_token'] = $res['verify_token'];
                /*
                            } elseif (isset($res['error']) && $res['error']=='invalid captcha') {
                                if (!$swapData['session']->nocaptcha) {
                //                    $mysqli->query("UPDATE isphere.session SET sessionstatusid=4,statuscode='invalidcaptcha',endtime=now() WHERE statuscode='used' AND id=" . $swapData['session']->id);
                                    $mysqli->query("UPDATE isphere.session SET sessionstatusid=4,statuscode='invalidcaptcha',captchaimage='' WHERE statuscode='used' AND id=" . $swapData['session']->id);
                                    unset($swapData['session']);
                                } else {
                //                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
                                    $mysqli->query("UPDATE isphere.session SET sessionstatusid=7,lasttime=now(),success=ifnull(success,0)+1,statuscode='success',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id);
                                    if (isset($swapData['captcha_service']) && isset($swapData['captcha_id']))
                                        $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),48,4,'invalidcaptcha','".$swapData['captcha_token']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
                                }
                                unset($swapData['captcha_token']);
                                unset($swapData['session']);
                */
            } elseif (\strpos($content, 'технические работы')) {
                \file_put_contents('./logs/gosuslugi/'.$swapData['mode'].'_verify_err_'.\time().'.txt', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);
                if ($swapData['iteration'] > 5) {
                    $error = 'Технические работы на Госуслугах';
                }
                //                unset($swapData['captcha_token']);
                unset($swapData['session']);
            } else {
                if (!empty(\trim($content))) {
                    \file_put_contents('./logs/gosuslugi/'.$swapData['mode'].'_verify_err_'.\time().'.txt', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);
                }
                if ($swapData['iteration'] > 5) {
                    $error = 'Некорректный ответ сервиса';
                }
                //                unset($swapData['captcha_token']);
                unset($swapData['session']);
            }
        } elseif (!isset($swapData['request_id'])) {
            //            if (!empty(trim($content))) file_put_contents('./logs/gosuslugi/'.$swapData['mode'].'_find_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
            if (isset($res['message']) && \strpos($res['message'], 'verify.token.is.invalid')) {
                if (!$swapData['session']->nocaptcha) {
                    //                    $mysqli->query("UPDATE isphere.session SET sessionstatusid=4,statuscode='invalidcaptcha',endtime=now() WHERE statuscode='used' AND id=" . $swapData['session']->id);
                    $mysqli->query("UPDATE isphere.session SET sessionstatusid=4,statuscode='invalidcaptcha',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id);
                    unset($swapData['session']);
                } else {
                    //                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
                    $mysqli->query("UPDATE isphere.session SET sessionstatusid=7,lasttime=now(),success=ifnull(success,0)+1,statuscode='success',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id);
                    if (isset($swapData['captcha_service']) && isset($swapData['captcha_id'])) {
                        $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),48,4,'invalidcaptcha','".$swapData['captcha_token']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
                    }
                }
                unset($swapData['verify_token']);
                unset($swapData['captcha_token']);
                unset($swapData['session']);
            } elseif (\is_array($res) && ('phone' == $checktype || 'email' == $checktype || (isset($res['message']) && \strpos($res['message'], 'not.found')))) {
                $resultData = new ResultDataList();
                if (isset($res['requestId'])) {
                    $data = [];
                    if (isset($initData['phone'])) {
                        $data['phone'] = new ResultDataField('string', 'Phone', $initData['phone'], 'Телефон', 'Телефон');
                    }
                    if (isset($initData['email'])) {
                        $data['email'] = new ResultDataField('string', 'Email', $initData['email'], 'E-mail', 'E-mail');
                    }
                    $data['result'] = new ResultDataField('string', 'Result', 'Найден', 'Результат', 'Результат');
                    $data['result_code'] = new ResultDataField('string', 'ResultCode', 'FOUND', 'Код результата', 'Код результата');
                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                if (!$swapData['session']->nocaptcha) {
                    //                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
                    $mysqli->query("UPDATE isphere.session SET sessionstatusid=7,lasttime=now(),success=ifnull(success,0)+1,statuscode='success',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id);
                } else {
                    //                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
                    $mysqli->query("UPDATE isphere.session SET sessionstatusid=7,lasttime=now(),success=ifnull(success,0)+1,statuscode='success',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id);
                    if (isset($swapData['captcha_service']) && isset($swapData['captcha_id'])) {
                        $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),48,3,'success','".$swapData['captcha_token']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
                    }
                }

                return true;
            } elseif (isset($res['requestId'])) {
                $swapData['request_id'] = $res['requestId'];
            } else {
                if (!empty(\trim($content))) {
                    \file_put_contents('./logs/gosuslugi/'.$swapData['mode'].'_find_err_'.\time().'.txt', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);
                }
                if ($swapData['iteration'] > 5) {
                    $error = 'Некорректный ответ сервиса';
                }
            }
        } else {
            //            if (!empty(trim($content))) file_put_contents('./logs/gosuslugi/'.$swapData['mode'].'_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
            if (\is_array($res)) {
                $resultData = new ResultDataList();
                $data = [];
                $notified = [];

                if (isset($res['contactValueMBT'])) {
                    $data['phone'] = new ResultDataField('string', 'Phone', $res['contactValueMBT'], 'Телефон', 'Телефон');
                    $notified[] = 'sms';
                }
                if (isset($res['contactValueEML'])) {
                    $data['email'] = new ResultDataField('string', 'Email', $res['contactValueEML'], 'E-mail', 'E-mail');
                    $notified[] = 'email';
                }
                if (1 == \count($notified)) {
                    $data['notifiedby'] = new ResultDataField('string', 'NotifiedBy', $notified[0], 'Отправлено уведомление', 'Отправлено уведомление');
                }
                $data['result'] = new ResultDataField('string', 'Result', 'Найден', 'Результат', 'Результат');
                $data['result_code'] = new ResultDataField('string', 'ResultCode', 'FOUND', 'Код результата', 'Код результата');
                $resultData->addResult($data);

                $rContext->setResultData($resultData);
                $rContext->setFinished();
                if (!$swapData['session']->nocaptcha) {
                    //                        $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
                    $mysqli->query("UPDATE isphere.session SET sessionstatusid=7,lasttime=now(),success=ifnull(success,0)+1,statuscode='success',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id);
                } else {
                    //                        $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
                    $mysqli->query("UPDATE isphere.session SET sessionstatusid=7,lasttime=now(),success=ifnull(success,0)+1,statuscode='success',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id);
                    if (isset($swapData['captcha_service']) && isset($swapData['captcha_id'])) {
                        $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),48,3,'success','".$swapData['captcha_token']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
                    }
                }

                return true;
            } else {
                if (!empty(\trim($content))) {
                    \file_put_contents('./logs/gosuslugi/'.$swapData['mode'].'_err_'.\time().'.txt', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);
                }
                if ($swapData['iteration'] > 5) {
                    $error = 'Некорректный ответ сервиса';
                }
            }
            /*
                    } else {
                        if (trim($content)) file_put_contents('./logs/gosuslugi/'.$swapData['mode'].'_err_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
                        if (strpos($content,'временно недоступен') || strpos($content,'произошла ошибка') || strpos($content,'повторить операцию') || strpos($content,'Service unavailable')) {
                            if($swapData['iteration']>30)
                               $error = "Внутренняя ошибка источника";
                        } elseif (empty(trim($content))) {
                            if (!$swapData['session']->nocaptcha) {
                                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='empty' WHERE statuscode='used' AND id=".$swapData['session']->id);
                            } else {
                                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='empty' WHERE statuscode='used' AND id=" . $swapData['session']->id);
            //                    if (isset($swapData['captcha_service']) && isset($swapData['captcha_id']))
            //                        $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),48,3,'success','".$swapData['captcha_token']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
                            }
                            if($swapData['iteration']>10) $error = "Сервис не отвечает";
                        } elseif (strpos($content,'Страница не найдена')) {
                            if (!$swapData['session']->nocaptcha) {
                                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='pagenotfound' WHERE statuscode='used' AND id=".$swapData['session']->id);
                            } else {
                                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='pagenotfound' WHERE statuscode='used' AND id=" . $swapData['session']->id);
            //                    if (isset($swapData['captcha_service']) && isset($swapData['captcha_id']))
            //                        $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),48,3,'success','".$swapData['captcha_token']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
                            }
                            unset($swapData['captcha']);
                            unset($swapData['posted']);
                            unset($swapData['verified']);
                            unset($swapData['captcha_token']);
                        } elseif(strpos($content,'error')!==false) {
                            $error = "Сервис временно недоступен";
                        } elseif($swapData['iteration']>5) {
                            $error = "Некорректный ответ сервиса";
                        }
                        unset($swapData['session']);
            */
        }
        $rContext->setSwapData($swapData);

        if ($error || $swapData['iteration'] > 10) {
            $rContext->setFinished();
            $rContext->setError($error ?: 'Превышено количество попыток получения ответа');
        }

        $rContext->setSleep(1);

        return false;
    }
}

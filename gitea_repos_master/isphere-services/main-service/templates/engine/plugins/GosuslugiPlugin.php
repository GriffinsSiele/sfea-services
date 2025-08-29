<?php

class GosuslugiPlugin implements PluginInterface
{
    //    private $googlekey = '6LdDOgoTAAAAAP7P7kgDGKtblbOlYMgHzqE9UqJs';
    private $captcha_service = [
        //        array('host' => 'api.capmonster.cloud', 'key' => 'afb26cbb248d650ea8b8d88822984242'),
        //        array('host' => 'rucaptcha.com', 'key' => 'd167c71a9278312f184f17caa4e71050'),
        ['host' => 'api.anti-captcha.com', 'key' => '63def0a149a147e0d13e409cc8318fc3'],
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

    public function getSessionData(array $params, $nocaptcha)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $reqId = $params['_reqId'];
        $sessionData = null;
        if (0 == \rand(0, 9)) {
            $mysqli->executeStatement(
                "DELETE FROM session WHERE sessionstatusid=2 AND sourceid=48 AND cookies='' ORDER BY lasttime limit 1"
            );
        }
        try {
            $mysqli->executeStatement(
                'UPDATE session s SET request_id='.$reqId." WHERE sessionstatusid=2 AND sourceid=48 AND cookies>'' AND (statuscode<>'used' OR lasttime<from_unixtime(unix_timestamp(now())-600)) AND lasttime<from_unixtime(unix_timestamp(now())-10) AND captcha>'' ORDER BY lasttime limit 1"
            );
        } catch (Exception $e) {
            return $sessionData;
        }
        $result = $mysqli->executeQuery(
            "SELECT id,cookies,starttime,lasttime,captcha,captcha_service,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM session s WHERE sourceid=48 AND request_id=".$reqId.' ORDER BY lasttime limit 1'
        );
        if ($result && 0 == $result->rowCount() && $nocaptcha) {
            $mysqli->executeStatement(
                'UPDATE session s SET request_id='.$reqId." WHERE sessionstatusid IN (2,7) AND sourceid=48 AND cookies>'' AND (statuscode<>'used' OR unix_timestamp(now())-unix_timestamp(lasttime)>600) AND lasttime<from_unixtime(unix_timestamp(now())-10) ORDER BY lasttime limit 1"
            );
            $result = $mysqli->executeQuery(
                "SELECT id,cookies,starttime,lasttime,'' captcha,'' captcha_service,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM session s WHERE sourceid=48 AND request_id=".$reqId.' ORDER BY lasttime limit 1'
            );
            $nocaptcha = true;
        } else {
            $nocaptcha = false;
        }
        if ($result) {
            $row = $result->fetchAssociative();
            if ($row) {
                $sessionData = new \stdClass();
                $sessionData->proxyid = $row['proxyid'];
                $sessionData->proxy = $row['proxy'];
                $sessionData->proxy_auth = \strlen((string) $row['proxy_auth']) > 1 ? $row['proxy_auth'] : false;
                $sessionData->id = $row['id'];
                $sessionData->code = $row['captcha'];
                $sessionData->token = $row['token'];
                $sessionData->starttime = $row['starttime'];
                $sessionData->lasttime = $row['lasttime'];
                $sessionData->cookies = $row['cookies'];
                $sessionData->nocaptcha = '' == $row['captcha'];
                // $nocaptcha;
                //                $mysqli->query("UPDATE session SET ".($nocaptcha?"":"sessionstatusid=3,statuscode='used',endtime=now(),")."lasttime=now(),used=ifnull(used,0)+1,request_id=NULL WHERE id=".$sessionData->id);
                $mysqli->executeStatement(
                    'UPDATE session SET '.($nocaptcha ? "captcha='',captcha_id=NULL," : '')."lasttime=now(),used=ifnull(used,0)+1,sessionstatusid=2,statuscode='used',request_id=NULL WHERE id=".$sessionData->id
                );
                $mysqli->executeStatement(
                    "UPDATE session SET lasttime=now(),used=ifnull(used,0)+1,sessionstatusid=2,statuscode='used',request_id=NULL WHERE statuscode<>'used' AND id=".$sessionData->id
                );
                if (!$row['proxyid']) {
                    //                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM proxy WHERE enabled=1 AND status=1 AND country='ru' AND (rotation>0 OR (SELECT COUNT(*) FROM session WHERE proxyid=proxy.id AND sourceid=48 AND sessionstatusid IN (1,2,6,7))<1) ORDER BY lasttime limit 1");
                    $result = $mysqli->executeQuery(
                        "SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM proxy WHERE enabled=1 AND status=1 AND country='ru' AND rotation>0 ORDER BY lasttime limit 1"
                    );
                    if ($result) {
                        $row = $result->fetchAssociative();
                        if ($row) {
                            $sessionData->proxyid = $row['proxyid'];
                            $sessionData->proxy = $row['proxy'];
                            $sessionData->proxy_auth = \strlen($row['proxy_auth']) > 1 ? $row['proxy_auth'] : false;
                            //                            $mysqli->query("UPDATE proxy SET lasttime=now() WHERE id=".$row['proxyid']);
                            $mysqli->executeStatement(
                                'UPDATE session SET proxyid='.$row['proxyid'].' WHERE id='.$sessionData->id
                            );
                        }
                    }
                }
                //                echo "Session {$row['id']} {$row['captcha_service']}\n";
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
            $rContext->setError('Указаны не все обязательные параметры (серия и номер паспорта)');

            return false;
        }
        if ('passport' == $checktype && (!\preg_match('/^\\d{4}$/', $initData['passport_series']) || !\preg_match(
            '/^\\d{6}$/',
            $initData['passport_number']
        ))) {
            $rContext->setFinished();
            $rContext->setError('Некорректные значения серии или номера паспорта');

            return false;
        }
        if ('inn' == $checktype && !isset($initData['inn'])) {
            $rContext->setFinished();
            $rContext->setError('Указаны не все обязательные параметры (ИНН)');

            return false;
        }
        if ('email' == $checktype && !isset($initData['email'])) {
            $rContext->setFinished();
            $rContext->setError('Не указаны параметры для поиска (email)');

            return false;
        }
        if ('phone' == $checktype && !isset($initData['phone'])) {
            $rContext->setFinished();
            $rContext->setError('Не указаны параметры для поиска (телефон)');

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
            $rContext->setError('Поиск производится только по российским телефонам');

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
        !isset($swapData['iteration']) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
        if (!isset($swapData['session'])) {
            unset($swapData['captcha_session']);
            unset($swapData['captcha_image']);
            unset($swapData['captcha_value']);
            unset($swapData['verify_token']);
            unset($swapData['request_id']);
            $swapData['session'] = $this->getSessionData($params, $swapData['iteration'] > 0);
            $rContext->setSwapData($swapData);
            if (!$swapData['session']) {
                if (isset($swapData['iteration']) && $swapData['iteration'] >= 30) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                } else {
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);
                }

                return false;
            }
            if ($swapData['iteration'] > 10 && \rand(0, 2)) {
                $astro = ['213.108.196.179:10687'];
                $swapData['session']->proxyid = 2;
                $swapData['session']->proxy = $astro[\rand(0, \count($astro) - 1)];
                $swapData['session']->proxy_auth = 'isphere:e6eac1';
            }
        }
        /*
                if (!isset($swapData['captcha_token']) && $swapData['session']->code) {
                    $swapData['captcha_token'] = $swapData['session']->code;
                }
                if (!isset($swapData['verify_token']) && !isset($swapData['captcha_token']) && !isset($swapData['captcha_id'.$swapData['num']])) {
                    $token = neuro_token('gosuslugi.ru');
                    if (strlen($token)>30) {
                        $swapData['captcha_token'] = $token;
                    }
        //             echo "Neuro token $token\n";
                }
        */
        /*
                if (!isset($swapData['captcha_value']) && $swapData['session']->code) {
                    $swapData['captcha_value'] = $swapData['session']->code;
                }
        */
        $rContext->setSwapData($swapData);
        $site = 'https://esia.gosuslugi.ru';
        $page = $site.'/login/recovery';
        if (isset($swapData['captcha_session']) && !isset($swapData['captcha_image'])) {
            $url = $site.'/captcha/api/public/v2/image';
            $header[] = 'Accept: */*';
            $header[] = 'Referer: '.$page;
            $header[] = 'captchaSession: '.$swapData['captcha_session'];
            \curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);
            \curl_setopt($ch, \CURLOPT_URL, $url);
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 10);
            \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
            //            curl_setopt($ch, CURLOPT_HEADER, true);
            if ($swapData['session']->proxy) {
                \curl_setopt(
                    $ch,
                    \CURLOPT_PROXY,
                    $swapData['iteration'] > 100 ? '193.23.50.2:10775' : $swapData['session']->proxy
                );
                if ($swapData['session']->proxy_auth) {
                    \curl_setopt(
                        $ch,
                        \CURLOPT_PROXYUSERPWD,
                        $swapData['iteration'] > 100 ? 'isphere:e6eac1' : $swapData['session']->proxy_auth
                    );
                    \curl_setopt($ch, \CURLOPT_PROXYAUTH, \CURLAUTH_ANY);
                }
            }
        //            echo $swapData['iteration'].": $url\n";
        //            echo "\n";
        } elseif (isset($swapData['captcha_image']) && !isset($swapData['captcha_value'])) {
            if (!isset($swapData['captcha_id'.$swapData['num']])) {
                $swapData['captcha_service'.$swapData['num']] = (int) (($swapData['iteration'] - 1) / 3) % \count(
                    $this->captcha_service
                );
                //                echo $swapData['iteration'].": New captcha from ".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']."\n";
                $rContext->setSwapData($swapData);
                $params = [
                    'key' => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'],
                    'method' => 'base64',
                    'body' => $swapData['captcha_image'],
                    'is_russian' => 1,
                    'min_len' => 5,
                    'max_len' => 7,
                ];
                $url = 'https://'.$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'].'/in.php';
                \curl_setopt($ch, \CURLOPT_POST, true);
                \curl_setopt($ch, \CURLOPT_POSTFIELDS, \http_build_query($params));
            } else {
                $params = [
                    'key' => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'],
                    'action' => 'get',
                    'id' => $swapData['captcha_id'.$swapData['num']],
                ];
                $url = 'https://'.$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'].'/res.php?'.\http_build_query(
                    $params
                );
            }
            \curl_setopt($ch, \CURLOPT_URL, $url);
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 5);
            \curl_setopt($ch, \CURLOPT_PROXY, false);
        //            echo $swapData['iteration'].": $url\n";
        //            var_dump($params);
        //            echo "\n";
        } else {
            $cookies = \App\Utils\Legacy\CookieUtilStatic::str_cookies($swapData['session']->cookies);
            $post = false;
            if (!isset($swapData['captcha_session'])) {
                $url = $site.'/captcha/api/public/v2/type';
            } elseif (!isset($swapData['verify_token'])) {
                $url = $site.'/captcha/api/public/v2/verify';
                $header[] = 'captchaSession: '.$swapData['captcha_session'];
                $post = [
                    //                    'captchaType' => 'recaptcha',
                    //                    'captchaResponse' => $swapData['captcha_token'], //.($swapData['iteration']<3?'0':''),
                    'captchaType' => 'esiacaptcha',
                    'answer' => $swapData['captcha_value'],
                ];
            } else {
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
                //                echo "$url\n";
            }
            $header[] = 'Accept: */*';
            $header[] = 'Origin: '.$site;
            $header[] = 'Referer: '.$page;
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
            \curl_setopt($ch, \CURLOPT_SSL_VERIFYPEER, false);
            if ($swapData['session']->proxy) {
                \curl_setopt($ch, \CURLOPT_PROXY, $swapData['session']->proxy);
                if ($swapData['session']->proxy_auth) {
                    \curl_setopt($ch, \CURLOPT_PROXYUSERPWD, $swapData['session']->proxy_auth);
                    \curl_setopt($ch, \CURLOPT_PROXYAUTH, \CURLAUTH_ANY);
                }
            }
            //            echo $swapData['iteration'].": $url\n";
            //            var_dump($params);
            //            echo "\n";
        }
        //        echo $swapData['iteration'].": $url\n";
        \curl_setopt($ch, \CURLOPT_URL, $url);
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(array $params, &$rContext)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        //        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
        $checktype = \substr($initData['checktype'], 10);
        $error = $swapData['iteration'] > 5 ? \curl_error($rContext->getCurlHandler()) : false;
        $content = \curl_multi_getcontent($rContext->getCurlHandler());
        if (isset($swapData['captcha_image']) && !isset($swapData['captcha_value'])) {
            //            echo "$content\n\n";
            $res = \json_decode($content, true);
            if (!isset($swapData['captcha_id'.$swapData['num']])) {
                //                echo "Thread ".$swapData['num']."  Getting new captcha\n";
                if (false !== \strpos($content, 'OK|')) {
                    $swapData['captcha_id'.$swapData['num']] = \substr($content, 3);
                } elseif ($swapData['iteration'] > 10) {
                    $rContext->setFinished();
                    $rContext->setError('Ошибка получения капчи');
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                        './logs/gosuslugi/'.$initData['checktype'].'_captcha_err_'.\time().'.txt',
                        /* curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n". */
                        $content."\r\n".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']
                    );
                }
            } else {
                if ('CAPCHA_NOT_READY' == $content) {
                } else {
                    if (false !== \strpos($content, 'OK|')) {
                        $swapData['captcha_value'] = \substr($content, 3);
                        $swapData['captcha_id'] = $swapData['captcha_id'.$swapData['num']];
                        $swapData['captcha_service'] = $swapData['captcha_service'.$swapData['num']];
                    //                        echo "Thread ".$swapData['num']."  Received captcha ID ".$swapData['captcha_id'.$swapData['num']]."\n";
                    } elseif ($swapData['iteration'] > 10) {
                        //                        $rContext->setFinished();
                        //                        $rContext->setError('Ошибка распознавания капчи');
                    }
                    unset($swapData['captcha_id'.$swapData['num']]);
                }
                --$swapData['iteration'];
            }
            if (++$swapData['num'] > $this->captcha_threads) {
                $swapData['num'] = 1;
            }
            $rContext->setSwapData($swapData);
            if (!isset($swapData['captcha_value']) && isset($swapData['captcha_id'.$swapData['num']])) {
                $rContext->setSleep(3);
            } else {
                $rContext->setSleep(1);
            }

            return true;
        }
        if (empty(\trim($content))) {
            //            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/gosuslugi/'.$swapData['mode'].'_empty_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
            //            $mysqli->query("UPDATE session SET proxyid=NULL,used=NULL,success=NULL WHERE proxyid<100 AND statuscode='used' AND id=".$swapData['session']->id);
            //            $mysqli->query("UPDATE session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='empty',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id);
            $mysqli->executeStatement(
                'UPDATE session SET proxyid=NULL,unlocktime=date_add(now(),interval '.($swapData['session']->proxyid < 100 ? '30 second' : '5 minute')."),sessionstatusid=6,statuscode='empty' WHERE statuscode='used' AND id=".$swapData['session']->id
            );
            if ($swapData['iteration'] >= 20) {
                $error = 'Сервис не отвечает';
            } else {
                unset($swapData['session']);
            }
            /*
                    } elseif (strlen($content)>30000 && strpos($content,'технические работы')) {
                        \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/gosuslugi/'.$swapData['mode'].'_err_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
                        if ($swapData['iteration']>=10)
                            $error = 'Технические работы на Госуслугах';
                        $mysqli->query("UPDATE session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='techwork',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id);
                        unset($swapData['session']);
            */
        } else {
            $cookies = \App\Utils\Legacy\CookieUtilStatic::str_cookies($swapData['session']->cookies);
            foreach (\curl_getinfo($rContext->getCurlHandler(), \CURLINFO_COOKIELIST) as $cookie) {
                //                print 'Response cookie '.$cookie."\n";
                $arr = \explode('	', $cookie);
                if (!isset($cookies[$arr[5]]) || $cookies[$arr[5]] != $arr[6]) {
                    $cookies[$arr[5]] = $arr[6];
                    //                    print 'New cookie '.$arr[5].' = '.$arr[6]."\n";
                }
            }
            $new_cookies = \App\Utils\Legacy\CookieUtilStatic::cookies_str($cookies);
            $swapData['session']->cookies = $new_cookies;
            $rContext->setSwapData($swapData);
            //            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/gosuslugi/gosuslugi_'.time().'.cookies',$new_cookies);
            $mysqli->executeStatement(
                "UPDATE session SET cookies='{$new_cookies}' WHERE id=".$swapData['session']->id
            );
            //            $mysqli->query("UPDATE session SET success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->id);
        }
        if (isset($swapData['captcha_session']) && !isset($swapData['captcha_image'])) {
            if ('PNG' == \substr($content, 1, 3) && 'END' == \substr($content, \strlen($content) - 7, 3)) {
                //                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/gosuslugi/'.$swapData['mode'].'_captcha_'.time().'.jpg',$content);
                $value = neuro_post($content, 'gosuslugidecode');
                if ($value && 'ERROR' != \substr($value, 0, 5)) {
                    $swapData['captcha_value'] = $value;
                } else {
                    unset($swapData['session']);
                }
                $swapData['captcha_image'] = \base64_encode($content);
                --$swapData['iteration'];
                $rContext->setSleep(1);
            } elseif (isset($swapData['session'])) {
                if (!empty(\trim($content))) {
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                        './logs/gosuslugi/'.$swapData['mode'].'_bad_captcha_'.\time().'.jpg',
                        $content
                    );
                }
                $mysqli->executeStatement(
                    "UPDATE session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='badimage',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id
                );
                unset($swapData['session']);
                $rContext->setSleep(1);
            }
            $rContext->setSwapData($swapData);

            return true;
        }
        $start = \strpos($content, '{');
        $jsoncontent = \trim(\substr($content, $start, \strlen($content) - $start + 1));
        $res = \json_decode($jsoncontent, true);
        if (!isset($swapData['captcha_session'])) {
            //            if (!empty(trim($content))) \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/gosuslugi/'.$swapData['mode'].'_session_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
            if (\is_array(
                $res
            ) && isset($res['captchaType']) && 'esiacaptcha' == $res['captchaType'] && isset($res['captchaSession'])) {
                $swapData['captcha_session'] = $res['captchaSession'];
            } elseif (\strpos($content, 'технические работы')) {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                    './logs/gosuslugi/'.$swapData['mode'].'_session_err_'.\time().'.txt',
                    \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content
                );
                if ($swapData['iteration'] >= 3) {
                    $error = 'Технические работы на Госуслугах';
                }
                $mysqli->executeStatement(
                    "UPDATE session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='techwork',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id
                );
                unset($swapData['session']);
            } elseif (\strpos($content, 'Внутренняя ошибка')) {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                    './logs/gosuslugi/'.$swapData['mode'].'_session_err_'.\time().'.txt',
                    \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content
                );
                if ($swapData['iteration'] >= 3) {
                    $error = 'Внутренняя ошибка источника';
                }
                $mysqli->executeStatement(
                    "UPDATE session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='error',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id
                );
                unset($swapData['session']);
            } elseif (\strpos($content, 'временно недоступен')) {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                    './logs/gosuslugi/'.$swapData['mode'].'_session_err_'.\time().'.txt',
                    \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content
                );
                if ($swapData['iteration'] >= 3) {
                    $error = 'Сервис временно недоступен';
                }
                $mysqli->executeStatement(
                    "UPDATE session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='unavailable',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id
                );
                unset($swapData['session']);
            } elseif (!empty(\trim($content))) {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                    './logs/gosuslugi/'.$swapData['mode'].'_session_err_'.\time().'.txt',
                    \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content
                );
                $mysqli->executeStatement(
                    "UPDATE session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='badsession',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id
                );
                unset($swapData['session']);
            }
        } elseif (!isset($swapData['verify_token'])) {
            //            if (!empty(trim($content))) \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/gosuslugi/'.$swapData['mode'].'_verify_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
            if (\is_array($res) && isset($res['verify_token'])) {
                $swapData['verify_token'] = $res['verify_token'];
            } elseif (\strpos($content, 'технические работы')) {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                    './logs/gosuslugi/'.$swapData['mode'].'_verify_err_'.\time().'.txt',
                    \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content
                );
                if ($swapData['iteration'] >= 5) {
                    $error = 'Технические работы на Госуслугах';
                }
                $mysqli->executeStatement(
                    "UPDATE session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='techwork',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id
                );
                unset($swapData['session']);
            } elseif (\strpos($content, 'Внутренняя ошибка')) {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                    './logs/gosuslugi/'.$swapData['mode'].'_session_err_'.\time().'.txt',
                    \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content
                );
                if ($swapData['iteration'] >= 5) {
                    $error = 'Внутренняя ошибка источника';
                }
                $mysqli->executeStatement(
                    "UPDATE session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='error',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id
                );
                unset($swapData['session']);
            } elseif (\strpos($content, 'временно недоступен')) {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                    './logs/gosuslugi/'.$swapData['mode'].'_session_err_'.\time().'.txt',
                    \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content
                );
                if ($swapData['iteration'] >= 5) {
                    $error = 'Сервис временно недоступен';
                }
                $mysqli->executeStatement(
                    "UPDATE session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='unavailable',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id
                );
                unset($swapData['session']);
            } elseif (!empty(\trim($content))) {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                    './logs/gosuslugi/'.$swapData['mode'].'_verify_err_'.\time().'.txt',
                    \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content
                );
                if ($swapData['iteration'] >= 5) {
                    $error = 'Некорректный ответ сервиса';
                }
                $mysqli->executeStatement(
                    "UPDATE session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='invalidanswer',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id
                );
                unset($swapData['session']);
            }
        } elseif (!isset($swapData['request_id'])) {
            //            if (!empty(trim($content))) \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/gosuslugi/'.$swapData['mode'].'_find_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
            if (isset($res['message']) && \strpos($res['message'], 'verify.token.is.invalid')) {
                if (!$swapData['session']->nocaptcha) {
                    //                    $mysqli->query("UPDATE session SET sessionstatusid=4,statuscode='invalidcaptcha',endtime=now() WHERE statuscode='used' AND id=" . $swapData['session']->id);
                    $mysqli->executeStatement(
                        "UPDATE session SET sessionstatusid=4,statuscode='invalidcaptcha',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id
                    );
                    unset($swapData['session']);
                } else {
                    //                    $mysqli->query("UPDATE session SET successtime=now(),success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
                    $mysqli->executeStatement(
                        "UPDATE session SET sessionstatusid=7,lasttime=now(),successtime=now(),success=ifnull(success,0)+1,statuscode='success',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id
                    );
                    if (isset($swapData['captcha_service']) && isset($swapData['captcha_id'])) {
                        //                        $mysqli->query("INSERT INTO session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),48,4,'invalidcaptcha','".$swapData['captcha_value']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
                    }
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                        './logs/gosuslugi/captcha/bad/'.$swapData['captcha_value'].'.jpg',
                        \base64_decode($swapData['captcha_image'])
                    );
                }
                unset($swapData['verify_token']);
                unset($swapData['captcha_session']);
                unset($swapData['captcha_image']);
                unset($swapData['captcha_value']);
            //                unset($swapData['session']);
            } elseif (\is_array(
                $res
            ) && ('phone' == $checktype || 'email' == $checktype || isset($res['message']) && (\strpos(
                $res['message'],
                'not.found'
            ) || \strpos($res['message'], 'не найден')))) {
                $resultData = new ResultDataList();
                if (isset($res['requestId'])) {
                    $data = [];
                    if (isset($initData['phone'])) {
                        $data['phone'] = new ResultDataField(
                            'string', 'Phone', $initData['phone'], 'Телефон', 'Телефон'
                        );
                    }
                    if (isset($initData['email'])) {
                        $data['email'] = new ResultDataField('string', 'Email', $initData['email'], 'E-mail', 'E-mail');
                    }
                    $data['result'] = new ResultDataField('string', 'Result', 'Найден', 'Результат', 'Результат');
                    $data['result_code'] = new ResultDataField(
                        'string',
                        'ResultCode',
                        'FOUND',
                        'Код результата',
                        'Код результата'
                    );
                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                if (!$swapData['session']->nocaptcha) {
                    $mysqli->executeStatement(
                        "UPDATE session SET successtime=now(),success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id
                    );
                //                    $mysqli->query("UPDATE session SET sessionstatusid=7,lasttime=now(),successtime=now(),success=ifnull(success,0)+1,statuscode='success',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id);
                } else {
                    $mysqli->executeStatement(
                        "UPDATE session SET successtime=now(),success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id
                    );
                    //                    $mysqli->query("UPDATE session SET sessionstatusid=7,lasttime=now(),successtime=now(),success=ifnull(success,0)+1,statuscode='success',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id);
                    if (isset($swapData['captcha_service']) && isset($swapData['captcha_id'])) {
                        //                        $mysqli->query("INSERT INTO session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),48,3,'success','".$swapData['captcha_value']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
                    }
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                        './logs/gosuslugi/captcha/good/'.$swapData['captcha_value'].'.jpg',
                        \base64_decode($swapData['captcha_image'])
                    );
                }

                return true;
            } elseif (isset($res['requestId'])) {
                $swapData['request_id'] = $res['requestId'];
            } elseif (!empty(\trim($content))) {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                    './logs/gosuslugi/'.$swapData['mode'].'_find_err_'.\time().'.txt',
                    \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content
                );
                if ($swapData['iteration'] > 5) {
                    $error = 'Некорректный ответ сервиса';
                }
            }
        } else {
            //            if (!empty(trim($content))) \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/gosuslugi/'.$swapData['mode'].'_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
            if (\is_array($res)) {
                $resultData = new ResultDataList();
                $data = [];
                $notified = [];
                if (isset($res['contactValueMBT'])) {
                    $data['phone'] = new ResultDataField(
                        'string',
                        'Phone',
                        $res['contactValueMBT'],
                        'Телефон',
                        'Телефон'
                    );
                    $notified[] = 'sms';
                }
                if (isset($res['contactValueEML'])) {
                    $data['email'] = new ResultDataField(
                        'string', 'Email', $res['contactValueEML'], 'E-mail', 'E-mail'
                    );
                    $notified[] = 'email';
                }
                if (1 == \count($notified)) {
                    $data['notifiedby'] = new ResultDataField(
                        'string',
                        'NotifiedBy',
                        $notified[0],
                        'Отправлено уведомление',
                        'Отправлено уведомление'
                    );
                }
                $data['result'] = new ResultDataField('string', 'Result', 'Найден', 'Результат', 'Результат');
                $data['result_code'] = new ResultDataField(
                    'string',
                    'ResultCode',
                    'FOUND',
                    'Код результата',
                    'Код результата'
                );
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                if (!$swapData['session']->nocaptcha) {
                    //                    $mysqli->query("UPDATE session SET successtime=now(),success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
                    $mysqli->executeStatement(
                        "UPDATE session SET sessionstatusid=7,lasttime=now(),successtime=now(),success=ifnull(success,0)+1,statuscode='success',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id
                    );
                } else {
                    //                    $mysqli->query("UPDATE session SET successtime=now(),success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
                    $mysqli->executeStatement(
                        "UPDATE session SET sessionstatusid=7,lasttime=now(),successtime=now(),success=ifnull(success,0)+1,statuscode='success',captchaimage='' WHERE statuscode='used' AND id=".$swapData['session']->id
                    );
                    if (isset($swapData['captcha_service']) && isset($swapData['captcha_id'])) {
                        //                        $mysqli->query("INSERT INTO session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),48,3,'success','".$swapData['captcha_value']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
                    }
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                        './logs/gosuslugi/captcha/good/'.$swapData['captcha_value'].'.jpg',
                        \base64_decode($swapData['captcha_image'])
                    );
                }

                return true;
            } elseif (!empty(\trim($content))) {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                    './logs/gosuslugi/'.$swapData['mode'].'_err_'.\time().'.txt',
                    \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content
                );
                if (\strpos($content, 'Внутренняя ошибка')) {
                    $error = 'Внутренняя ошибка источника';
                } elseif (\strpos($content, 'временно недоступен')) {
                    $error = 'Сервис временно недоступен';
                } elseif ($swapData['iteration'] > 5) {
                    $error = 'Некорректный ответ сервиса';
                }
            }
        }
        $rContext->setSwapData($swapData);
        if ($error || $swapData['iteration'] > 20) {
            $rContext->setFinished();
            $rContext->setError($error ?: 'Превышено количество попыток получения ответа');
        }
        if (!isset($swapData['session'])) {
            $rContext->setSleep(1);
        }

        return false;
    }
}

<?php

class VKCheckPlugin implements PluginInterface
{
    //    private $googlekey = '6Le00B8TAAAAACHiybbHy8tMOiJhM5vh88JVtP4c';
    private $captcha_service = [
        //        array('host' => 'api.capmonster.cloud', 'key' => 'afb26cbb248d650ea8b8d88822984242'),
        ['host' => 'rucaptcha.com', 'key' => 'd167c71a9278312f184f17caa4e71050'],
        ['host' => 'api.anti-captcha.com', 'key' => '63def0a149a147e0d13e409cc8318fc3'],
    ];
    private $captcha_threads = 1;

    public function getName()
    {
        return 'VK';
    }

    public function getTitle($checktype = '')
    {
        $title = ['' => 'Поиск учетной записи в VK', 'vk_phonecheck' => 'VK - проверка телефона на наличие пользователя', 'vk_emailcheck' => 'VK - проверка email на наличие пользователя'];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск учетной записи в VK';
    }

    public function getSessionData(array $params, $nocaptcha)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $reqId = $params['_reqId'];
        $sessionData = null;
        $mysqli->executeStatement('UPDATE session s SET lasttime=now(),request_id='.$reqId.' WHERE request_id IS NULL AND sessionstatusid=2 AND sourceid=22 AND unix_timestamp(now())-unix_timestamp(lasttime)>5 ORDER BY lasttime limit 1');
        $result = $mysqli->executeQuery("SELECT id,cookies,starttime,lasttime,captcha,token,enckey,device,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM session s WHERE sourceid=22 AND request_id=".$reqId.' ORDER BY lasttime limit 1');
        if ($result && 0 == $result->rowCount() && $nocaptcha) {
            $mysqli->executeStatement('UPDATE session s SET lasttime=now(),request_id='.$reqId.' WHERE request_id IS NULL AND sessionstatusid IN (3,5) AND sourceid=22 ORDER BY lasttime limit 1');
            $result = $mysqli->executeQuery("SELECT id,cookies,starttime,lasttime,'' captcha,token,enckey,device,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM session s WHERE sourceid=22 AND request_id=".$reqId.' ORDER BY lasttime limit 1');
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
                $sessionData->proxy_auth = \strlen($row['proxy_auth']) > 1 ? $row['proxy_auth'] : false;
                $sessionData->id = $row['id'];
                if ('' == $row['captcha']) {
                    $nocaptcha = true;
                }
                $sessionData->code = $nocaptcha ? '' : $row['captcha'];
                $sessionData->token = $row['token'];
                $sessionData->starttime = $row['starttime'];
                $sessionData->lasttime = $row['lasttime'];
                $sessionData->cookies = $row['cookies'];
                $sessionData->nocaptcha = $nocaptcha;
                $mysqli->executeStatement('UPDATE session SET '.($nocaptcha ? '' : 'sessionstatusid=3,endtime=now(),')."statuscode='used',lasttime=now(),used=ifnull(used,0)+1,request_id=NULL WHERE id=".$sessionData->id);
                //                if ($sessionData->proxyid)
                //                    $mysqli->query("UPDATE proxy SET lasttime=now(),used=used+1 WHERE id=".$sessionData->proxyid);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        if (!isset($initData['phone']) && !isset($initData['email'])) {
            $rContext->setFinished();
            $rContext->setError('Указаны не все обязательные параметры (телефон или email)');

            return false;
        }
        if (isset($initData['phone'])) {
            //            if (strlen($initData['phone'])==10)
            //                $initData['phone']='7'.$initData['phone'];
            //            if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
            //                $initData['phone']='7'.substr($initData['phone'],1);
            /*
                        if(substr($initData['phone'],0,1)!='7'){
                            $rContext->setFinished();
            //                $rContext->setError('Поиск производится только по российским телефонам');
                            return false;
                        }
            */
        }
        if (!isset($initData['phone'])) {
            $rContext->setFinished();

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
        if (!isset($swapData['start_time'])) {
            $swapData['start_time'] = \microtime(true);
        }
        !isset($swapData['iteration']) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
        if (!isset($swapData['session'])) {
            //            unset($swapData['captcha']);
            unset($swapData['captcha_image']);
            unset($swapData['captcha_url']);
            //            unset($swapData['captcha_id'.$swapData['num']]);
            //            unset($swapData['captcha_token']);
            unset($swapData['hash']);
            $swapData['unauth_id'] = \rand(1, 2147483647);
            $swapData['session'] = $this->getSessionData($params, $swapData['iteration'] > 50);
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
            if ($swapData['iteration'] > 50 && \rand(0, 2)) {
                $astro = ['213.108.196.179:10687'];
                $swapData['session']->proxyid = 2;
                $swapData['session']->proxy = $astro[\rand(0, \count($astro) - 1)];
                $swapData['session']->proxy_auth = 'isphere:e6eac1';
            }
        }
        /*
                if (!isset($swapData['captcha_token']) && $swapData['session']->code) {
                    $swapData['captcha_token'] = $swapData['session']->code;
                    $swapData['session']->code = '';
                }
        */
        $rContext->setSwapData($swapData);
        $ch = $rContext->getCurlHandler();
        $page = 'https://id.vk.com/restore/';
        if (isset($swapData['captcha_url']) && !isset($swapData['captcha_image'])) {
            $url = $swapData['captcha_url'];
            \curl_setopt($ch, \CURLOPT_URL, $url);
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 5);
            \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
            //            curl_setopt($ch, CURLOPT_HEADER, true);
            if ($swapData['session']->proxy) {
                \curl_setopt($ch, \CURLOPT_PROXY, $swapData['session']->proxy);
                if ($swapData['session']->proxy_auth) {
                    \curl_setopt($ch, \CURLOPT_PROXYUSERPWD, $swapData['session']->proxy_auth);
                    \curl_setopt($ch, \CURLOPT_PROXYAUTH, \CURLAUTH_ANY);
                }
            }
        //            echo $swapData['iteration'].": $url\n";
        //            echo "\n";
        } elseif (isset($swapData['captcha_image']) && !isset($swapData['captcha_value'])) {
            if (!isset($swapData['captcha_id'])) {
                $swapData['captcha_service'] = (int) (($swapData['iteration'] - 1) / 3) % \count($this->captcha_service);
                //                echo $swapData['iteration'].": New captcha from ".$this->captcha_service[$swapData['captcha_service']]['host']."\n";
                $rContext->setSwapData($swapData);
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service']]['host']) {
                    $params = ['key' => $this->captcha_service[$swapData['captcha_service']]['key'], 'method' => 'base64', 'body' => $swapData['captcha_image']];
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service']]['host'].'/in.php';
                    \curl_setopt($ch, \CURLOPT_POST, true);
                    \curl_setopt($ch, \CURLOPT_POSTFIELDS, \http_build_query($params));
                } else {
                    $params = ['clientKey' => $this->captcha_service[$swapData['captcha_service']]['key'], 'task' => ['type' => 'ImageToTextTask', 'body' => $swapData['captcha_image']]];
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service']]['host'].'/createTask';
                    \curl_setopt($ch, \CURLOPT_POST, true);
                    \curl_setopt($ch, \CURLOPT_POSTFIELDS, \json_encode($params, \JSON_UNESCAPED_UNICODE));
                }
            } else {
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service']]['host']) {
                    $params = ['key' => $this->captcha_service[$swapData['captcha_service']]['key'], 'action' => 'get', 'id' => $swapData['captcha_id']];
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service']]['host'].'/res.php?'.\http_build_query($params);
                } else {
                    $params = ['clientKey' => $this->captcha_service[$swapData['captcha_service']]['key'], 'taskId' => $swapData['captcha_id']];
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service']]['host'].'/getTaskResult';
                    \curl_setopt($ch, \CURLOPT_POST, true);
                    \curl_setopt($ch, \CURLOPT_POSTFIELDS, \json_encode($params, \JSON_UNESCAPED_UNICODE));
                }
            }
            \curl_setopt($ch, \CURLOPT_URL, $url);
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 3);
            \curl_setopt($ch, \CURLOPT_PROXY, false);
        //            echo $swapData['iteration'].": $url\n";
        //            var_dump($params);
        //            echo "\n";
            /*
                    } elseif (isset($swapData['captcha']) && !isset($swapData['captcha_token'])) {
                        if (!isset($swapData['captcha_id'.$swapData['num']])) {
                            $swapData['captcha_service'.$swapData['num']] = intval(($swapData['iteration']-1)/5)%sizeof($this->captcha_service);
                            $rContext->setSwapData($swapData);
                            if ($this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']=='rucaptcha.com') {
                                $params = array(
                                    'key' => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'],
                                    'method' => 'userrecaptcha',
                                    'googlekey' => $this->googlekey,
                                    'pageurl' => $page,
                                );
            //                    if ($swapData['session']->proxy) {
            //                        $params['proxytype'] = 'http';
            //                        $params['proxy'] = ($swapData['session']->proxy_auth ? $swapData['session']->proxy_auth.'@' : '').$swapData['session']->proxy;
            //                    }
                                $url = "https://".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']."/in.php?".http_build_query($params);
                            } else {
                                $params = array(
                                    "clientKey" => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'],
                                    "task" => array(
                                        "type" => "NoCaptchaTaskProxyless",
            //                            "type" => "NoCaptchaTask",
                                        "websiteURL" => $page,
                                        "websiteKey" => $this->googlekey,
            //                            "proxyType" => "http",
            //                            "proxyAddress" => "8.8.8.8",
            //                            "proxyPort" => 8080,
            //                            "proxyLogin" => "proxyLoginHere",
            //                            "proxyPassword" => "proxyPasswordHere",
            //                            "userAgent" => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36",
                                    ),
                                );
                                $url = "https://".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']."/createTask";
                                curl_setopt($ch, CURLOPT_POST, true);
                                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params,JSON_UNESCAPED_UNICODE));
                            }
                        } else {
                            if ($this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']=='rucaptcha.com') {
                                $params = array(
                                    'key' => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'],
                                    'action' => 'get',
                                    'id' => $swapData['captcha_id'.$swapData['num']],
                                );
                                $url = "https://".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']."/res.php?".http_build_query($params);
                            } else {
                                $params = array(
                                    "clientKey" => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'],
                                    "taskId" => $swapData['captcha_id'.$swapData['num']],
                                );
                                $url = "https://".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']."/getTaskResult";
                                curl_setopt($ch, CURLOPT_POST, true);
                                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params,JSON_UNESCAPED_UNICODE));
                            }
                        }
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch,CURLOPT_TIMEOUT, 3);
            //            echo $swapData['iteration'].": $url\n";
            //            var_dump($params);
            //            echo "\n";
            */
        } else {
            $url = 'https://api.vk.com/method/restore.resetPassword';
            $header = ['Content-Type: application/x-www-form-urlencoded; charset=UTF-8', 'Origin: https://id.vk.com', 'Referer: https://id.vk.com/', 'X-Requested-With: XMLHttpRequest'];
            $params = ['v' => '5.83', 'lang' => 0, 'app_id' => 0, 'app_version' => '', 'device_id' => '3cdb0740-b9ef-4019-acda-95654a858a6d', 'unauth_id' => $swapData['unauth_id'], 'platform' => 'vkcom', 'history[]' => 'reset', 'login' => isset($initData['phone']) ? $initData['phone'] : $initData['email'], 'restore_session_id' => $swapData['session']->token, 'supports_auth' => 1, 'vkui' => 1];
            if (isset($swapData['hash']) && isset($initData['last_name'])) {
                $params['hash'] = $swapData['hash'];
                $params['last_name'] = $initData['last_name'];
            }
            if (isset($swapData['captcha_sid']) && isset($swapData['captcha_value'])) {
                $params['captcha_key'] = $swapData['captcha_value'];
                $params['captcha_sid'] = $swapData['captcha_sid'];
                /*
                            } elseif (isset($swapData['captcha']) && isset($swapData['captcha_token'])) {
                                $params['recaptcha'] = $swapData['captcha_token'];
                */
            }
            \curl_setopt($ch, \CURLOPT_URL, $url);
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 5);
            \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
            \curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);
            //            curl_setopt($ch, CURLOPT_HEADER, true);
            //            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            \curl_setopt($ch, \CURLOPT_POST, true);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, \http_build_query($params));
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
        //        $rContext->setSwapData($swapData);
        $error = $swapData['iteration'] >= 5 ? \curl_error($rContext->getCurlHandler()) : false;
        $content = \curl_multi_getcontent($rContext->getCurlHandler());
        if (isset($swapData['captcha_url']) && !isset($swapData['captcha_image']) && \strlen($content) > 1000 && 'JFIF' == \substr($content, 6, 4) && "\xff\xd9" == \substr($content, \strlen($content) - 2, 2)) {
            $value = neuro_post($content, 'vkdecode');
            if ($value && 'ERROR' != \substr($value, 0, 5)) {
                $swapData['captcha_value'] = $value;
                if ($swapData['iteration'] > 1) {
                    --$swapData['iteration'];
                }
                //                $rContext->setSleep(1);
            }
            if (\microtime(true) - $swapData['start_time'] > 60 || $swapData['iteration'] > 10) {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/vk/captcha_slow_'.$swapData['iteration'].'_'.\time().'_'.$value.'.jpg', $content);
            }
            unset($swapData['captcha_url']);
            $swapData['captcha_image'] = \base64_encode($content);
            $rContext->setSwapData($swapData);

            return true;
        }
        if (isset($swapData['captcha_image']) && !isset($swapData['captcha_value'])) {
            //            echo "$content\n";
            $res = \json_decode($content, true);
            if (!isset($swapData['captcha_id'])) {
                //                echo "Thread "."  Getting new captcha\n";
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service']]['host']) {
                    if (false !== \strpos($content, 'OK|')) {
                        $swapData['captcha_id'] = \substr($content, 3);
                        $swapData['captcha_time'] = \time();
                    } elseif ($swapData['iteration'] > 20) {
                        $rContext->setFinished();
                        $rContext->setError('Ошибка получения капчи');
                        \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                            './logs/vk/'.$initData['checktype'].'_captcha_image_err_'.$swapData['iteration'].'_'.\time().'.txt',
                            /* curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n". */
                            $content."\r\n".$this->captcha_service[$swapData['captcha_service']]['host']
                        );
                    }
                } else {
                    if (isset($res['taskId'])) {
                        $swapData['captcha_id'] = $res['taskId'];
                        $swapData['captcha_time'] = \time();
                    } elseif ($swapData['iteration'] > 20) {
                        $rContext->setFinished();
                        $rContext->setError('Ошибка получения капчи');
                        \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                            './logs/vk/'.$initData['checktype'].'_captcha_image_err_'.$swapData['iteration'].'_'.\time().'.txt',
                            /* curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n". */
                            $content."\r\n".$this->captcha_service[$swapData['captcha_service']]['host']
                        );
                    }
                }
            } else {
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service']]['host']) {
                    if ('CAPCHA_NOT_READY' == $content && \time() - $swapData['captcha_time'] < 30) {
                    } else {
                        if (false !== \strpos($content, 'OK|')) {
                            $swapData['captcha_value'] = \substr($content, 3);
                        //                            echo "Thread "."  Received captcha ID ".$swapData['captcha_id']."\n";
                        } else {
                            $swapData['captcha_value'] = 'abcdef';
                            unset($swapData['captcha_id']);
                            //                        } elseif ($swapData['iteration']>20) {
                            //                            $rContext->setFinished();
                            //                            $rContext->setError('Ошибка распознавания капчи');
                        }
                        //                        unset($swapData['captcha_id']);
                    }
                } else {
                    if (!$content) {
                    } elseif (isset($res['status']) && 'ready' !== $res['status'] && \time() - $swapData['captcha_time'] < 30) {
                    } else {
                        if (isset($res['solution']['text'])) {
                            $swapData['captcha_value'] = $res['solution']['text'];
                        //                            echo "Thread "."  Received captcha ID ".$swapData['captcha_id']."\n";
                        } else {
                            $swapData['captcha_value'] = 'abcdef';
                            unset($swapData['captcha_id']);
                            //                        } elseif ($swapData['iteration']>20) {
                            //                            $rContext->setFinished();
                            //                            $rContext->setError('Ошибка распознавания капчи');
                        }
                        //                        unset($swapData['captcha_id']);
                    }
                }
                //                $swapData['iteration']--;
            }
            $rContext->setSwapData($swapData);
            if (!isset($swapData['captcha_value']) && isset($swapData['captcha_id'])) {
                $rContext->setSleep(5);
            } else {
                $rContext->setSleep(1);
            }

            return true;
        }
        /*
                if (isset($swapData['captcha']) && !isset($swapData['captcha_token'])) {
        //            echo "$content\n";
                    $res = json_decode($content, true);
                    if (!isset($swapData['captcha_id'.$swapData['num']])) {
        //                echo "Thread ".$swapData['num']."  Getting new captcha\n";
                        if ($this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']=='rucaptcha.com') {
                            if (strpos($content,'OK|')!==false){
                                $swapData['captcha_id'.$swapData['num']] = substr($content,3);
                            } elseif ($swapData['iteration']>10) {
        //                        $rContext->setFinished();
        //                        $rContext->setError('Ошибка получения капчи');
                                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/vk/'.$initData['checktype'].'_captcha_err_'.time().'.txt',$content."\r\n".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']);
                            }
                        } else {
                            if (isset($res['taskId'])){
                                $swapData['captcha_id'.$swapData['num']] = $res['taskId'];
                            } elseif ($swapData['iteration']>10) {
        //                        $rContext->setFinished();
        //                        $rContext->setError('Ошибка получения капчи');
                                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/vk/'.$initData['checktype'].'_captcha_err_'.time().'.txt',$content."\r\n".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']);
                            }
                        }
                    } else {
                        if ($this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']=='rucaptcha.com') {
                            if ($content=='CAPCHA_NOT_READY') {
                            } else {
                                if (strpos($content,'OK|')!==false) {
                                    $swapData['captcha_token'] = substr($content,3);
                                    $swapData['captcha_id'] = $swapData['captcha_id'.$swapData['num']];
                                    $swapData['captcha_service'] = $swapData['captcha_service'.$swapData['num']];
        //                            echo "Thread ".$swapData['num']."  Received captcha ID ".$swapData['captcha_id'.$swapData['num']]."\n";
                                } elseif ($swapData['iteration']>10) {
        //                            $rContext->setFinished();
        //                            $rContext->setError('Ошибка распознавания капчи');
                                }
                                unset($swapData['captcha_id'.$swapData['num']]);
                            }
                        } else {
                            if (isset($res['status']) && $res['status']!=='ready') {
                            } else {
                                if (isset($res['solution']['gRecaptchaResponse'])) {
                                    $swapData['captcha_token'] = $res['solution']['gRecaptchaResponse'];
                                    $swapData['captcha_id'] = $swapData['captcha_id'.$swapData['num']];
                                    $swapData['captcha_service'] = $swapData['captcha_service'.$swapData['num']];
        //                            echo "Thread ".$swapData['num']."  Received captcha ID ".$swapData['captcha_id'.$swapData['num']]."\n";
                                } elseif ($swapData['iteration']>10) {
        //                            $rContext->setFinished();
        //                            $rContext->setError('Ошибка распознавания капчи');
                                }
                                unset($swapData['captcha_id'.$swapData['num']]);
                            }
                        }
                        $swapData['iteration']--;
                    }
                    if (++$swapData['num'] > $this->captcha_threads) {
                        $swapData['num']=1;
                    }
                    $rContext->setSwapData($swapData);
                    if (!isset($swapData['captcha_token']) && isset($swapData['captcha_id'.$swapData['num']])) $rContext->setSleep(3); else $rContext->setSleep(1);
                    return true;
                }
        */
        if (!$error) {
            //            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/vk/vk_restore_'.$swapData['iteration'].'_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
            if (\microtime(true) - $swapData['start_time'] > 60 || $swapData['iteration'] > 10) {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/vk/vk_restore_slow_'.$swapData['iteration'].'_'.\time().'.txt', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);
            }
            $start = \strpos($content, '{');
            $content = \trim(\substr($content, $start, \strlen($content) - $start + 1));
            $res = \json_decode($content, true);
            if ($res && isset($res['response'][1])) {
                if (isset($swapData['captcha_id']) && isset($swapData['captcha_service'])) {
                    /*
                                        $params = array(
                                            'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                                            'action' => 'reportgood',
                                            'id' => $swapData['captcha_id'],
                                        );
                                        $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/res.php?".http_build_query($params);
                                        $res = file_get_contents($url);
                    */
                    //                    $mysqli->query("INSERT INTO session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captchaimage,captcha,captcha_service,captcha_id) VALUES (now(),now(),22,3,'success','".$swapData['captcha_image']."','".$swapData['captcha_value']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
                    $mysqli->executeStatement("INSERT INTO session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),22,3,'success','".$swapData['captcha_value']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
                    //                    echo "Captcha ID ".$swapData['captcha_id']." reported as good with result $res\n";
                }
                if (isset($swapData['captcha_image']) && isset($swapData['captcha_value'])) {
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/vk/captcha/good/'.$swapData['captcha_value'].'.jpg', \base64_decode($swapData['captcha_image']));
                }
                //                unset($swapData['captcha']);
                //                unset($swapData['captcha_image']);
                if (isset($res['response'][1]['user']) || isset($res['response'][1]['users'])) {
                    $resultData = new ResultDataList();
                    $user = isset($res['response'][1]['user']) ? $res['response'][1]['user'] : $res['response'][1]['users'];
                    if (isset($swapData['data'])) {
                        $data = $swapData['data'];
                    } else {
                        if (isset($initData['phone'])) {
                            $data['phone'] = new ResultDataField('string', 'phone', $initData['phone'], 'Телефон', 'Телефон');
                        } else {
                            $data['email'] = new ResultDataField('string', 'email', $initData['email'], 'E-mail', 'E-mail');
                        }
                        $data['result'] = new ResultDataField('string', 'result', 'Найден', 'Результат', 'Результат');
                        $data['result_code'] = new ResultDataField('string', 'result_code', 'FOUND', 'Код результата', 'Код результата');
                    }
                    $first_name = '';
                    $name = '';
                    if (isset($user['first_name'])) {
                        $first_name = \iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', \strtr($user['first_name'], [\chr(0) => ' '])));
                        $name = $first_name;
                        $data['firstname'] = new ResultDataField('string', 'FirstName', $first_name, 'Имя', 'Имя');
                    }
                    if (isset($user['last_name']) && $user['last_name'] > '') {
                        $last_name = \iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', \strtr($user['last_name'], [\chr(0) => ' '])));
                        $name = \trim($name.' '.$last_name);
                        $data['lastname'] = new ResultDataField('string', 'LastName', $last_name, 'Фамилия', 'Фамилия');
                    }
                    if ($name) {
                        $data['name'] = new ResultDataField('string', 'Name', $name, 'Полное имя', 'Полное имя');
                    }
                    if (isset($initData['last_name']) && isset($swapData['hash'])) {
                        $data['match_code'] = new ResultDataField('string', 'match_code', isset($initData['first_name']) && \App\Utils\Legacy\StrUtilStatic::str_uprus($initData['first_name']) == \App\Utils\Legacy\StrUtilStatic::str_uprus($first_name) ? 'MATCHED' : 'MATCHED_LASTNAME_ONLY', 'Результат сравнения имени', 'Результат сравнения имени');
                    }
                    if (isset($user['sex']) && $user['sex']) {
                        $data['gender'] = new ResultDataField('string', 'Gender', 2 == $user['sex'] ? 'male' : (1 == $user['sex'] ? 'female' : ''), 'Пол', 'Пол');
                    }
                    if (isset($res['response'][1]['home_place'])) {
                        $data['place'] = new ResultDataField('string', 'Place', \iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', \strtr($res['response'][1]['home_place'], [\chr(0) => ' ']))), 'Город', 'Город');
                    }
                    if (isset($user['screen_name']) && \strlen($user['screen_name']) > 1) {
                        $data['link'] = new ResultDataField('url:recursive', 'Link', 'https://vk.com/'.$user['screen_name'], 'Ссылка', 'Ссылка на профиль');
                    }
                    if (isset($user['photo_100']) && !\strpos($user['photo_100'], 'vk.com/images')) {
                        $data['photo'] = new ResultDataField('image', 'Photo', $user['photo_100'], 'Фото профиля', 'Фото профиля');
                    }
                    $data['state'] = new ResultDataField('string', 'State', isset($user['deactivated']) ? $user['deactivated'] : 'active', 'Статус', 'Статус');
                    if (isset($user['online_info']['visible'])) {
                        $data['visible'] = new ResultDataField('string', 'Visible', $user['online_info']['visible'] ? 'Да' : 'Нет', 'Видимый', 'Видимый');
                    }
                    if (isset($user['online_info']['is_mobile'])) {
                        $data['is_mobile'] = new ResultDataField('string', 'IsMobile', $user['online_info']['is_mobile'] ? 'Да' : 'Нет', 'Мобильное приложение', 'Мобильное приложение');
                    }
                    if (isset($user['date_created'])) {
                        $data['created'] = new ResultDataField('datetime', 'Created', \date('c', $user['date_created']), 'Зарегистрирован', 'Зарегистрирован');
                    }
                    if (isset($user['online_info']['last_seen'])) {
                        $data['last_seen'] = new ResultDataField('datetime', 'LastSeen', \date('c', $user['online_info']['last_seen']), 'Время последнего посещения', 'Время последнего посещения');
                    }
                    $resultData->addResult($data);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->executeStatement("UPDATE session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    //                        if ($swapData['session']->proxyid)
                    //                            $mysqli->query("UPDATE proxy SET success=success+1,successtime=now() WHERE id=".$swapData['session']->proxyid);
                    return true;
                }
                if (!isset($swapData['hash'])) {
                    //                    if (!$swapData['session']->nocaptcha)
                    //                        $mysqli->query("UPDATE session SET sessionstatusid=3,success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    //                      $mysqli->query("UPDATE session SET sessionstatusid=7,success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    $data = [];
                    if (isset($initData['phone'])) {
                        $data['phone'] = new ResultDataField('string', 'phone', $initData['phone'], 'Телефон', 'Телефон');
                        if (isset($res['response'][1]['email'])) {
                            $data['email'] = new ResultDataField('string', 'email', $res['response'][1]['email'], 'E-mail', 'E-mail');
                        }
                    } else {
                        $data['email'] = new ResultDataField('string', 'email', $initData['email'], 'E-mail', 'E-mail');
                    }
                    $data['result'] = new ResultDataField('string', 'result', 'Найден', 'Результат', 'Результат');
                    $data['result_code'] = new ResultDataField('string', 'result_code', 'FOUND', 'Код результата', 'Код результата');
                    if (!isset($initData['last_name'])) {
                        $resultData = new ResultDataList();
                        $resultData->addResult($data);
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
                        $mysqli->executeStatement("UPDATE session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                        //                        if ($swapData['session']->proxyid)
                        //                            $mysqli->query("UPDATE proxy SET success=success+1,successtime=now() WHERE id=".$swapData['session']->proxyid);
                        return true;
                    } elseif (isset($res['response'][1]['hash']) || isset($res['response'][1]['restore_hash'])) {
                        $swapData['data'] = $data;
                        $swapData['hash'] = isset($res['response'][1]['hash']) ? $res['response'][1]['hash'] : $res['response'][1]['restore_hash'];
                    } else {
                        /* if (isset($res['response'][1]['uid'])) */
                        \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/vk/vk_restore_nohash_'.$swapData['iteration'].'_'.\time().'.txt', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);
                        if (isset($res['response'][1]['uid'])) {
                            $data['link'] = new ResultDataField('url:recursive', 'Link', 'https://vk.com/id'.$res['response'][1]['uid'], 'Ссылка', 'Ссылка на профиль');
                        }
                        $resultData = new ResultDataList();
                        $resultData->addResult($data);
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
                        $mysqli->executeStatement("UPDATE session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                        //                        if ($swapData['session']->proxyid)
                        //                            $mysqli->query("UPDATE proxy SET success=success+1,successtime=now() WHERE id=".$swapData['session']->proxyid);
                        return true;
                        /*
                                            } else {
                                                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/vk/vk_restore_err_'.$swapData['iteration'].'_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
                                                unset($swapData['session']);
                                                if ($swapData['iteration']>=3) $error = "Невозможно обработать ответ";
                        */
                    }
                }
            } elseif ($res && isset($res['error']['error_code'])) {
                if (100 == $res['error']['error_code']) {
                    $resultData = new ResultDataList();
                    if (isset($swapData['hash'])) {
                        $data = $swapData['data'];
                        $data['match_code'] = new ResultDataField('string', 'match_code', 'NOT_MATCHED', 'Результат сравнения имени', 'Результат сравнения имени');
                        $resultData->addResult($data);
                    } else {
                        //                        if (!$swapData['session']->nocaptcha)
                        //                            $mysqli->query("UPDATE session SET sessionstatusid=3,success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                        //                            $mysqli->query("UPDATE session SET sessionstatusid=7,success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    }
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->executeStatement("UPDATE session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    //                    if ($swapData['session']->proxyid)
                    //                        $mysqli->query("UPDATE proxy SET success=success+1,successtime=now() WHERE id=".$swapData['session']->proxyid);
                    return true;
                } elseif (9 == $res['error']['error_code']) {
                    //                    $mysqli->query("UPDATE session SET statuscode='limit' WHERE id=" . $swapData['session']->id);
                    $mysqli->executeStatement('UPDATE session SET unlocktime=date_add(now(),interval '.($swapData['session']->proxyid < 100 ? '15 second' : '30 minute')."),sessionstatusid=6,statuscode='limit' WHERE sourceid=22 AND proxyid=".$swapData['session']->proxyid.' ORDER BY lasttime DESC LIMIT 10');
                    //                    if ($swapData['session']->proxyid)
                    //                        $mysqli->query("UPDATE proxy SET success=success+1,successtime=now() WHERE id=".$swapData['session']->proxyid);
                    unset($swapData['session']);
                //                    unset($swapData['captcha']);
                } elseif (10 == $res['error']['error_code']) {
                    $error = 'Внутренняя ошибка источника';
                    $mysqli->executeStatement("UPDATE session SET success=ifnull(success,0)+1,statuscode='internal' WHERE id=".$swapData['session']->id);
                } elseif (14 == $res['error']['error_code']) {
                    //                    $mysqli->query("UPDATE session SET unlocktime=date_add(now(),interval 30 minute),sessionstatusid=6,statuscode='captcha' WHERE id=" . $swapData['session']->id);
                    //                    unset($swapData['session']);
                    if (isset($swapData['captcha_id']) && isset($swapData['captcha_service'])) {
                        /*
                                                $params = array(
                                                    'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                                                    'action' => 'reportbad',
                                                    'id' => $swapData['captcha_id'],
                                                );
                                                $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/res.php?".http_build_query($params);
                                                $res = file_get_contents($url);
                        */
                        //                        $mysqli->query("INSERT INTO session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captchaimage,captcha,captcha_service,captcha_id) VALUES (now(),now(),22,4,'invalidcaptcha','".$swapData['captcha_image']."','".$swapData['captcha_value']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
                        $mysqli->executeStatement("INSERT INTO session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),22,4,'invalidcaptcha','".$swapData['captcha_value']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
                        //                        echo "Captcha ID ".$swapData['captcha_id']." reported as bad with result $res\n";
                    }
                    if (isset($swapData['captcha_image']) && isset($swapData['captcha_value'])) {
                        \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/vk/captcha/bad/'.$swapData['captcha_value'].'.jpg', \base64_decode($swapData['captcha_image']));
                    }
                    unset($swapData['captcha_image']);
                    unset($swapData['captcha_value']);
                    unset($swapData['captcha_id']);
                    if ($swapData['iteration'] % 5 == 0) {
                        unset($swapData['session']);
                    } else {
                        $swapData['captcha_url'] = $res['error']['captcha_img'];
                        $swapData['captcha_sid'] = $res['error']['captcha_sid'];
                    }
                } elseif (3300 == $res['error']['error_code']) {
                    $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 30 minute),sessionstatusid=6,statuscode='recaptcha' WHERE id=".$swapData['session']->id);
                    //                    if ($swapData['session']->proxyid)
                    //                        $mysqli->query("UPDATE proxy SET success=success+1,successtime=now() WHERE id=".$swapData['session']->proxyid);
                    unset($swapData['session']);
                    /*
                                        if (isset($swapData['captcha'])) {
                                            if (!$swapData['session']->nocaptcha) {
                                                $mysqli->query("UPDATE session SET sessionstatusid=4,statuscode='invalidcaptcha' WHERE statuscode='used' AND id=" . $swapData['session']->id);
                    //                            if ($swapData['iteration']<20) {
                                                    unset($swapData['session']);
                                                    unset($swapData['captcha']);
                    //                            }
                                            }
                                            unset($swapData['hash']);
                                            unset($swapData['captcha_token']);
                                        } else {
                                            $swapData['captcha'] = 1;
                                        }
                                        $swapData['iteration']--;
                    */
                } elseif (3301 == $res['error']['error_code']) {
                    $data = [];
                    if (isset($initData['phone'])) {
                        $data['phone'] = new ResultDataField('string', 'phone', $initData['phone'], 'Телефон', 'Телефон');
                    } else {
                        $data['email'] = new ResultDataField('string', 'email', $initData['email'], 'E-mail', 'E-mail');
                    }
                    $data['result'] = new ResultDataField('string', 'result', 'Найден', 'Результат', 'Результат');
                    $data['result_code'] = new ResultDataField('string', 'result_code', 'FOUND', 'Код результата', 'Код результата');
                    $resultData = new ResultDataList();
                    $resultData->addResult($data);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->executeStatement("UPDATE session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    //                    if ($swapData['session']->proxyid)
                    //                        $mysqli->query("UPDATE proxy SET success=success+1,successtime=now() WHERE id=".$swapData['session']->proxyid);
                    return true;
                } else {
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/vk/vk_unknown_err_'.$swapData['iteration'].'_'.\time().'.txt', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);
                }
            } elseif ($res && !isset($res['error'])) {
                $error = 'Сервис временно недоступен';
                $mysqli->executeStatement("UPDATE session SET success=ifnull(success,0)+1,statuscode='unavailable' WHERE id=".$swapData['session']->id);
            } elseif (\strpos($content, 'недоступен') || \strpos($content, 'невозможно') || \strpos($content, 'техническим') || \strpos($content, '502 Bad Gateway') || \strpos($content, 'Service Unavailable')) {
                $error = 'Сервис временно недоступен';
                $mysqli->executeStatement("UPDATE session SET success=ifnull(success,0)+1,statuscode='unavailable' WHERE id=".$swapData['session']->id);
            } else {
                if ($content) {
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/vk/vk_invalid_err_'.$swapData['iteration'].'_'.\time().'.txt', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);
                    $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='invalidanswer' WHERE id=".$swapData['session']->id);
                    if ($swapData['iteration'] >= 10) {
                        $error = 'Некорректный ответ';
                    }
                } else {
                    //                    $mysqli->query("UPDATE session SET statuscode='empty' WHERE id=" . $swapData['session']->id);
                    $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 15 minute),sessionstatusid=6,statuscode='empty' WHERE proxyid>100 AND id=".$swapData['session']->id);
                }
                //                if (isset($swapData['data'])) {
                //                    $rContext->setSleep(3);
                //                } else {
                unset($swapData['session']);
                //                    unset($swapData['captcha']);
                //                }
            }
        }
        if (!$error && isset($swapData['iteration']) && $swapData['iteration'] >= 30) {
            $error = 'Превышено количество попыток получения ответа';
        }
        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();

            return false;
        }
        $rContext->setSwapData($swapData);
        $rContext->setSleep(1);

        return true;
    }
}

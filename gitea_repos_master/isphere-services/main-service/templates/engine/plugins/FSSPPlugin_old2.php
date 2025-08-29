<?php

class FSSPPlugin_old2 implements PluginInterface
{
    private $captcha_service = [
//        array('host' => 'api.capmonster.cloud', 'key' => 'afb26cbb248d650ea8b8d88822984242'),
        ['host' => 'rucaptcha.com', 'key' => 'd167c71a9278312f184f17caa4e71050'],
        ['host' => 'api.anti-captcha.com', 'key' => '63def0a149a147e0d13e409cc8318fc3'],
    ];
    private $captcha_threads = 1;
    private $captcha_lifetime = 14;

    public function getName()
    {
        return 'fssp';
    }

    public function getTitle($checktype = '')
    {
        $title = [
            '' => 'ФССП - поиск исполнительных производств',
            'fssp_person' => 'ФССП - исполнительные производства (сайт)',
            'fssp_org' => 'ФССП - исполнительные производства по организации',
            'fssp_ip' => 'ФССП - информация об исполнительном производстве',
        ];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'ФССП РФ - поиск исполнительных производств';
    }

    public function getSessionData()
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

        $mysqli->query("DELETE FROM isphere.session WHERE sessionstatusid=2 AND sourceid=3 AND cookies='' ORDER BY lasttime limit 1");
        $mysqli->query('UPDATE isphere.session s SET request_id='.$reqId." WHERE sourceid=3 AND request_id IS NULL AND sessionstatusid=2 AND (statuscode<>'used' OR lasttime<from_unixtime(unix_timestamp(now())-600)) AND unix_timestamp(now())-unix_timestamp(lasttime)>10 ORDER BY lasttime limit 1");
        //        $mysqli->query("UPDATE isphere.session s SET endtime=now(),sessionstatusid=5,statuscode='expired' WHERE sourceid=3 AND request_id IS NULL AND sessionstatusid IN (1,2) AND unix_timestamp(now())-unix_timestamp(starttime)>=15");
        //        $mysqli->query("UPDATE isphere.session s SET request_id=".$reqId." WHERE sourceid=3 AND request_id IS NULL AND sessionstatusid=2 AND unix_timestamp(now())-unix_timestamp(starttime)<15 ORDER BY starttime limit 1");
        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sourceid=3 AND request_id=".$reqId.' ORDER BY lasttime limit 1');

        if ($result) {
            $row = $result->fetch_object();

            if ($row) {
                $sessionData = new \stdClass();

                $sessionData->id = $row->id;
                $sessionData->code = $row->captcha;
                $sessionData->token = $row->token;
                $sessionData->starttime = $row->starttime;
                $sessionData->lasttime = $row->lasttime;
                $sessionData->cookies = $row->cookies;
                $sessionData->proxyid = $row->proxyid;
                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = \strlen($row->proxy_auth) > 1 ? $row->proxy_auth : false;

                //                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='used',lasttime=now(),used=ifnull(used,0)+1,request_id=NULL WHERE id=".$sessionData->id);
                $mysqli->query("UPDATE isphere.session SET statuscode='used',lasttime=now(),used=ifnull(used,0)+1,captcha='',request_id=NULL WHERE id=".$sessionData->id);
                //                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='exhausted' WHERE used>=4 AND id=".$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        // //////////////////////////////////////////////////////////////////////////////////////////////////

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = \substr($initData['checktype'], 5);

        if (('person' == $checktype) && (!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date']))) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (ФИО, дата рождения)');

            return false;
        }
        /*
                if(($checktype=='person') && !isset($initData['region_id']))
                {
                    $rContext->setFinished();
                    $rContext->setError('ФССП теперь требует указывать в запросе регион');

                    return false;
                }
        */
        if (isset($initData['patronymic']) && ($initData['first_name'] == $initData['patronymic'] || $initData['last_name'] == $initData['patronymic'])) {
            $initData['patronymic'] = '';
        }

        if (isset($initData['last_name']) && $initData['last_name'] && ($initData['last_name'] == $initData['first_name'] || (isset($initData['patronymic']) && $initData['last_name'] == $initData['patronymic']) || (isset($initData['patronymic']) && $initData['first_name'] == $initData['patronymic']))) {
            $rContext->setFinished();
            $rContext->setError('ФССП не может обработать запрос с совпадением полей в ФИО');

            return false;
        }

        if (('org' == $checktype) && !isset($initData['name']) && !isset($initData['address'])) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (название и адрес организации)');

            return false;
        }

        if (('ip' == $checktype) && !isset($initData['fssp_ip'])) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (номер ИП)');

            return false;
        }

        if (isset($initData['last_name']) && isset($initData['first_name']) && \preg_match("/[^А-Яа-яЁё\s\-\.]/ui", $initData['last_name'].' '.$initData['first_name'].(isset($initData['patronymic']) ? ' '.$initData['patronymic'] : ''))) {
            $rContext->setFinished();
            $rContext->setError('Имя может содержать только русские буквы');

            return false;
        }

        $rContext->setError('Сервис временно недоступен');
        $rContext->setFinished();

        return false;

        // //////////////////////////////////////////////////////////////////////////////////////////////////

        if (!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData();
            $swapData['callback'] = 'jQuery34009543113056123491_'.(\round(\microtime(true) * 1000) - 10);
            $swapData['_'] = \round(\microtime(true) * 1000);
        }

        if (isset($swapData['captcha_time']) && (\microtime(true) - $swapData['captcha_time']) > $this->captcha_lifetime) {
            unset($swapData['captcha_image']);
            unset($swapData['captcha_id']);
        }

        (!isset($swapData['iteration'])) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
        $rContext->setSwapData($swapData);

        if (!$swapData['session']) {
            if ($swapData['iteration'] > 60) {
                $rContext->setError('Сервис временно недоступен');
                $rContext->setFinished();

                return false;
            }
            $rContext->setSleep(1);

            return false;
        }

        // //////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        if (!isset($swapData['captcha_image'])) {
            $url = 'https://is.fssp.gov.ru/refresh_visual_captcha/';
            \curl_setopt($ch, \CURLOPT_URL, $url);
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 10);
            \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
            //            curl_setopt($ch, CURLOPT_HEADER, true);
            if ($swapData['session']->proxy) {
                \curl_setopt($ch, \CURLOPT_PROXY, $swapData['session']->proxy);
                if ($swapData['session']->proxy_auth) {
                    \curl_setopt($ch, \CURLOPT_PROXYUSERPWD, $swapData['session']->proxy_auth);
                    \curl_setopt($ch, \CURLOPT_PROXYAUTH, \CURLAUTH_ANY);
                }
            }
        //            echo date('H:i:s')." ".$swapData['iteration'].": $url\n";
        } elseif (isset($swapData['captcha_image']) && !isset($swapData['captcha_value'])) {
            if (!isset($swapData['captcha_id'])) {
                $swapData['captcha_service'] = (int) (($swapData['iteration'] - 1) / 3) % \count($this->captcha_service);
                $rContext->setSwapData($swapData);
                //                echo date('H:i:s')." ".$swapData['iteration'].": Sending captcha to ".$this->captcha_service[$swapData['captcha_service']]['host']."\n";
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service']]['host']) {
                    $params = [
                        'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                        'method' => 'base64',
                        'body' => $swapData['captcha_image'],
//                        'regsense' => 1,
                        'min_len' => 5,
                        'max_len' => 5,
                        'language' => 1,
                        'lang' => 'ru',
                    ];
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service']]['host'].'/in.php';
                    \curl_setopt($ch, \CURLOPT_POST, true);
                    \curl_setopt($ch, \CURLOPT_POSTFIELDS, \http_build_query($params));
                } else {
                    $params = [
                        'clientKey' => $this->captcha_service[$swapData['captcha_service']]['key'],
                        'task' => [
                            'type' => 'ImageToTextTask',
                            'body' => $swapData['captcha_image'],
//                            "case" => true,
                            'minLength' => 5,
                            'maxLength' => 5,
                        ],
                        'languagePool' => 'ru',
                    ];
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service']]['host'].'/createTask';
                    \curl_setopt($ch, \CURLOPT_POST, true);
                    \curl_setopt($ch, \CURLOPT_POSTFIELDS, \json_encode($params, \JSON_UNESCAPED_UNICODE));
                }
            } else {
                //                echo date('H:i:s')." ".$swapData['iteration'].": Getting captcha value from ".$this->captcha_service[$swapData['captcha_service']]['host']."\n";
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service']]['host']) {
                    $params = [
                        'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                        'action' => 'get',
                        'id' => $swapData['captcha_id'],
                    ];
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service']]['host'].'/res.php?'.\http_build_query($params);
                } else {
                    $params = [
                        'clientKey' => $this->captcha_service[$swapData['captcha_service']]['key'],
                        'taskId' => $swapData['captcha_id'],
                    ];
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service']]['host'].'/getTaskResult';
                    \curl_setopt($ch, \CURLOPT_POST, true);
                    \curl_setopt($ch, \CURLOPT_POSTFIELDS, \json_encode($params, \JSON_UNESCAPED_UNICODE));
                }
            }
            \curl_setopt($ch, \CURLOPT_URL, $url);
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 3);
            \curl_setopt($ch, \CURLOPT_PROXY, false);
        //            echo date('H:i:s')." ".$swapData['iteration'].": $url\n";
        //            var_dump($params);
        //            echo "\n";
        } else {
            $url = 'https://is.fssp.gov.ru/ajax_search';

            if (!isset($swapData['page'])) {
                $swapData['page'] = 1;
            }
            $params = [
                'system' => 'ip',
                'is[extended]' => 1,
                'nocache' => 1,
                'is[variant]' => 0,
                'is[region_id][0]' => isset($initData['region_id']) ? $initData['region_id'] : '-1',
                'is[last_name]' => '',
                'is[first_name]' => '',
                'is[drtr_name]' => '',
                'is[ip_number]' => '',
                'is[patronymic]' => '',
                'is[date]' => '',
                'is[address]' => '',
                'is[id_number]' => '',
                'is[id_type][0]' => '',
                'is[id_issuer]' => '',
                'callback' => $swapData['callback'],
//                'token' => '',
                'code' => $swapData['captcha_value'], // $swapData['session']->code,
                '_' => $swapData['_']++,
                'page' => $swapData['page'],
            ];
            $rContext->setSwapData($swapData);

            if (isset($initData['fssp_ip'])) {
                $params['is[variant]'] = '3';
                $params['is[ip_number]'] = $initData['fssp_ip'];
            } elseif (isset($initData['name'])) {
                $params['is[variant]'] = '2';
                $params['is[drtr_name]'] = $initData['name'];
                $params['is[address]'] = isset($initData['address']) ? $initData['address'] : '';
            } else {
                $params['is[variant]'] = '1';
                $params['is[last_name]'] = $initData['last_name'] ?: '-';
                $params['is[first_name]'] = $initData['first_name'];
                $params['is[patronymic]'] = isset($initData['patronymic']) ? $initData['patronymic'] : '';
                $params['is[date]'] = isset($initData['date']) ? \date('d.m.Y', \strtotime($initData['date'])) : '';
            }
            $url .= '?&'.\http_build_query($params);
            /*
                for( $j=1; $j <= 4; $j++){
                          if(file_exists('./logs/cookies/fssp_'.$j.'.txt') && ( ( time() - filemtime('./logs/cookies/fssp_'.$j.'.txt')) > 30 )){
                                break;
                      }
                }
                    if( $j > 3 ){
                              file_put_contents('./logs/cookies/fssp_error.txt', '4', FILE_APPEND);
                              return true;
                    }
                    else{
                             $swapData['cuka'] = 'fssp_'.$j.'.txt';
                    }
                    $rContext->setSwapData($swapData);
            */
            \curl_setopt($ch, \CURLOPT_URL, $url);
            \curl_setopt($ch, \CURLOPT_HTTPHEADER, [
                'Accept: */*',
                'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
                'Connection: keep-alive',
                'Sec-Fetch-Dest: script',
                'Sec-Fetch-Mode: no-cors',
                'Sec-Fetch-Site: same-site']);
            \curl_setopt($ch, \CURLOPT_ENCODING, '');
            //            curl_setopt($ch, CURLOPT_REFERER, 'https://fssp.gov.ru/');
            \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
            \curl_setopt($ch, \CURLOPT_COOKIEFILE, '');
            \curl_setopt($ch, \CURLOPT_HEADER, true);
            //            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            /*
                        curl_setopt($ch, CURLOPT_COOKIEFILE, './logs/cookies/fssp_'.$j.'.txt');
                        curl_setopt($ch, CURLOPT_COOKIEJAR, './logs/cookies/fssp_'.$j.'.txt');
            */
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 90);

            if ($swapData['session']->proxy) {
                \curl_setopt($ch, \CURLOPT_PROXY, $swapData['session']->proxy);
                if ($swapData['session']->proxy_auth) {
                    \curl_setopt($ch, \CURLOPT_PROXYUSERPWD, $swapData['session']->proxy_auth);
                    \curl_setopt($ch, \CURLOPT_PROXYAUTH, \CURLAUTH_ANY);
                }
            }
            //            echo date('H:i:s')." ".$swapData['iteration'].": $url\n";
        }

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(array $params, &$rContext)
    {
        global $mysqli;
        global $reqId;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        //        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
        //        $rContext->setSwapData($swapData);

        $content = \curl_multi_getcontent($rContext->getCurlHandler());

        if (!isset($swapData['captcha_image']) && \strlen($content) > 1000) {
            //            file_put_contents('./logs/fssp/'.$initData['checktype'].'_captcha_image_'.$swapData['iteration'].'_'.time().'.txt',/*curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".*/$content);
            $start = \strpos($content, '{');
            $finish = \strpos($content, '}');
            $content = \trim(\substr($content, $start, $finish - $start + 1));
            $res = \json_decode($content, true);
            if ($res && isset($res['image'])) {
                $captcha = \substr($res['image'], 23, \strlen($res['image']) - 23);
                $swapData['captcha_image'] = $captcha;
                $swapData['captcha_time'] = \microtime(true);
                unset($swapData['captcha_value']);
                $captcha = \base64_decode($captcha);
                //                file_put_contents('./logs/fssp/captcha_'.time().'.jpg', $captcha);
                --$swapData['iteration'];

                $value = neuro_post($captcha, 'fsspsitedecode');
                if ($value && 'ERROR' != \substr($value, 0, 5)) {
                    $swapData['captcha_value'] = $value;
                    //                    $rContext->setSleep(2);
                }
            } else {
                \file_put_contents('./logs/fssp/'.$initData['checktype'].'_captcha_image_err_'.$swapData['iteration'].'_'.\time().'.txt', /* curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n". */ $content);
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='invalidimage' WHERE id=".$swapData['session']->id);
                unset($swapData['session']);
            }
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
                    } elseif ($swapData['iteration'] > 20) {
                        $rContext->setFinished();
                        $rContext->setError('Ошибка получения капчи');
                        \file_put_contents('./logs/fssp/'.$initData['checktype'].'_captcha_err_'.$swapData['iteration'].'_'.\time().'.txt', /* curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n". */ $content."\r\n".$this->captcha_service[$swapData['captcha_service']]['host']);
                    }
                } else {
                    if (isset($res['taskId'])) {
                        $swapData['captcha_id'] = $res['taskId'];
                    } elseif ($swapData['iteration'] > 20) {
                        $rContext->setFinished();
                        $rContext->setError('Ошибка получения капчи');
                        \file_put_contents('./logs/fssp/'.$initData['checktype'].'_captcha_err_'.$swapData['iteration'].'_'.\time().'.txt', /* curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n". */ $content."\r\n".$this->captcha_service[$swapData['captcha_service']]['host']);
                    }
                }
                $rContext->setSleep(5);
            } else {
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service']]['host']) {
                    if ('CAPCHA_NOT_READY' == $content) {
                    } else {
                        if (false !== \strpos($content, 'OK|')) {
                            $swapData['captcha_value'] = \substr($content, 3);
                        //                            echo "Thread "."  Received captcha ID ".$swapData['captcha_id']."\n";
                        } else {
                            unset($swapData['captcha_id']);
                            unset($swapData['captcha_image']);
                            //                        } elseif ($swapData['iteration']>20) {
                            //                            $rContext->setFinished();
                            //                            $rContext->setError('Ошибка распознавания капчи');
                        }
                        //                        unset($swapData['captcha_id']);
                    }
                } else {
                    if (!$content) {
                    } elseif (isset($res['status']) && 'ready' !== $res['status']) {
                    } else {
                        if (isset($res['solution']['text'])) {
                            $swapData['captcha_value'] = $res['solution']['text'];
                        //                            echo "Thread "."  Received captcha ID ".$swapData['captcha_id']."\n";
                        } else {
                            unset($swapData['captcha_id']);
                            unset($swapData['captcha_image']);
                            //                        } elseif ($swapData['iteration']>20) {
                            //                            $rContext->setFinished();
                            //                            $rContext->setError('Ошибка распознавания капчи');
                        }
                        //                        unset($swapData['captcha_id']);
                    }
                }
                --$swapData['iteration'];
                $rContext->setSleep(1);
            }
            $rContext->setSwapData($swapData);

            return true;
        }

        $error = \curl_error($rContext->getCurlHandler());
        if ($error) {
            $rContext->setError($error);
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
        //        file_put_contents('./logs/fssp/fssp_'.time().'.cookies',$new_cookies);
        $mysqli->query("UPDATE isphere.session SET cookies='$new_cookies' WHERE id=".$swapData['session']->id);

        $full_content = \curl_multi_getcontent($rContext->getCurlHandler());

        $start = \strpos($full_content, '{');
        $finish = \strrpos($full_content, '}');
        if (false !== $start && false !== $finish) {
            $content = \substr($full_content, $start, $finish - $start + 1);
            $data = \json_decode($content, true);
            if (isset($data['data'])) {
                $content = $data['data'];
            }
        //            file_put_contents('./logs/fssp/search_'.time().'.htm', curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content."\n\n".$swapData['iteration']);
        } elseif ($full_content) {
            $content = $full_content;
            \file_put_contents('./logs/fssp/search_err_'.\time().'.htm', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content."\n\n".$swapData['iteration']);
        } else {
            $content = $full_content;
            if (isset($swapData['session'])) {
                //                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='forbidden' WHERE id=" . $swapData['session']->id);
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='empty' WHERE id=".$swapData['session']->id);
            }
            unset($swapData['session']);
            $rContext->setSwapData($swapData);
        }

        if (\preg_match('/превысили лимит/', $content)) {
            if (isset($swapData['session'])) {
                //                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='forbidden' WHERE id=" . $swapData['session']->id);
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='limit' WHERE id=".$swapData['session']->id);
            }
            unset($swapData['session']);
            $rContext->setSwapData($swapData);
        }
        if (\preg_match('/403 Forbidden/', $content)) {
            if (isset($swapData['session'])) {
                //                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='forbidden' WHERE id=" . $swapData['session']->id);
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 6 hour),sessionstatusid=6,statuscode='forbidden' WHERE id=".$swapData['session']->id);
            }
            unset($swapData['session']);
            $rContext->setSwapData($swapData);
        }
        if (\preg_match('/503 Service Temporarily Unavailable/', $content)) {
            if (isset($swapData['session'])) {
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='unavailable' WHERE id=".$swapData['session']->id);
            }
            unset($swapData['session']);
            $rContext->setSwapData($swapData);
        }
        if (\preg_match('/уже обрабатывается/', $content)) {
            \file_put_contents('./logs/fssp/search_err_'.\time().'.htm', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content."\n\niteration=".$swapData['iteration']."\nsessionid=".$swapData['session']->id."\nrequestid=".$reqId);
            /*
                        if (isset($swapData['session']))
            //                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='forbidden' WHERE id=" . $swapData['session']->id);
                            $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='processing' WHERE id=" . $swapData['session']->id);
            */
            unset($swapData['session']);
            $rContext->setSwapData($swapData);
            $rContext->setSleep(5);

            return true;
        }

        if (\preg_match('/<div id="captcha-popup"/', $content)) {
            /*
                        if (isset($swapData['session'])) {
            //                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='exhausted' WHERE used>1 AND id=" . $swapData['session']->id);
                            $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=4,statuscode='invalidcaptcha' WHERE id=" . $swapData['session']->id);
                        }
                        unset($swapData['session']);
            */
            if (isset($swapData['captcha_id']) && isset($swapData['captcha_service']) /* && $this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com' */) {
                //                $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),'".date('Y-m-d H:i:s',$swapData['captcha_time'])."',3,4,'invalidcaptcha','".$swapData['captcha_value']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
            }
            if (isset($swapData['captcha_image']) && isset($swapData['captcha_value'])) {
                \file_put_contents('./logs/fssp/captcha/bad/'.$swapData['captcha_value'].'.jpg', \base64_decode($swapData['captcha_image']));
            }

            $prefix = 'data:image/jpeg;base64,';
            $start = \strpos($content, $prefix);
            $captcha = false;
            if (false !== $start) {
                $captcha = \substr($content, $start + \strlen($prefix));
                $finish = \strpos($captcha, '=');
                if (false !== $finish) {
                    $captcha = \substr($captcha, 0, $finish + 1);
                    $swapData['captcha_image'] = $captcha;
                    $swapData['captcha_time'] = \microtime(true);
                    unset($swapData['captcha_value']);
                    unset($swapData['captcha_id']);
                    $captcha = \base64_decode($captcha);
                    //                    file_put_contents('./logs/fssp/captcha_'.time().'.jpg', $captcha);

                    $value = neuro_post($captcha, 'fsspsitedecode');
                    if ($value && 'ERROR' != \substr($value, 0, 5)) {
                        $swapData['captcha_value'] = $value;
                        //                        $rContext->setSleep(2);
                    }
                }
            }
            $rContext->setSwapData($swapData);

            return true;
        } elseif (\preg_match_all('/<tr class="[^"]*">(.+?)<\/tr>/msu', $content, $records)) {
            if (isset($swapData['captcha_id']) && isset($swapData['captcha_service']) /* && $this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com' */) {
                $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),'".\date('Y-m-d H:i:s', $swapData['captcha_time'])."',3,3,'success','".$swapData['captcha_value']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
            }
            if (isset($swapData['captcha_value'])) {
                \file_put_contents('./logs/fssp/captcha/good/'.$swapData['captcha_value'].'.jpg', \base64_decode($swapData['captcha_image']));
            }

            $mysqli->query("UPDATE isphere.session SET statuscode='success',success=ifnull(success,0)+1 WHERE id=".$swapData['session']->id);
            $resultData = isset($swapData['result']) ? $swapData['result'] : new ResultDataList();

            foreach ($records[1] as $record) {
                if (\preg_match_all('/<td[^>]*>(.*?)<\/td>/msu', $record, $cols)) {
                    if (\count($cols[1]) < 8) {
                        continue;
                    }

                    $record = [];

                    $debtor = \preg_split('/<br[^>]*>/', $cols[1][0]);
                    $cases = \preg_split('/<br[^>]*>/', $cols[1][1]);
                    $case = \explode(' от ', $cases[0]);
                    $doc = \preg_split('/<br[^>]*>/', $cols[1][2]);
                    $close = \preg_split('/<br[^>]*>/', $cols[1][3]);
                    $subjects = \preg_split('/<br[^>]*>/', $cols[1][5]);
                    if (\preg_match('/^(.*?)Исполнительский сбор:/', $subjects[0], $matches) || \preg_match('/^(.*?)Задолженность по расходам:/', $subjects[0], $matches) || \preg_match('/^(.*?)Штраф СПИ:/', $subjects[0], $matches)) {
                        $subject = [$matches[1]];
                        $subjects[0] = \substr($subjects[0], \strlen($matches[1]));
                    } else {
                        $subject = \explode(': ', $subjects[0]);
                        \array_shift($subjects);
                    }
                    $department = \preg_split('/<br[^>]*>/', $cols[1][6]);
                    $bailiff = \preg_split('/<br[^>]*>/', $cols[1][7]);

                    foreach ($cols[1] as &$row) {
                        $row = \strip_tags($row);
                    }

                    $record['name'] = new ResultDataField('string', 'Debtor', \strtr(\trim($debtor[0]), ["\u{a0}" => '']), 'Должник', 'Должник');
                    if (\count($debtor) > 1 && \preg_match("/^[\d\.\-]+$/", \trim($debtor[1]))) {
                        $record['debtor_birthday'] = new ResultDataField('string', 'DebtorBirthday', \trim($debtor[1]), 'Дата рождения', 'Дата рождения должника');
                        if (\count($debtor) > 2) {
                            $record['debtor_birthplace'] = new ResultDataField('string', 'DebtorBirthplace', \trim($debtor[2]), 'Место рождения', 'Место рождения должника');
                        }
                    } elseif (\count($debtor) > 1) {
                        $record['debtor_address'] = new ResultDataField('string', 'DebtorAddress', \trim($debtor[1]), 'Адрес', 'Адрес должника');
                    }

                    $record['case_num'] = new ResultDataField('string', 'CaseNumber', $case[0], 'Номер ИП', 'Номер исполнительного производства');
                    $record['case_date'] = new ResultDataField('string', 'CaseDate', \trim($case[1]), 'Дата ИП', 'Дата исполнительного производства');
                    if (\count($cases) > 1) {
                        $record['summary_case_num'] = new ResultDataField('string', 'SummaryCaseNumber', \trim($cases[1]), 'Номер сводного ИП', 'Номер сводного исполнительного производства');
                    }

                    $record['doc_text'] = new ResultDataField('string', 'DocText', \implode(' ', $doc), 'Реквизиты документа', 'Реквизиты исполнительного документа');
                    $record['doc_type'] = new ResultDataField('string', 'DocType', \strip_tags(\substr($doc[0], 0, \strpos($doc[0], ' от '))), 'Вид документа', 'Вид исполнительного документа');
                    $doc[0] = \substr($doc[0], \strpos($doc[0], ' от ') + 6);
                    if (\preg_match("/^([0-9\.]+) /", $doc[0], $matches)) {
                        $record['doc_date'] = new ResultDataField('string', 'DocDate', $matches[1], 'Дата документа', 'Дата исполнительного документа');
                    }
                    if (\strpos($doc[0], '№') && \trim(\substr($doc[0], \strpos($doc[0], '№') + 4))) {
                        $record['doc_num'] = new ResultDataField('string', 'DocNumber', \trim(\substr($doc[0], \strpos($doc[0], '№') + 4)), 'Номер документа', 'Номер исполнительного документа');
                    }
                    $record['doc_issuer'] = new ResultDataField('string', 'DocIssuer', $doc[\count($doc) - 1], 'Орган', 'Орган, выдавший исполнительный документ');
                    if ($h = \strpos($doc[0], "href='http")) {
                        $h += 6;
                        $record['doc_url'] = new ResultDataField('url', 'DocURL', \substr($doc[0], $h, \strpos($doc[0], "'", $h) - $h), 'URL документа', 'URL исполнительного документа');
                    }
                    //                    if (strlen($doc)>2)
                    //                        $record['doc_title'] = new ResultDataField('string','DocTitle', $doc[1], 'Название документа', 'Название исполнительного документа');

                    if (isset($close[0]) && $close[0]) {
                        $record['close_date'] = new ResultDataField('string', 'CloseDate', $close[0], 'Дата завершения', 'Дата завершения исполнительного производства');
                    }
                    if (isset($close[1])) {
                        $record['close_reason'] = new ResultDataField('string', 'CloseReason', $close[1].(isset($close[2]) ? ' '.$close[2] : '').(isset($close[3]) ? ' '.$close[3] : ''), 'Причина завершения', 'Причина завершения исполнительного производства');
                        $record['close_reason1'] = new ResultDataField('string', 'CloseReason1', \substr($close[1], \strpos($close[1], ' ') + 1), 'Причина завершения - статья', 'Причина завершения исполнительного производства - статья');
                    }
                    if (isset($close[2])) {
                        $record['close_reason2'] = new ResultDataField('string', 'CloseReason2', \substr($close[2], \strpos($close[2], ' ') + 1), 'Причина завершения - часть', 'Причина завершения исполнительного производства - часть');
                    }
                    if (isset($close[3])) {
                        $record['close_reason3'] = new ResultDataField('string', 'CloseReason3', \substr($close[3], \strpos($close[3], ' ') + 1), 'Причина завершения - пункт', 'Причина завершения исполнительного производства - пункт');
                    }

                    if ($subject[0]) {
                        $record['subject'] = new ResultDataField('string', 'Subject', $subject[0], 'Предмет исполнения', 'Предмет исполнения');
                    }
                    if (\count($subject) > 1) {
                        $record['total'] = new ResultDataField('float', 'Total', \substr($subject[1], 0, \strpos($subject[1], ' ')), 'Сумма задолженности', 'Сумма задолженности');
                    }
                    foreach ($subjects as $subject) {
                        $subject_b = \explode(':', \trim($subject));
                        $name = [
                            'Общая сумма задолженности' => ['', false, 'Сумма задолженности'],
                            'Исполнительский сбор' => ['Bailiff', 'Сбор исполнителя', 'Сумма сбора исполнителя'],
                            'Задолженность по расходам' => ['Costs', 'Расходы исполнителя', 'Сумма расходов исполнителя'],
                            'Штраф СПИ' => ['Fine', 'Штраф', 'Сумма штрафа'],
                        ];
                        if (isset($name[$subject_b[0]])) {
                            $n = $name[$subject_b[0]][0];
                            $s = $name[$subject_b[0]][1];
                            $t = $name[$subject_b[0]][2];
                            if ($s) {
                                $record[$n.'Subject'] = new ResultDataField('string', $n.'Subject', $subject_b[0], $s, $s);
                            }
                            if ($t && \count($subject_b) > 1) {
                                $record[$n.'Total'] = new ResultDataField('float', $n.'Total', \trim(\strtr($subject_b[1], ['руб.' => ''])), $t, $t);
                            }
                        }
                    }

                    $record['department'] = new ResultDataField('string', 'Department', $department[0], 'Отдел', 'Отдел судебных приставов');
                    if (isset($department[1])) {
                        $record['department_address'] = new ResultDataField('string', 'DepartmentAddress', $department[1], 'Адрес отдела', 'Адрес отдела судебных приставов');
                    }

                    $record['bailiff'] = new ResultDataField('string', 'Bailiff', $bailiff[0], 'Пристав', 'Судебный пристав-исполнитель');
                    foreach ($bailiff as $i => $bailiff_phone) {
                        if ($bailiff_phone && $i && $bailiff_phone != $bailiff[$i - 1]) {
                            $record['bailiff_phone'.$i] = new ResultDataField('string', 'BailiffPhone', \strip_tags($bailiff_phone), 'Телефон', 'Телефон судебного пристава-исполнителя');
                        }
                    }

                    $resultData->addResult($record);
                }
            }

            if (\preg_match_all('/page=([\d]+)\">[\d]+</', $content, $matches) && ($swapData['page'] < (int) $matches[1][\count($matches[1]) - 1]) && $swapData['page'] < 10) {
                unset($swapData['captcha_image']);
                unset($swapData['captcha_value']);
                unset($swapData['captcha_id']);
                ++$swapData['page'];
                --$swapData['iteration'];
                $swapData['result'] = $resultData;
                $rContext->setSwapData($swapData);
                $rContext->setSleep(2);
            } else {
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            }

            return true;
        } elseif (\preg_match('/ничего не найдено/', $content)) {
            if (isset($swapData['captcha_id']) && isset($swapData['captcha_service']) /* && $this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com' */) {
                $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),'".\date('Y-m-d H:i:s', $swapData['captcha_time'])."',3,3,'success','".$swapData['captcha_value']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
            }
            if (isset($swapData['captcha_value'])) {
                \file_put_contents('./logs/fssp/captcha/good/'.$swapData['captcha_value'].'.jpg', \base64_decode($swapData['captcha_image']));
            }

            $mysqli->query("UPDATE isphere.session SET statuscode='success',success=ifnull(success,0)+1 WHERE id=".$swapData['session']->id);
            $resultData = new ResultDataList();

            $rContext->setResultData($resultData);
            $rContext->setFinished();

            return true;
        }

        if (isset($initData['last_name']) && $initData['last_name'] && ($initData['last_name'] == $initData['first_name'] || (isset($initData['patronymic']) && $initData['last_name'] == $initData['patronymic']) || (isset($initData['patronymic']) && $initData['first_name'] == $initData['patronymic'])) && $swapData['iteration'] >= 3) {
            $rContext->setFinished();
            $rContext->setError($error ?: 'ФССП не может обработать запрос с совпадением полей в ФИО');

            return false;
        }

        if ($swapData['iteration'] >= 50) {
            $rContext->setFinished();
            $rContext->setError('' == $error ? 'Превышено количество попыток получения ответа' : $error);

            return false;
        }

        $rContext->setSwapData($swapData);
        $rContext->setSleep(1);

        return true;
    }
}

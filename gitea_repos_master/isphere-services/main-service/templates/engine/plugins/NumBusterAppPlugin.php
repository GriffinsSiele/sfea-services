<?php

class NumBusterAppPlugin implements PluginInterface
{
    public function getName()
    {
        return 'NumBuster';
    }

    public function getTitle()
    {
        return 'Поиск телефона в NumBuster';
    }

    public function getSessionData(array $params)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $reqId = $params['_reqId'];
        $sessionData = null;
        $mysqli->executeStatement('UPDATE session s SET request_id='.$reqId." WHERE request_id IS NULL AND (sessionstatusid=2 OR statuscode='limitexceed') AND sourceid=12 AND token>'' AND unix_timestamp(now())-unix_timestamp(lasttime)>15 AND (substr(data,1,1)<>'r' OR unix_timestamp(now())-unix_timestamp(lasttime)>3600) ORDER BY lasttime limit 1");
        $result = $mysqli->executeQuery("SELECT id,cookies,server,starttime,lasttime,statuscode,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM session s WHERE sourceid=12 AND request_id=".$reqId.' ORDER BY lasttime limit 1');
        if ($result) {
            $row = $result->fetchAssociative();
            if ($row) {
                $sessionData = new \stdClass();
                $sessionData->id = $row['id'];
                $sessionData->code = $row['captcha'];
                $sessionData->token = $row['token'];
                $sessionData->starttime = $row['starttime'];
                $sessionData->lasttime = $row['lasttime'];
                $sessionData->status = $row['statuscode'];
                $sessionData->cookies = $row['cookies'];
                $sessionData->host = $row['server'];
                $sessionData->proxyid = $row['proxyid'];
                $sessionData->proxy = $row['proxy'];
                $sessionData->proxy_auth = \strlen($row['proxy_auth']) > 1 ? $row['proxy_auth'] : false;
                $mysqli->executeStatement("UPDATE session SET lasttime=now(),used=ifnull(used,0)+1,statuscode='used',request_id=NULL WHERE request_id=".$reqId.' AND id='.$sessionData->id);
                //                $mysqli->query("UPDATE session SET endtime=now(),sessionstatusid=3 WHERE used>=1 AND id=".$sessionData->id);
                if (!$row['proxyid']) {
                    $result = $mysqli->executeQuery("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM proxy WHERE enabled=1 AND status=1 AND proxygroup=1 AND id NOT IN (SELECT proxyid FROM session WHERE sourceid=12 AND proxyid IS NOT NULL) ORDER BY lasttime limit 1");
                    if ($result) {
                        $row = $result->fetchAssociative();
                        if ($row) {
                            $sessionData->proxyid = $row['proxyid'];
                            $sessionData->proxy = $row['proxy'];
                            $sessionData->proxy_auth = \strlen($row['proxy_auth']) > 1 ? $row['proxy_auth'] : false;
                            //                            $mysqli->query("UPDATE proxy SET lasttime=now() WHERE id=".$row['proxyid']);
                            $mysqli->executeStatement('UPDATE session SET proxyid='.$row['proxyid'].' WHERE id='.$sessionData->id);
                        }
                    }
                }
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        if (!isset($initData['phone'])) {
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
        //              $rContext->setError('Поиск производится только по мобильным телефонам в коде 9xx');
                      return false;
                  }
        */
        // //////////////////////////////////////////////////////////////////////////////////////////////////
        /*
                global $userId;
                if ($userId==915 || $userId==340 || $userId==3178 || $userId==975) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                    return false;
                }
        */
        $swapData['iteration'] = !isset($swapData['iteration']) ? 1 : $swapData['iteration'] + 1;
        if (!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData($params);
            if (isset($swapData['session'])) {
                $rContext->setSwapData($swapData);
            }
        }
        if (!$swapData['session']) {
            if (isset($swapData['iteration']) && $swapData['iteration'] >= 30) {
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
            } else {
                //                (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
                $rContext->setSwapData($swapData);
                /*
                                if($swapData['iteration']>30) {
                                    $rContext->setError('Сервис временно недоступен');
                                    $rContext->setFinished();
                                    return false;
                                }
                */
                $rContext->setSleep(1);
            }

            return false;
        }
        // //////////////////////////////////////////////////////////////////////////////////////////////////
        $ch = $rContext->getCurlHandler();
        $buf = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $cnonce = '';
        $len = 30 + \rand(0, 19);
        for ($i = 0; $i < $len; ++$i) {
            $cnonce .= $buf[\rand(0, 61)];
        }
        $swapData['test'] = false;
        // (substr($swapData['session']->lasttime,0,13)<date('Y-m-d H') && date('H')>='09');
        $rContext->setSwapData($swapData);
        $sol = '0woz2wTimes9izs0vFQjLmwqqSzAPNFtmWNcbOL6xJva5Molyb';
        $access_token = $swapData['session']->token;
        $locale = 'en';
        //        $locale = 'ru';
        $timestamp = \time();
        if (\substr($swapData['session']->lasttime, 0, 10) < \date('Y-m-d')) {
            $swapData['session']->host = '';
        }
        if ('' == $swapData['session']->host) {
            //            $host = 'api.numbuster.com';
            //            $host = '96969696969696696696699996969699996969699.com';
            //            $host = '969696969696966966669999696969999696969.com';
            //            $host = '96969696969696696666999696969999696969.com';
            //            $host = 'cbb5723fed9575909f113b17c09bc4bb3b6466c.de';
            //            $host = 'b0e6cc97e9a9170e0234ce5912e24a4cb8ff9090737a645e29.de';
            //            $host = 'a7bdece4963d6d4969981d84bcc14582b3cc880e72b52.cc';
            $host = '7d7992e49365310ec5e997241c6312bd.com';
            $url = 'https://'.$host.'/api/v6/api_domain';
        } else {
            $host = $swapData['session']->host;
            //            if ($swapData['iteration']>=3 && rand(0,1))
            $host = '7d7992e49365310ec5e997241c6312bd.com';
            $phone = $swapData['test'] ? '78005555550' : $initData['phone'];
            //            $source = 'GET'.$host.'/api/v6/old/phone/'.$phone.'access_token='.$access_token.'&cnonce='.$cnonce.'&locale='.$locale.'&timestamp='.$timestamp;
            //            $source = 'GET'.$host.'/api/v8/search/'.$phone.'access_token='.$access_token.'&cnonce='.$cnonce.'&paidSearch=0&timestamp='.$timestamp;
            //            $source = 'GET'.$host.'/api/v13/search/'.$phone.'access_token='.$access_token.'&cnonce='.$cnonce.'&locale='.$locale.'&paidSearch=0&timestamp='.$timestamp;
            //            $source = 'GET'.$host.'/api/v13/call/outgoing'.'access_token='.$access_token.'&cnonce='.$cnonce.'&locale='.$locale.'&phone='.$phone.'&timestamp='.$timestamp;
            $source = 'GET'.$host.'/api/v13/call/incomingaccess_token='.$access_token.'&cnonce='.$cnonce.'&locale='.$locale.'&phone='.$phone.'&timestamp='.$timestamp;
            $signature = \hash('sha256', $source.$sol);
            //            $url = 'https://'.$host.'/api/v6/old/phone/'.$phone.'?access_token='.$access_token.'&locale='.$locale.'&timestamp='.$timestamp.'&signature='.$signature.'&cnonce='.$cnonce;
            //            $url = 'https://'.$host.'/api/v8/search/'.$phone.'?access_token='.$access_token.'&paidSearch=0&timestamp='.$timestamp.'&signature='.$signature.'&cnonce='.$cnonce;
            //            $url = 'https://'.$host.'/api/v13/search/'.$phone.'?access_token='.$access_token.'&locale='.$locale.'&timestamp='.$timestamp.'&signature='.$signature.'&cnonce='.$cnonce.'&paidSearch=0';
            //            $url = 'https://'.$host.'/api/v13/call/outgoing'.'?phone='.$phone.'&access_token='.$access_token.'&locale='.$locale.'&timestamp='.$timestamp.'&signature='.$signature.'&cnonce='.$cnonce;
            $url = 'https://'.$host.'/api/v13/call/incoming?phone='.$phone.'&access_token='.$access_token.'&locale='.$locale.'&timestamp='.$timestamp.'&signature='.$signature.'&cnonce='.$cnonce;
        }
        $header = [
            'Host: '.$host,
            //            'Accept: application/json',
            'Accept-Encoding: gzip',
            'Connection: keep-alive',
            'Content-length: 0',
        ];
        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);
        //        curl_setopt($ch, CURLOPT_HEADER, true);
        //        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        \curl_setopt($ch, \CURLOPT_USERAGENT, 'okhttp/3.12.1');
        \curl_setopt($ch, \CURLOPT_ENCODING, '');
        \curl_setopt($ch, \CURLOPT_TIMEOUT, 'success' == $swapData['session']->status || 0 == \rand(0, 4) ? 20 : 5);
        \curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, false);
        \curl_setopt($ch, \CURLOPT_SSL_VERIFYPEER, false);
        if ($swapData['session']->proxy) {
            \curl_setopt($ch, \CURLOPT_PROXY, $swapData['session']->proxy);
            if ($swapData['session']->proxy_auth) {
                \curl_setopt($ch, \CURLOPT_PROXYUSERPWD, $swapData['session']->proxy_auth);
                \curl_setopt($ch, \CURLOPT_PROXYAUTH, \CURLAUTH_ANY);
            }
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
        $error = false;
        $content = \curl_multi_getcontent($rContext->getCurlHandler());
        if (!$content) {
            if ($swapData['iteration'] >= 3) {
                $error = \curl_error($rContext->getCurlHandler());
            }
            $http_code = \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HTTP_CODE);
            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/numbuster/numbuster_empty_'.$swapData['iteration'].'_'.\time().'.txt', $http_code."\n".\curl_error($rContext->getCurlHandler()));
            if ($http_code >= 500) {
                if ($swapData['iteration'] >= 2) {
                    $error = 'Внутренняя ошибка источника';
                }
                $mysqli->executeStatement("UPDATE session SET statuscode='error' WHERE id=".$swapData['session']->id);
            } else {
                $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='empty' WHERE id=".$swapData['session']->id);
                $mysqli->executeStatement('INSERT INTO session12 SELECT * FROM session WHERE successtime<DATE_SUB(now(),INTERVAL 6 HOUR) AND id='.$swapData['session']->id);
                $mysqli->executeStatement('UPDATE session SET sessionstatusid=4,endtime=now() WHERE id IN (SELECT id FROM session12) AND id='.$swapData['session']->id);
            }
            unset($swapData['session']);
            /*
                    } elseif(!isset($swapData['v6'])) {
            //            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/numbuster/numbuster_old_'.($swapData['test']?'test_':'').$swapData['iteration'].'_'.time().'.txt',$content);
                        $res = json_decode($content, true);
                        if($res && isset($res['phones'])){
                            $swapData['v6'] = true;
                            $swapData['iteration']--;
                            $rContext->setSleep(10);
                            $rContext->setSwapData($swapData);
                            return true;
                        } elseif($res && isset($res['data']) && $res['data']=="wrong phone") {
                            $error = "Некорректный номер телефона";
                            $mysqli->query("UPDATE session SET success=ifnull(success,0)+1,statuscode='success',successtime=now() WHERE id=".$swapData['session']->id);
                            unset($swapData['session']);
                        } else {
                            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/numbuster/numbuster_err_'.$swapData['iteration'].'_'.time().'.txt',$content);
                            if ($swapData['iteration']>2) {
                                $error = strpos($content,'nginx')?"Сервис временно недоступен":"Некорректный ответ";
                            }
                            $mysqli->query("UPDATE session SET unlocktime=date_add(now(),interval 10 minute),sessionstatusid=6,statuscode='invalidanswer' WHERE id=" . $swapData['session']->id);
                            unset($swapData['session']);
                        }
            */
        } elseif ('' == $swapData['session']->host) {
            //            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/numbuster/numbuster_host_'.$swapData['iteration'].'_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
            $start = \strpos($content, '{');
            $content = \trim(\substr($content, $start, \strlen($content) - $start + 1));
            $res = \json_decode($content, true);
            if ($res && isset($res['status']) && 'success' == $res['status'] && isset($res['data'])) {
                $host = \strtr($res['data'], ['https://' => '', 'http://' => '']);
                $swapData['session']->host = $host;
                $swapData['session']->lasttime = \date('Y-m-d H:i:s');
                $mysqli->executeStatement("UPDATE session SET lasttime=now(),server='{$host}' WHERE id=".$swapData['session']->id);
            }
        } else {
            //            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/numbuster/numbuster_'.($swapData['test']?'test_':'').$swapData['iteration'].'_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
            $start = \strpos($content, '{');
            $content = \trim(\substr($content, $start, \strlen($content) - $start + 1));
            $res = \json_decode($content, true);
            if ($res && isset($res['status']) && 'success' == $res['status'] && \is_array($res['data'])) {
                $res = $res['data'];
            }
            if ($res && isset($res['metrics'])) {
                $resultData = new ResultDataList();
                $data = [];
                $name = '';
                $profile = isset($res['name']) && \is_array($res['name']) ? $res['name'] : (isset($res['averageProfile']) ? $res['averageProfile'] : (isset($res['profile']) ? $res['profile'] : []));
                if (isset($profile['firstName'])) {
                    if ('Please Update App' == $profile['firstName']) {
                        $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 1 year),sessionstatusid=6,statuscode='needupdate' WHERE id=".$swapData['session']->id);
                        unset($swapData['session']);
                        $rContext->setSwapData($swapData);

                        return false;
                    } elseif ('Covers up info' == $profile['firstName']) {
                        $name = 'Информация скрыта';
                    } else {
                        $data['first_name'] = new ResultDataField('string', 'first_name', \trim($profile['firstName']), 'Имя', 'Имя');
                        $name = \trim($profile['firstName']);
                    }
                }
                if (isset($profile['lastName']) && \trim($profile['lastName'])) {
                    $data['last_name'] = new ResultDataField('string', 'last_name', \trim($profile['lastName']), 'Фамилия', 'Фамилия');
                    $name = \trim($name.' '.\trim($profile['lastName']));
                }
                if (!$name && isset($res['name']) && !\is_array($res['name'])) {
                    $name = \trim($res['name']);
                    $split_name = \explode(' ', $name);
                    $first_name = '';
                    for ($i = 0; $i < \count($split_name) - 1; ++$i) {
                        $first_name = \trim($first_name.' '.$split_name[$i]);
                    }
                    $last_name = $split_name[\count($split_name) - 1];
                    if ('.' == $last_name) {
                        $last_name = '';
                    }
                    $data['first_name'] = new ResultDataField('string', 'first_name', $first_name, 'Имя', 'Имя');
                    $data['last_name'] = new ResultDataField('string', 'last_name', $last_name, 'Фамилия', 'Фамилия');
                }
                if ($name) {
                    $data['name'] = new ResultDataField('string', 'name', $name, 'Полное имя', 'Полное имя');
                    if (isset($res['metrics']['contactsCount']) && $res['metrics']['contactsCount']) {
                        $data['names_count'] = new ResultDataField('string', 'names_count', $res['metrics']['contactsCount'], 'Количество имен', 'Количество возможных имен');
                    }
                    if (isset($res['metrics']['commentsCount']) && $res['metrics']['commentsCount']) {
                        $data['comments_count'] = new ResultDataField('string', 'comments_count', $res['metrics']['commentsCount'], 'Количество комментариев', 'Количество комментариев');
                    }
                    if (isset($res['metrics']['isVerified'])) {
                        $data['isverified'] = new ResultDataField('string', 'IsVerified', $res['metrics']['isVerified'] ? 'Да' : 'Нет', 'Проверен', 'Проверен');
                    }
                    if (isset($res['metrics']['isUnwanted'])) {
                        $data['isunwanted'] = new ResultDataField('string', 'IsUnwanted', $res['metrics']['isUnwanted'] ? 'Да' : 'Нет', 'Нежелательный', 'Нежелательный');
                    }
                    if (isset($res['metrics']['isHidden'])) {
                        $data['ishidden'] = new ResultDataField('string', 'IsHidden', $res['metrics']['isHidden'] ? 'Да' : 'Нет', 'Скрыт', 'Скрыт');
                    }
                    /*
                                        if (isset($res['metrics']['isPro'])) {
                                            $data['ispro'] = new ResultDataField('string','IsPro',$res['metrics']['isPro']?'Да':'Нет','Аккаунт Pro','Аккаунт Pro');
                                        }
                                        if (isset($res['rating']['likes'])) {
                                            $data['likes'] = new ResultDataField('string','likes',$res['rating']['likes'],'Likes','Likes');
                                        }
                                        if (isset($res['rating']['dislikes'])) {
                                            $data['dislikes'] = new ResultDataField('string','dislikes',$res['rating']['dislikes'],'Dislikes','Dislikes');
                                        }
                    */
                }
                if (isset($res['common']['leftRequests']) && $res['common']['leftRequests'] <= 1) {
                    $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(str_to_date('".\date('Y-m-d 03:00:00', \time() - 3 * 60 * 60)."', '%Y-%m-%d %H:%i:%s'),interval 1 day),sessionstatusid=6,statuscode='limitexceed' WHERE sessionstatusid=2 AND id=".$swapData['session']->id);
                    unset($swapData['session']);
                } elseif ($swapData['test']) {
                    $swapData['session']->lasttime = \date('Y-m-d H:i:s');
                    $swapData['test'] = false;
                    if ('Сбербанка Линия' != $name) {
                        //    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/numbuster/dead/'.$swapData['session']->id.'_'.time().'.txt', $content);
                        $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 1 month),sessionstatusid=6,statuscode='garbage' WHERE id=".$swapData['session']->id);
                        unset($swapData['session']);
                    }
                } else {
                    if (\count($data)) {
                        $resultData->addResult($data);
                    }
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->executeStatement("UPDATE session SET success=ifnull(success,0)+1,statuscode='success',successtime=now() WHERE id=".$swapData['session']->id);

                    return true;
                }
            } elseif ($res && isset($res['data']) && 'wrong phone' == $res['data']) {
                $error = 'Некорректный номер телефона';
                $mysqli->executeStatement("UPDATE session SET success=ifnull(success,0)+1,statuscode='success',successtime=now() WHERE id=".$swapData['session']->id);
                unset($swapData['session']);
            } elseif ($res && isset($res['message']) && 'Unknown error' == $res['message']) {
                $error = 'Внутренняя ошибка источника';
                $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='unknown' WHERE id=".$swapData['session']->id);
                unset($swapData['session']);
            } else {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/numbuster/numbuster_err_'.$swapData['iteration'].'_'.\time().'.txt', $content);
                if ($swapData['iteration'] > 2) {
                    $error = !$content || \strpos($content, 'nginx') || \strpos($content, 'Error Occurred') ? 'Сервис не отвечает' : 'Некорректный ответ';
                }
                if (\strpos($content, '414 URI Too Long')) {
                    $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 1 year),sessionstatusid=6,statuscode='uritoolong' WHERE id=".$swapData['session']->id);
                    $mysqli->executeStatement('INSERT INTO session12 SELECT * FROM session WHERE id='.$swapData['session']->id);
                    $mysqli->executeStatement('UPDATE session SET sessionstatusid=4,endtime=now() WHERE id='.$swapData['session']->id);
                } else {
                    $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 10 minute),sessionstatusid=6,statuscode='invalidanswer' WHERE id=".$swapData['session']->id);
                }
                unset($swapData['session']);
            }
        }
        $rContext->setSwapData($swapData);
        if (!$error && isset($swapData['iteration']) && $swapData['iteration'] >= 20) {
            $error = 'Превышено количество попыток получения ответа';
        }
        if ($error) {
            //            $rContext->setResultData(new ResultDataList());
            $rContext->setError($error);
            $rContext->setFinished();

            return false;
        }
        //        $rContext->setError('Сервис временно недоступен');
        //        $rContext->setResultData(new ResultDataList());
        //        $rContext->setFinished();
        $rContext->setSleep(1);

        return true;
    }
}

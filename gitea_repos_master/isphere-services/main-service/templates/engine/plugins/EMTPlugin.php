<?php

class EMTPlugin implements PluginInterface
{
    private $sitekey = 'd5638496-1c51-4ed4-b44c-efc34f3c957c';
    private $captcha_service = [['host' => 'api.capmonster.cloud', 'key' => 'afb26cbb248d650ea8b8d88822984242'], ['host' => 'rucaptcha.com', 'key' => 'd167c71a9278312f184f17caa4e71050']];
    private $captcha_threads = 1;

    public function getName()
    {
        return 'emt';
    }

    public function getTitle()
    {
        return 'Поиск в EmobileTracker';
    }

    public function getSessionData(array $params, $sourceid = 24)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $reqId = $params['_reqId'];
        $sessionData = null;
        $mysqli->executeStatement('UPDATE session s SET request_id='.$reqId." WHERE request_id IS NULL AND sessionstatusid=2 AND sourceid={$sourceid} AND captcha>'' AND unix_timestamp(now())-unix_timestamp(captchatime)<110 AND (statuscode<>'used' OR lasttime<from_unixtime(unix_timestamp(now())-60)) ORDER BY lasttime limit 1");
        $result = $mysqli->executeQuery("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM session s WHERE sourceid=24 AND request_id=".$reqId.' ORDER BY lasttime limit 1');
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
                $mysqli->executeStatement("UPDATE session SET lasttime=now(),endtime=now(),sessionstatusid=3,statuscode='used',used=ifnull(used,0)+1,request_id=NULL WHERE id=".$sessionData->id);
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
        if (!isset($initData['phone'])) {
            $rContext->setFinished();
            $rContext->setError('Не задан номер телефона');

            return false;
        }
        //        if (strlen($initData['phone'])==10)
        //            $initData['phone']='7'.$initData['phone'];
        //        if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
        //            $initData['phone']='7'.substr($initData['phone'],1);
        $country = false;
        if (\preg_match('/7[3489]/', \substr($initData['phone'], 0, 2))) {
            $country = 'RU';
        }
        if (\preg_match('/7[67]/', \substr($initData['phone'], 0, 2))) {
            $country = 'KZ';
        }
        if (\preg_match('/1/', \substr($initData['phone'], 0, 1))) {
            $country = 'US';
        }
        if (\preg_match('/30/', \substr($initData['phone'], 0, 2))) {
            $country = 'GR';
        }
        if (\preg_match('/31/', \substr($initData['phone'], 0, 2))) {
            $country = 'NL';
        }
        if (\preg_match('/32/', \substr($initData['phone'], 0, 2))) {
            $country = 'BE';
        }
        if (\preg_match('/33/', \substr($initData['phone'], 0, 2))) {
            $country = 'FR';
        }
        if (\preg_match('/34/', \substr($initData['phone'], 0, 2))) {
            $country = 'ES';
        }
        if (\preg_match('/351/', \substr($initData['phone'], 0, 3))) {
            $country = 'PT';
        }
        if (\preg_match('/352/', \substr($initData['phone'], 0, 3))) {
            $country = 'LU';
        }
        if (\preg_match('/353/', \substr($initData['phone'], 0, 3))) {
            $country = 'IE';
        }
        if (\preg_match('/354/', \substr($initData['phone'], 0, 3))) {
            $country = 'IS';
        }
        if (\preg_match('/355/', \substr($initData['phone'], 0, 3))) {
            $country = 'AL';
        }
        if (\preg_match('/356/', \substr($initData['phone'], 0, 3))) {
            $country = 'MT';
        }
        if (\preg_match('/357/', \substr($initData['phone'], 0, 3))) {
            $country = 'CY';
        }
        if (\preg_match('/358/', \substr($initData['phone'], 0, 3))) {
            $country = 'FI';
        }
        if (\preg_match('/359/', \substr($initData['phone'], 0, 3))) {
            $country = 'BG';
        }
        if (\preg_match('/36/', \substr($initData['phone'], 0, 2))) {
            $country = 'HU';
        }
        if (\preg_match('/370/', \substr($initData['phone'], 0, 3))) {
            $country = 'LT';
        }
        if (\preg_match('/371/', \substr($initData['phone'], 0, 3))) {
            $country = 'LV';
        }
        if (\preg_match('/372/', \substr($initData['phone'], 0, 3))) {
            $country = 'EE';
        }
        if (\preg_match('/373/', \substr($initData['phone'], 0, 3))) {
            $country = 'MD';
        }
        if (\preg_match('/374/', \substr($initData['phone'], 0, 3))) {
            $country = 'AM';
        }
        if (\preg_match('/375/', \substr($initData['phone'], 0, 3))) {
            $country = 'BY';
        }
        if (\preg_match('/376/', \substr($initData['phone'], 0, 3))) {
            $country = 'AD';
        }
        if (\preg_match('/377/', \substr($initData['phone'], 0, 3))) {
            $country = 'MC';
        }
        if (\preg_match('/378/', \substr($initData['phone'], 0, 3))) {
            $country = 'SM';
        }
        if (\preg_match('/379/', \substr($initData['phone'], 0, 3))) {
            $country = 'VA';
        }
        if (\preg_match('/380/', \substr($initData['phone'], 0, 3))) {
            $country = 'UA';
        }
        if (\preg_match('/381/', \substr($initData['phone'], 0, 3))) {
            $country = 'RS';
        }
        if (\preg_match('/382/', \substr($initData['phone'], 0, 3))) {
            $country = 'ME';
        }
        if (\preg_match('/385/', \substr($initData['phone'], 0, 3))) {
            $country = 'HR';
        }
        if (\preg_match('/386/', \substr($initData['phone'], 0, 3))) {
            $country = 'SI';
        }
        if (\preg_match('/387/', \substr($initData['phone'], 0, 3))) {
            $country = 'BA';
        }
        if (\preg_match('/389/', \substr($initData['phone'], 0, 3))) {
            $country = 'MK';
        }
        if (\preg_match('/39/', \substr($initData['phone'], 0, 2))) {
            $country = 'IT';
        }
        if (\preg_match('/40/', \substr($initData['phone'], 0, 2))) {
            $country = 'RO';
        }
        if (\preg_match('/41/', \substr($initData['phone'], 0, 2))) {
            $country = 'CH';
        }
        if (\preg_match('/420/', \substr($initData['phone'], 0, 3))) {
            $country = 'CZ';
        }
        if (\preg_match('/421/', \substr($initData['phone'], 0, 3))) {
            $country = 'SK';
        }
        if (\preg_match('/423/', \substr($initData['phone'], 0, 3))) {
            $country = 'LI';
        }
        if (\preg_match('/43/', \substr($initData['phone'], 0, 2))) {
            $country = 'AT';
        }
        if (\preg_match('/44/', \substr($initData['phone'], 0, 2))) {
            $country = 'GB';
        }
        if (\preg_match('/45/', \substr($initData['phone'], 0, 2))) {
            $country = 'DK';
        }
        if (\preg_match('/46/', \substr($initData['phone'], 0, 2))) {
            $country = 'SE';
        }
        if (\preg_match('/47/', \substr($initData['phone'], 0, 2))) {
            $country = 'NO';
        }
        if (\preg_match('/48/', \substr($initData['phone'], 0, 2))) {
            $country = 'PL';
        }
        if (\preg_match('/49/', \substr($initData['phone'], 0, 2))) {
            $country = 'DE';
        }
        if (\preg_match('/51/', \substr($initData['phone'], 0, 2))) {
            $country = 'PE';
        }
        if (\preg_match('/52/', \substr($initData['phone'], 0, 2))) {
            $country = 'MX';
        }
        if (\preg_match('/53/', \substr($initData['phone'], 0, 2))) {
            $country = 'CU';
        }
        if (\preg_match('/54/', \substr($initData['phone'], 0, 2))) {
            $country = 'AR';
        }
        if (\preg_match('/55/', \substr($initData['phone'], 0, 2))) {
            $country = 'BR';
        }
        if (\preg_match('/56/', \substr($initData['phone'], 0, 2))) {
            $country = 'CL';
        }
        if (\preg_match('/57/', \substr($initData['phone'], 0, 2))) {
            $country = 'CO';
        }
        if (\preg_match('/58/', \substr($initData['phone'], 0, 2))) {
            $country = 'VE';
        }
        if (\preg_match('/84/', \substr($initData['phone'], 0, 2))) {
            $country = 'VN';
        }
        if (\preg_match('/90/', \substr($initData['phone'], 0, 2))) {
            $country = 'TR';
        }
        if (\preg_match('/972/', \substr($initData['phone'], 0, 3))) {
            $country = 'IL';
        }
        if (\preg_match('/992/', \substr($initData['phone'], 0, 3))) {
            $country = 'TJ';
        }
        if (\preg_match('/993/', \substr($initData['phone'], 0, 3))) {
            $country = 'TM';
        }
        if (\preg_match('/994/', \substr($initData['phone'], 0, 3))) {
            $country = 'AZ';
        }
        if (\preg_match('/995/', \substr($initData['phone'], 0, 3))) {
            $country = 'GE';
        }
        if (\preg_match('/996/', \substr($initData['phone'], 0, 3))) {
            $country = 'KG';
        }
        if (\preg_match('/998/', \substr($initData['phone'], 0, 3))) {
            $country = 'UZ';
        }
        if (!$country) {
            $rContext->setFinished();
            //            $rContext->setError('Эта страна пока не поддерживается');
            return false;
        }
        /*
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
                return false;
        */
        // //////////////////////////////////////////////////////////////////////////////////////////////////
        $ch = $rContext->getCurlHandler();
        if (!isset($swapData['num'])) {
            $swapData['num'] = 1;
            $rContext->setSwapData($swapData);
        }
        !isset($swapData['iteration']) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
        $swapData['session'] = $this->getSessionData($params);
        if (!$swapData['session']) {
            if ($swapData['iteration'] >= 60) {
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
            } else {
                $rContext->setSwapData($swapData);
                $rContext->setSleep(3);
            }

            return false;
        }
        $rContext->setSwapData($swapData);
        if (!isset($swapData['captcha_token']) && $swapData['session']->code) {
            $swapData['captcha_token'] = $swapData['session']->code;
        }
        /*
                if (!isset($swapData['captcha_token']) && !isset($swapData['captcha_id'.$swapData['num']])) {
                    $token = neuro_token('hcaptcha',$this->sitekey);
                    if (strlen($token)>30) {
                        $swapData['captcha_token'] = $token;
                    }
        //            echo "Neuro token $token\n";
                }
        */
        $rContext->setSwapData($swapData);
        $page = 'https://www.emobiletracker.com/';
        if (!isset($swapData['captcha_token'])) {
            if (!isset($swapData['captcha_id'.$swapData['num']])) {
                $swapData['captcha_service'.$swapData['num']] = (int) (($swapData['iteration'] - 1) / 2) % \count($this->captcha_service);
                $rContext->setSwapData($swapData);
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']) {
                    $params = ['key' => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'], 'method' => 'hcaptcha', 'sitekey' => $this->sitekey, 'pageurl' => $page];
                    /*
                                        if ($swapData['session']->proxy) {
                                            $params['proxytype'] = 'http';
                                            $params['proxy'] = ($swapData['session']->proxy_auth ? $swapData['session']->proxy_auth.'@' : '').$swapData['session']->proxy;
                                        }
                    */
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'].'/in.php?'.\http_build_query($params);
                } else {
                    $params = ['clientKey' => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'], 'task' => ['type' => 'HCaptchaTaskProxyless', 'websiteURL' => $page, 'websiteKey' => $this->sitekey]];
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'].'/createTask';
                    \curl_setopt($ch, \CURLOPT_POST, true);
                    \curl_setopt($ch, \CURLOPT_POSTFIELDS, \json_encode($params, \JSON_UNESCAPED_UNICODE));
                }
            } else {
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']) {
                    $params = ['key' => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'], 'action' => 'get', 'id' => $swapData['captcha_id'.$swapData['num']]];
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'].'/res.php?'.\http_build_query($params);
                } else {
                    $params = ['clientKey' => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'], 'taskId' => $swapData['captcha_id'.$swapData['num']]];
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'].'/getTaskResult';
                    \curl_setopt($ch, \CURLOPT_POST, true);
                    \curl_setopt($ch, \CURLOPT_POSTFIELDS, \json_encode($params, \JSON_UNESCAPED_UNICODE));
                }
            }
            \curl_setopt($ch, \CURLOPT_URL, $url);
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 5);
        //            echo "$url\n";
        //            var_dump($params);
        //            echo "\n";
        } else {
            $phone = $initData['phone'];
            $params = ['code' => $country, 'mobile' => '+'.$phone, 'g-recaptcha-response' => $swapData['captcha_token'], 'h-captcha-response' => $swapData['captcha_token'], 'submit' => ''];
            $url = $page.'trace-process.php';
            \curl_setopt($ch, \CURLOPT_URL, $url);
            //            curl_setopt($ch, CURLOPT_REFERER, $url);
            \curl_setopt($ch, \CURLOPT_POST, true);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, \http_build_query($params));
            \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
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
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        //        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $error = $swapData['iteration'] > 0 ? \curl_error($rContext->getCurlHandler()) : false;
        if (\strpos($error, 'timed out') || \strpos($error, 'connection')) {
            $error = false;
            //                $mysqli->query("UPDATE session SET unlocktime=date_add(now(),interval 10 minute),sessionstatusid=6 WHERE id=" . $swapData['session']->id);
        }
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
                        $rContext->setFinished();
                        $rContext->setError('Ошибка получения капчи');
                        \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                            './logs/emt/captcha_err_'.\time().'.txt',
                            /* curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n". */
                            $content."\r\n".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']
                        );
                    }
                } else {
                    if (isset($res['taskId'])) {
                        $swapData['captcha_id'.$swapData['num']] = $res['taskId'];
                    } elseif ($swapData['iteration'] > 10) {
                        $rContext->setFinished();
                        $rContext->setError('Ошибка получения капчи');
                        \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                            './logs/emt/captcha_err_'.\time().'.txt',
                            /* curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n". */
                            $content."\r\n".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']
                        );
                    }
                }
            } else {
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']) {
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
                $rContext->setSleep(1);
            }

            return true;
        }
        if (!$error) {
            $content = \curl_multi_getcontent($rContext->getCurlHandler());
            //            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/emt/emt_'.time().'.html',$content);
            if (\preg_match('/<strong>   Name:([^<]+)</', $content, $matches)) {
                $resultData = new ResultDataList();
                if ('No matches found' != $matches[1] && 'Not Found' != $matches[1] && 't' != $matches[1]) {
                    if (false !== \strpos($matches[1], 'Ð')) {
                        $matches[1] = \iconv('utf-8', 'iso-8859-1', $matches[1]);
                    }
                    $data['name'] = new ResultDataField('string', 'Name', $matches[1], 'Имя', 'Имя');
                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                $mysqli->executeStatement("UPDATE session SET sessionstatusid=3,success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);

                return true;
            } elseif (\preg_match("/<div class='alert alert-dismissable alert-danger'>(.*?)<\\/div>/", $content, $matches)) {
                if (\strpos($matches[1], 'so soon')) {
                    $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='waiting' WHERE id=".$swapData['session']->id);
                } else {
                    $error = \trim(\strip_tags($matches[1]));
                    $mysqli->executeStatement("UPDATE session SET sessionstatusid=3,success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
                }
                unset($swapData['session']);
                unset($swapData['captcha_token']);
            } elseif (\preg_match("/<span class='label label-warning'>[^<]+<\\/span>([^<]+)</", $content, $matches)) {
                if (\strpos($matches[1], 'captcha')) {
                    $mysqli->executeStatement("UPDATE session SET endtime=now(),sessionstatusid=4,statuscode='invalidcaptcha' WHERE statuscode='used' AND id=".$swapData['session']->id);
                } else {
                    $error = \trim($matches[1]);
                    $mysqli->executeStatement("UPDATE session SET sessionstatusid=3,success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
                }
                unset($swapData['session']);
                unset($swapData['captcha_token']);
            } else {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/emt/emt_err_'.\time().'.html', $content);
                if (!$content) {
                    $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 1 hour),sessionstatusid=6,statuscode='empty' WHERE id=".$swapData['session']->id);
                }
                unset($swapData['session']);
                unset($swapData['captcha_token']);
            }
        }
        if ($error || $swapData['iteration'] >= 20) {
            $rContext->setFinished();
            $rContext->setError($error ?: 'Превышено количество попыток получения ответа');
        }
        $rContext->setSwapData($swapData);
        $rContext->setSleep(1);

        return false;
    }
}

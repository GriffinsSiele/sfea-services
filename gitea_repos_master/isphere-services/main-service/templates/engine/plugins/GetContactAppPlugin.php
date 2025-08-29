<?php

class GetContactAppPlugin implements PluginInterface
{
    private $captcha_service = [
        //        array('host' => 'api.capmonster.cloud', 'key' => 'afb26cbb248d650ea8b8d88822984242'),
        ['host' => 'rucaptcha.com', 'key' => 'd167c71a9278312f184f17caa4e71050'],
        ['host' => 'api.anti-captcha.com', 'key' => '63def0a149a147e0d13e409cc8318fc3'],
    ];
    //    private $key = '2Wq7)qkX~cp7)H|n_tc&o+:G_USN3/-uIi~>M+c ;Oq]E{t9)RC_5|lhAA_Qq%_4';
    private $key = 'y1gY|J%&6V kTi$>_Ali8]/xCqmMMP1$*)I8FwJ,*r_YUM 4h?@7+@#<>+w-e3VW';

    public function getName()
    {
        return 'GetContact';
    }

    public function getTitle()
    {
        return 'Поиск в GetContact';
    }

    public function getSessionData(array $params, $usecaptcha = true)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $reqId = $params['_reqId'];
        $sessionData = null;
        $forcecaptcha = false;
        if ($usecaptcha) {
            $result = $mysqli->executeQuery("SELECT COUNT(*) count FROM session WHERE request_id IS NULL AND sessionstatusid=2 AND (statuscode<>'used' OR lasttime<from_unixtime(unix_timestamp(now())-600)) AND lasttime<from_unixtime(unix_timestamp(now())-10) AND sourceid=55 AND captcha>'' AND statuscode<>'success'");
            if ($result) {
                $row = $result->fetchAssociative();
                $forcecaptcha = $row && $row['count'];
            }
        }
        $uselocked = false;
        if (!$forcecaptcha) {
            $result = $mysqli->executeQuery('SELECT COUNT(*) count FROM session WHERE sessionstatusid=2 AND sourceid=55 AND lasttime<from_unixtime(unix_timestamp(now())-600)');
            if ($result) {
                $row = $result->fetchAssociative();
                $uselocked = $row && $row['count'] < 100;
            }
        }
        try {
            $mysqli->executeStatement('UPDATE session s SET request_id='.$reqId." WHERE request_id IS NULL AND ((sessionstatusid=2 AND (statuscode<>'used' OR unix_timestamp(now())-unix_timestamp(lasttime)>600)".($forcecaptcha ? " AND captcha>'' AND statuscode<>'success'" : " AND captcha=''".($usecaptcha ? '' : " AND statuscode='success'")).')'.($uselocked ? " OR (sessionstatusid=6 AND statuscode='tagslimitexceed')" : '').") AND unix_timestamp(now())-unix_timestamp(lasttime)>10 AND sourceid=55 AND token>'' ORDER BY lasttime limit 1");
        } catch (Exception $e) {
            return $sessionData;
        }
        $result = $mysqli->executeQuery("SELECT id,cookies,starttime,lasttime,captcha,token,enckey,device,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM session s WHERE sourceid=55 AND request_id=".$reqId);
        if ($result) {
            $row = $result->fetchAssociative();
            if ($row) {
                $sessionData = new \stdClass();
                $sessionData->id = $row['id'];
                $sessionData->code = $row['captcha'];
                $sessionData->token = $row['token'];
                $sessionData->enckey = $row['enckey'];
                $sessionData->device = $row['device'];
                $sessionData->starttime = $row['starttime'];
                $sessionData->lasttime = $row['lasttime'];
                $sessionData->cookies = $row['cookies'];
                $sessionData->proxyid = $row['proxyid'];
                $sessionData->proxy = $row['proxy'];
                $sessionData->proxy_auth = \strlen($row['proxy_auth']) > 1 ? $row['proxy_auth'] : false;
                $mysqli->executeStatement("UPDATE session SET lasttime=now(),used=ifnull(used,0)+1,used_ext=ifnull(used_ext,0)+1,sessionstatusid=2,statuscode='used',request_id=NULL WHERE id=".$sessionData->id);
                $mysqli->executeStatement("UPDATE session SET lasttime=now(),used=ifnull(used,0)+1,used_ext=ifnull(used_ext,0)+1,sessionstatusid=2,statuscode='used',request_id=NULL WHERE statuscode<>'used' AND id=".$sessionData->id);
                if (!$row['proxyid']) {
                    if (\rand() % 3) {
                        $proxygroup = 5;
                        $lastused = 10;
                        $lastlocked = 30;
                    } else {
                        $proxygroup = 1;
                        $lastused = 60;
                        $lastlocked = 600;
                    }
                    $result = $mysqli->executeQuery("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth, (SELECT count(*) FROM session s WHERE sourceid=55 AND proxyid=proxy.id) scnt FROM proxy WHERE enabled=1 AND status=1 AND proxygroup={$proxygroup} AND id NOT IN (SELECT proxyid FROM session s WHERE sourceid=55 AND proxyid IS NOT NULL AND ((sessionstatusid=6 AND lasttime>from_unixtime(unix_timestamp(now())-{$lastlocked})))) ORDER BY scnt, lasttime limit 1");
                    if ($result) {
                        $row = $result->fetchAssociative();
                        if ($row) {
                            $sessionData->proxyid = $row['proxyid'];
                            $sessionData->proxy = $row['proxy'];
                            $sessionData->proxy_auth = \strlen($row['proxy_auth']) > 1 ? $row['proxy_auth'] : false;
                            //                            $mysqli->query("UPDATE proxy SET lasttime=now() WHERE id=".$row['proxyid']);
                            $mysqli->executeStatement('UPDATE session SET proxyid='.$row['proxyid'].' WHERE id='.$sessionData->id);
                        } else {
                            //                            $sessionData = null;
                            //                            $mysqli->query("UPDATE session SET statuscode='needmoreproxy' WHERE id=".$sessionData->id);
                        }
                    }
                }
                //                if ($sessionData && $sessionData->proxyid)
                //                    $mysqli->query("UPDATE proxy SET lasttime=now(),used=used+1 WHERE id=".$sessionData->proxyid);
            }
        }

        return $sessionData;
    }

    private function decrypt($key, $garble)
    {
        return \openssl_decrypt(\base64_decode($garble), 'aes-256-ecb', $key, \OPENSSL_RAW_DATA);
    }

    private function encrypt($key, $garble)
    {
        $method = 'AES-256-ECB';
        $ivSize = \openssl_cipher_iv_length($method);
        $iv = '';
        if ($ivSize > 0) {
            $iv = \openssl_random_pseudo_bytes($ivSize);
        }

        return \openssl_encrypt($garble, $method, $key, \OPENSSL_RAW_DATA, $iv);
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
            $rContext->setError('Эта страна пока не поддерживается');

            return false;
        }
        //        global $userId;
        //        if ($userId==915
        //          || $userId==340
        //          || $userId==3178
        //          ) {
        //            $rContext->setFinished();
        //            $rContext->setError('Сервис временно недоступен');
        //            return false;
        //        }
        // //////////////////////////////////////////////////////////////////////////////////////////////////
        $ch = $rContext->getCurlHandler();
        !isset($swapData['iteration']) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
        if (!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData($params, $swapData['iteration'] % 2);
            if (!$swapData['session']) {
                if (isset($swapData['iteration']) && $swapData['iteration'] >= 20) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                } else {
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);
                }

                return false;
            }
            unset($swapData['captcha_image']);
            unset($swapData['captcha_id']);
            unset($swapData['captcha_value']);
            if ($swapData['session']->code) {
                $swapData['captcha_value'] = $swapData['session']->code;
            }
            if (!$swapData['session']->proxy) {
                $astro = ['213.108.196.179:10687'];
                $swapData['session']->proxyid = 2;
                $swapData['session']->proxy = $astro[\rand(0, \count($astro) - 1)];
                $swapData['session']->proxy_auth = 'isphere:e6eac1';
            }
        }
        $rContext->setSwapData($swapData);
        if (isset($swapData['captcha_image']) && !isset($swapData['captcha_value'])) {
            if (!isset($swapData['captcha_id'])) {
                $swapData['captcha_service'] = (int) (($swapData['iteration'] - 1) / 10) % \count($this->captcha_service);
                //                echo $swapData['iteration'].": New captcha from ".$this->captcha_service[$swapData['captcha_service']]['host']."\n";
                $rContext->setSwapData($swapData);
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service']]['host']) {
                    $params = ['key' => $this->captcha_service[$swapData['captcha_service']]['key'], 'method' => 'base64', 'body' => $swapData['captcha_image'], 'regsense' => 1, 'min_len' => 6, 'max_len' => 6];
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service']]['host'].'/in.php?'.\http_build_query($params);
                    \curl_setopt($ch, \CURLOPT_POST, true);
                    \curl_setopt($ch, \CURLOPT_POSTFIELDS, \http_build_query($params));
                } else {
                    $params = ['clientKey' => $this->captcha_service[$swapData['captcha_service']]['key'], 'task' => ['type' => 'ImageToTextTask', 'body' => $swapData['captcha_image'], 'case' => true, 'minLength' => 6, 'maxLength' => 6]];
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
        } else {
            $time = \time();
            $url = 'https://pbssrv-centralevents.com/v2.8/';
            $params = ['token' => $swapData['session']->token];
            if (isset($swapData['captcha_value'])) {
                $url .= 'verify-code';
                $params['validationCode'] = \strtr($swapData['captcha_value'], ['-' => 't']);
            //                echo "Sending ".($swapData['session']->code?"daemon":"plugin")." captcha ".$swapData['captcha_value']."\n";
            } else {
                $params['countryCode'] = $country;
                $params['phoneNumber'] = '+'.$initData['phone'];
                $params['source'] = '';
                if (isset($swapData['tags'])) {
                    $url .= 'number-detail';
                    $params['source'] = 'details';
                } else {
                    $url .= 'search';
                    $params['source'] = 'search';
                }
            }
            \ksort($params);
            $req = \json_encode($params);
            $crypt_data = \base64_encode($this->encrypt(\hex2bin($swapData['session']->enckey), $req));
            $signature = \base64_encode(\hash_hmac('sha256', $time.'-'.$req, $this->key, true));
            $header = ['X-App-Version: 5.6.2', 'X-Token: '.$swapData['session']->token, 'X-Os: android 6.0', 'X-Client-Device-Id: '.$swapData['session']->device, 'Content-Type: application/json; charset=utf-8', 'Connection: close', 'X-Req-Timestamp: '.$time, 'X-Req-Signature: '.$signature, 'X-Encrypted: 1', 'X-Lang: en_US', 'X-Network-Country: us', 'X-Country-Code: us', 'X-Mobile-Service: GMS'];
            \curl_setopt($ch, \CURLOPT_URL, $url);
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 3 + (isset($swapData['empty']) && $swapData['empty'] >= 2 ? 3 : 0));
            \curl_setopt($ch, \CURLOPT_POST, true);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, \json_encode(['data' => $crypt_data]));
            \curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);
            //            curl_setopt($ch, CURLOPT_HEADER, true);
            //            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            \curl_setopt($ch, \CURLOPT_USERAGENT, 'Dalvik/2.1.0 (Linux; U; Android 6.0; Google Build/MRA58K)');
            \curl_setopt($ch, \CURLOPT_ENCODING, '');
            if ($swapData['session']->proxy) {
                \curl_setopt($ch, \CURLOPT_PROXY, $swapData['session']->proxy);
                if ($swapData['session']->proxy_auth) {
                    \curl_setopt($ch, \CURLOPT_PROXYUSERPWD, $swapData['session']->proxy_auth);
                    \curl_setopt($ch, \CURLOPT_PROXYAUTH, \CURLAUTH_ANY);
                }
            }
            //             echo $swapData['iteration'].": $url\n\n";
            //             echo "$req\n\n";
            //             echo json_encode(array('data' => $crypt_data))."\n\n";
            //             var_dump($header);
            //             echo "\n";
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
            //                $mysqli->query("UPDATE session SET unlocktime=date_add(now(),interval 30 minute),sessionstatusid=6 WHERE id=" . $swapData['session']->id);
        }
        $content = \curl_multi_getcontent($rContext->getCurlHandler());
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
                            './logs/getcontact/'.$initData['checktype'].'_captcha_err_'.$swapData['iteration'].'_'.\time().'.txt',
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
                            './logs/getcontact/'.$initData['checktype'].'_captcha_err_'.$swapData['iteration'].'_'.\time().'.txt',
                            /* curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n". */
                            $content."\r\n".$this->captcha_service[$swapData['captcha_service']]['host']
                        );
                    }
                }
            } else {
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service']]['host']) {
                    if ('CAPCHA_NOT_READY' == $content && \time() - $swapData['captcha_time'] < 60) {
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
                    } elseif (isset($res['status']) && 'ready' !== $res['status'] && \time() - $swapData['captcha_time'] < 60) {
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
                if ($swapData['iteration']) {
                    --$swapData['iteration'];
                }
            }
            $rContext->setSwapData($swapData);
            if (!isset($swapData['captcha_value']) && isset($swapData['captcha_id'])) {
                $rContext->setSleep(5);
            } else {
                $rContext->setSleep(1);
            }

            return true;
        }
        $full_content = $content;
        $start = \strpos($content, '{');
        if (false !== $start) {
            $content = \trim(\substr($content, $start, \strlen($content) - $start + 1));
        }
        $res = \json_decode($content, true);
        //        \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_raw_'.$swapData['iteration'].'_'.time().'.html',$content);
        if ($res && isset($res['data'])) {
            $content = $this->decrypt(\hex2bin($swapData['session']->enckey), $res['data']);
            $res = \json_decode($content, true);
        }
        if (!$error && isset($swapData['captcha_value'])) {
            if ($swapData['iteration']) {
                --$swapData['iteration'];
            }
            $report = false;
            $captcha_value = $swapData['captcha_value'];
            //            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_verify_'.$swapData['iteration'].'_'.time().'.txt',$captcha_value."\n\n".$content);
            unset($swapData['captcha_value']);
            if (isset($res['meta']['httpStatusCode']) && 200 == $res['meta']['httpStatusCode']) {
                //                $mysqli->query("UPDATE session SET success_ext=ifnull(success_ext,0)+1 WHERE id=" . $swapData['session']->id);
                $report = 'good';
                unset($swapData['captcha_image']);
                if ($swapData['session']->code) {
                    $mysqli->executeStatement("UPDATE session SET statuscode='success',successtime=now() WHERE sessionstatusid=2 AND id=".$swapData['session']->id);
                    $swapData['session']->code = '';
                }
            } elseif (isset($res['result']['image'])) {
                $report = 'bad';
                //                echo strlen(base64_decode($res['result']['image']))."\n";
                //                if (strlen(base64_decode($res['result']['image']))>3400)
                //                    $swapData['captcha_image'] = $res['result']['image'];
                //                else
                //                    $swapData['captcha_value'] = 'fedcba';
                //                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_captcha_verify_'.$swapData['iteration'].'_'.time().'.jpg',base64_decode($res['result']['image']));
                if ($swapData['session']->code) {
                    $mysqli->executeStatement("UPDATE session SET sessionstatusid=4,statuscode='invalidcaptcha',captchaimage='".$res['result']['image']."' WHERE sessionstatusid=2 AND id=".$swapData['session']->id);
                    //                    if ($swapData['session']->proxyid)
                    //                        $mysqli->query("UPDATE proxy SET success=success+1,successtime=now() WHERE id=".$swapData['session']->proxyid);
                    unset($swapData['session']);
                    $rContext->setSwapData($swapData);

                    return true;
                }
            } elseif (isset($res['meta']['errorMessage']) && \strlen(\trim($res['meta']['errorMessage']))) {
                //                if ($swapData['session']->proxyid)
                //                    $mysqli->query("UPDATE proxy SET success=success+1,successtime=now() WHERE id=".$swapData['session']->proxyid);
                if (\strpos($res['meta']['errorMessage'], 'failed')) {
                    $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 1 year),sessionstatusid=6,statuscode='failed' WHERE sessionstatusid=2 AND id=".$swapData['session']->id);
                    unset($swapData['session']);
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_msg_'.$swapData['iteration'].'_'.\time().'.txt', $content);
                } elseif (\strpos($res['meta']['errorMessage'], 'not authorized')) {
                    $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 1 year),sessionstatusid=6,statuscode='notauthorized' WHERE sessionstatusid=2 AND id=".$swapData['session']->id);
                    unset($swapData['session']);
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_msg_'.$swapData['iteration'].'_'.\time().'.txt', $content);
                } elseif (\strpos($res['meta']['errorMessage'], '500000')) {
                    $error = 'Внутренняя ошибка источника';
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_msg_'.$swapData['iteration'].'_'.\time().'.txt', $content);
                    unset($swapData['session']);
                } else {
                    //                    $error = trim($res['meta']['errorMessage']);
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_msg_'.$swapData['iteration'].'_'.\time().'.txt', $content);
                    unset($swapData['session']);
                }
            } elseif (\strpos($content, '>nginx<')) {
                $error = 'Внутренняя ошибка источника';
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_err_'.$initData['phone'].'_'.$swapData['iteration'].'_verify_'.\time().'.html', $content);
                unset($swapData['session']);
            } elseif (\strlen($content)) {
                if ($swapData['iteration'] >= 3) {
                    $error = 'Некорректный ответ источника';
                }
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_err_'.$initData['phone'].'_'.$swapData['iteration'].'_verify_'.\time().'.html', $content);
                $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 30 minute),sessionstatusid=6,statuscode='error',captcha='',captcha_id=NULL WHERE sessionstatusid=2 AND id=".$swapData['session']->id);
                unset($swapData['session']);
            } else {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                    './logs/getcontact/getcontact_empty_'.$initData['phone'].'_'.$swapData['iteration'].'_verify_'.\time().'.html',
                    /* curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n". */
                    $full_content."\n".$swapData['session']->id
                );
                $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 30 minute),sessionstatusid=6,statuscode='empty',captcha='',captcha_id=NULL WHERE sessionstatusid=2 AND id=".$swapData['session']->id);
                $mysqli->executeStatement('UPDATE session SET proxyid=NULL WHERE sourceid=55 AND sessionstatusid=2 AND proxyid='.$swapData['session']->proxyid.' ORDER BY lasttime LIMIT '.($swapData['session']->proxyid < 100 ? '3' : '10'));
                //                $mysqli->query("UPDATE session SET used_ext=0,success_ext=0,proxy_id=NULL WHERE success_ext=0 AND used_ext>=3 AND sessionstatusid=6 AND id=" . $swapData['session']->id);
                //                $mysqli->query("UPDATE session SET used_ext=1,success_ext=0 WHERE success_ext>0 AND sessionstatusid=6 AND id=" . $swapData['session']->id);
                !isset($swapData['empty']) ? $swapData['empty'] = 1 : $swapData['empty']++;
                unset($swapData['session']);
                $rContext->setSwapData($swapData);
                $result = $mysqli->executeQuery("SELECT SUM(res_code=500) err, COUNT(*) cnt FROM ResponseNew WHERE source_name='GetContact' AND created_at>DATE_SUB(now(),INTERVAL 1 minute) AND process_time>1");
                if ($result) {
                    $row = $result->fetchAssociative();
                    if ($row && $row['cnt'] >= 3 && $row['err'] / $row['cnt'] > 0.3) {
                        $error = 'Сервис временно недоступен';
                    }
                }
                if ($swapData['empty'] >= 3) {
                    $error = 'Сервис не отвечает на запросы';
                }
                //                if (!$error) {
                //                    if ($swapData['empty']>=3) $rContext->setSleep(5);
                //                    return true;
                //                }
            }
            //            if (isset($swapData['captcha_service']))
            //                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_'.$this->captcha_service[$swapData['captcha_service']]['host'].'_'.$report.'_'.$swapData['iteration'].'_'.time().'.txt',$captcha_value);
            if ($report && isset($swapData['captcha_id']) && isset($swapData['captcha_service'])) {
                /*
                                $params = array(
                                    'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                                    'action' => 'report'.$report,
                                    'id' => $swapData['captcha_id'],
                                );
                                $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/res.php?".http_build_query($params);
                                $res = file_get_contents($url);
                */
                $mysqli->executeStatement('INSERT INTO session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),55,'.('bad' == $report ? 4 : 3).",'".('bad' == $report ? 'invalidcaptcha' : 'success')."','".$captcha_value."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
                //                echo "Captcha ID ".$swapData['captcha_id']." reported as ".$report." with result $res\n";
            }
            unset($swapData['captcha_id']);
        } elseif (!$error && isset($swapData['tags'])) {
            //            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_tags_'.$swapData['iteration'].'_'.time().'.txt',$content);
            $retry = false;
            $resultData = new ResultDataList();
            $data = $swapData['data'];
            $resultData->addResult($data);
            if ($res && isset($res['result']['tags'])) {
                foreach ($res['result']['tags'] as $tag) {
                    $data = [];
                    $data['name'] = new ResultDataField('string', 'Name', \iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', \html_entity_decode($tag['tag']))), 'Имя', 'Имя');
                    $data['count'] = new ResultDataField('string', 'Count', $tag['count'], 'Количество упоминаний', 'Количество упоминаний');
                    $resultData->addResult($data);
                }
            //                $mysqli->query("UPDATE session SET success_ext=ifnull(success_ext,0)+1 WHERE id=" . $swapData['session']->id);
            } elseif ($res && isset($res['meta']['errorMessage'])) {
                if (false !== \strpos($res['meta']['errorMessage'], 'No result found')) {
                    //                    $mysqli->query("UPDATE session SET success_ext=ifnull(success_ext,0)+1 WHERE id=" . $swapData['session']->id);
                } elseif (\strpos($res['meta']['errorMessage'], 'failed')) {
                    $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 1 year),sessionstatusid=6,statuscode='failed' WHERE sessionstatusid=2 AND id=".$swapData['session']->id);
                    unset($swapData['session']);
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_tags_msg_'.$swapData['iteration'].'_'.\time().'.txt', $content);
                } elseif (\strpos($res['meta']['errorMessage'], 'not authorized')) {
                    $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 1 year),sessionstatusid=6,statuscode='notauthorized' WHERE sessionstatusid=2 AND id=".$swapData['session']->id);
                    unset($swapData['session']);
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_tags_msg_'.$swapData['iteration'].'_'.\time().'.txt', $content);
                } elseif (\strpos($res['meta']['errorMessage'], 'validation')) {
                    //                    echo strlen(base64_decode($res['result']['image']))."\n";
                    //                    if (strlen(base64_decode($res['result']['image']))>3400)
                    //                        $swapData['captcha_image'] = $res['result']['image'];
                    //                    else
                    //                        $swapData['captcha_value'] = 'fedcba';
                    //                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_captcha_tags_'.$swapData['iteration'].'_'.time().'.jpg',base64_decode($res['result']['image']));
                    //                    $mysqli->query("UPDATE session SET unlocktime=date_add(now(),interval 1 day),sessionstatusid=6,statuscode='validation' WHERE id=" . $swapData['session']->id);
                    //                    $mysqli->query("UPDATE session SET sessionstatusid=7,captchaimage='".$res['result']['image']."' WHERE sessionstatusid=2 AND id=" . $swapData['session']->id);
                    //                    unset($swapData['session']);
                    //                    $retry = true;
                } else {
                    //                    $error = 'Ошибка при выполнении запроса';
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_tags_msg_'.$swapData['iteration'].'_'.\time().'.txt', $content);
                }
            } elseif (\strlen($content)) {
                //                $error = 'Ошибка при выполнении запроса';
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_tags_err_'.$swapData['iteration'].'_'.\time().'.txt', $content);
            }
            if (isset($res['subscriptionInfo']['usage']['numberDetail']['remainingCount']) && !$res['subscriptionInfo']['usage']['numberDetail']['remainingCount'] || isset($res['result']['subscriptionInfo']['usage']['numberDetail']['remainingCount']) && !$res['result']['subscriptionInfo']['usage']['numberDetail']['remainingCount']) {
                $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 1 day),sessionstatusid=6,statuscode='tagslimitexceed',captcha='',captcha_id=NULL WHERE sessionstatusid=2 AND id=".$swapData['session']->id);
            }
            if (!$retry) {
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                return true;
            }
        } elseif (!$error) {
            //            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_search_'.$swapData['iteration'].'_'.time().'.txt',$content);
            if (isset($res['meta']['errorMessage']) && \strlen(\trim($res['meta']['errorMessage']))) {
                //                if ($swapData['session']->proxyid)
                //                    $mysqli->query("UPDATE proxy SET success=success+1,successtime=now() WHERE id=".$swapData['session']->proxyid);
                if (false !== \strpos($res['meta']['errorMessage'], 'Number is invalid') || \strpos($res['meta']['errorMessage'], 'this country')) {
                    $resultData = new ResultDataList();
                    $mysqli->executeStatement("UPDATE session SET used=0,success=0,success_ext=ifnull(success_ext,0)+1,statuscode='success',successtime=now() WHERE sessionstatusid=2 AND id=".$swapData['session']->id);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();

                    return true;
                //                } elseif (strpos($res['meta']['errorMessage'],"be visible") || strpos($res['meta']['errorMessage'],"быть невидимым")) {
                } elseif (false !== \strpos($res['meta']['errorMessage'], 'No result found')) {
                    $resultData = new ResultDataList();
                    //                    $data['invisible'] = new ResultDataField('string','Invisible','да','Невидимый','Невидимый');
                    //                    $resultData->addResult($data);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->executeStatement("UPDATE session SET used=0,success=0,success_ext=ifnull(success_ext,0)+1,statuscode='success',successtime=now() WHERE sessionstatusid=2 AND id=".$swapData['session']->id);

                    return true;
                } elseif (\strpos($res['meta']['errorMessage'], 'limit')) {
                    $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(str_to_date('".\date('Y-m-01 03:00:00', \time() - 3 * 60 * 60)."', '%Y-%m-%d %H:%i:%s'),interval 1 month),sessionstatusid=6,statuscode='limitexceed',captcha='',captcha_id=NULL WHERE sessionstatusid=2 AND id=".$swapData['session']->id);
                    unset($swapData['session']);
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_msg_'.$swapData['iteration'].'_'.\time().'.txt', $content);
                } elseif (\strpos($res['meta']['errorMessage'], 'failed')) {
                    $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 1 year),sessionstatusid=6,statuscode='failed' WHERE sessionstatusid=2 AND id=".$swapData['session']->id);
                    unset($swapData['session']);
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_msg_'.$swapData['iteration'].'_'.\time().'.txt', $content);
                } elseif (\strpos($res['meta']['errorMessage'], 'not authorized')) {
                    $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 1 year),sessionstatusid=6,statuscode='notauthorized' WHERE sessionstatusid=2 AND id=".$swapData['session']->id);
                    unset($swapData['session']);
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_msg_'.$swapData['iteration'].'_'.\time().'.txt', $content);
                } elseif (\strpos($res['meta']['errorMessage'], 'validation') && isset($res['result']['image'])) {
                    //                    echo strlen(base64_decode($res['result']['image']))."\n";
                    //                    if (strlen(base64_decode($res['result']['image']))>3400)
                    //                        $swapData['captcha_image'] = $res['result']['image'];
                    //                    else
                    //                        $swapData['captcha_value'] = 'fedcba';
                    //                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_captcha_search_'.$swapData['iteration'].'_'.time().'.jpg',base64_decode($res['result']['image']));
                    $mysqli->executeStatement("UPDATE session SET sessionstatusid=7,captchaimage='".$res['result']['image']."' WHERE sessionstatusid=2 AND id=".$swapData['session']->id);
                    $mysqli->executeStatement("UPDATE session SET captcha_id=NULL WHERE sessionstatusid=7 AND statuscode='used' AND id=".$swapData['session']->id);
                    unset($swapData['session']);
                } elseif (\strpos($res['meta']['errorMessage'], '500000')) {
                    $error = 'Внутренняя ошибка источника';
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_msg_'.$swapData['iteration'].'_'.\time().'.txt', $content);
                    $mysqli->executeStatement("UPDATE session SET used=0,success=0,success_ext=ifnull(success_ext,0)+1,statuscode='success',successtime=now() WHERE sessionstatusid=2 AND id=".$swapData['session']->id);
                } else {
                    //                    $error = trim($res['meta']['errorMessage']);
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_msg_'.$swapData['iteration'].'_'.\time().'.txt', $content);
                    $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 1 day),sessionstatusid=6,statuscode='unknown',captcha='',captcha_id=NULL WHERE sessionstatusid=2 AND id=".$swapData['session']->id);
                    unset($swapData['session']);
                }
            } elseif (isset($res['result']['profile']['displayName'])) {
                //                if ($swapData['session']->proxyid)
                //                    $mysqli->query("UPDATE proxy SET success=success+1,successtime=now() WHERE id=".$swapData['session']->proxyid);
                $isuser = false;
                $resultData = new ResultDataList();
                $mysqli->executeStatement("UPDATE session SET used=0,success=0,success_ext=ifnull(success_ext,0)+1,statuscode='success',successtime=now() WHERE sessionstatusid=2 AND id=".$swapData['session']->id);
                $data['name'] = new ResultDataField('string', 'Name', \trim(\html_entity_decode(\strip_tags($res['result']['profile']['displayName']))), 'Имя', 'Имя');
                //                $data['invisible'] = new ResultDataField('string','Invisible','нет','Невидимый','Невидимый');
                if (isset($res['result']['profile']['profileImage'])) {
                    $data['avatar'] = new ResultDataField('image', 'Avatar', $res['result']['profile']['profileImage'], 'Аватар', 'Аватар');
                }
                if (isset($res['result']['badge'])) {
                    if ('spam' == $res['result']['badge']) {
                        $data['spam'] = new ResultDataField('string', 'Spam', 'Да', 'Спам', 'Спам');
                    }
                    if ('gtc' == $res['result']['badge'] || 'premium' == $res['result']['badge'] || 'business' == $res['result']['badge']) {
                        $isuser = true;
                        $data['badge'] = new ResultDataField('string', 'Badge', $res['result']['badge'], 'Тип аккаунта', 'Тип аккаунта');
                    }
                }
                $data['user'] = new ResultDataField('string', 'IsUser', $isuser ? 'Да' : 'Нет', 'Пользователь GetContact', 'Пользователь GetContact');
                if (isset($res['result']['profile']['tagCount'])) {
                    $data['tags'] = new ResultDataField('string', 'TagsCount', $res['result']['profile']['tagCount'], 'Количество тегов', 'Количество тегов');
                }
                if (isset($res['result']['deletedTagCount'])) {
                    $data['deletedtags'] = new ResultDataField('string', 'DeletedTagsCount', $res['result']['deletedTagCount'], 'Количество удаленных тегов', 'Количество удаленных тегов');
                }
                if (isset($res['result']['comments']['comments']) && \is_array($res['result']['comments']['comments'])) {
                    foreach ($res['result']['comments']['comments'] as $i => $comment) {
                        $data['comment'.$i] = new ResultDataField('string', 'Comment', \iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', \html_entity_decode($comment['body']))), 'Комментарий', 'Комментарий');
                    }
                }
                if (isset($res['result']['subscriptionInfo']['usage']['numberDetail']['remainingCount']) && !$res['result']['subscriptionInfo']['usage']['numberDetail']['remainingCount']) {
                    if (!$swapData['session']->code) {
                        $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 1 day),sessionstatusid=6,statuscode='tagslimitexceed',captcha='',captcha_id=NULL WHERE sessionstatusid=2 AND id=".$swapData['session']->id);
                    }
                } elseif (isset($res['result']['limitedResult']) && !$res['result']['limitedResult'] && $isuser && isset($res['result']['profile']['tagCount']) && $res['result']['profile']['tagCount']) {
                    $swapData['tags'] = true;
                    $swapData['data'] = $data;
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(3);
                    $clientId = $params['_clientId'];
                    if (265 == $clientId) {
                        return true;
                    }
                }
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                return true;
            } elseif (\strpos($content, '>nginx<')) {
                $error = 'Внутренняя ошибка источника';
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_err_'.$initData['phone'].'_'.$swapData['iteration'].'_'.\time().'.html', $content);
                unset($swapData['session']);
            } elseif (\strlen($content)) {
                if ($swapData['iteration'] >= 3) {
                    $error = 'Некорректный ответ источника';
                }
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/getcontact/getcontact_err_'.$initData['phone'].'_'.$swapData['iteration'].'_'.\time().'.html', $content);
                $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 30 minute),sessionstatusid=6,statuscode='error',captcha='',captcha_id=NULL WHERE sessionstatusid=2 AND id=".$swapData['session']->id);
                unset($swapData['session']);
            } else {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents(
                    './logs/getcontact/getcontact_empty_'.$initData['phone'].'_'.$swapData['iteration'].'_'.\time().'.html',
                    /* curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n". */
                    $full_content."\n".$swapData['session']->id
                );
                $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 30 minute),sessionstatusid=6,statuscode='empty',captcha='',captcha_id=NULL WHERE sessionstatusid=2 AND id=".$swapData['session']->id);
                $mysqli->executeStatement('UPDATE session SET proxyid=NULL WHERE sourceid=55 AND sessionstatusid=2 AND proxyid='.$swapData['session']->proxyid.' ORDER BY lasttime LIMIT 10');
                //                $mysqli->query("UPDATE session SET used_ext=0,success_ext=0,proxy_id=NULL WHERE success_ext=0 AND used_ext>=3 AND sessionstatusid=6 AND id=" . $swapData['session']->id);
                //                $mysqli->query("UPDATE session SET used_ext=1,success_ext=0 WHERE success_ext>0 AND sessionstatusid=6 AND id=" . $swapData['session']->id);
                !isset($swapData['empty']) ? $swapData['empty'] = 1 : $swapData['empty']++;
                unset($swapData['session']);
                $rContext->setSwapData($swapData);
                $result = $mysqli->executeQuery("SELECT SUM(res_code=500) err, COUNT(*) cnt FROM ResponseNew WHERE source_name='GetContact' AND created_at>DATE_SUB(now(),INTERVAL 1 minute) AND process_time>1");
                if ($result) {
                    $row = $result->fetchAssociative();
                    if ($row && $row['cnt'] >= 3 && $row['err'] / $row['cnt'] > 0.3) {
                        $error = 'Сервис временно недоступен';
                    }
                }
                if ($swapData['empty'] >= 3) {
                    $error = 'Сервис не отвечает на запросы';
                }
                //                if (!$error) {
                //                    if ($swapData['empty']>=3) $rContext->setSleep(5);
                //                    return true;
                //                }
            }
        }
        if (!$error && $swapData['iteration'] >= 10) {
            $error = 'Превышено количество попыток получения ответа';
        }
        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();

            return false;
        }
        //        unset($swapData['session']);
        $rContext->setSwapData($swapData);
        $rContext->setSleep(1);

        return true;
    }
}

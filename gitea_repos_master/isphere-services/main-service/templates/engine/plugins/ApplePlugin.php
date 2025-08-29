<?php

class ApplePlugin implements PluginInterface
{
    public function getName()
    {
        return 'Apple';
    }

    public function getTitle($checktype = '')
    {
        $title = ['' => 'Поиск в Apple', 'apple_email' => 'Apple - проверка email на наличие пользователя'];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск в HH';
    }

    public function getSessionData(array $params)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $sessionData = null;
        $result = $mysqli->executeQuery("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM session s WHERE sessionstatusid=2 AND sourceid=46 AND unix_timestamp(now())-unix_timestamp(lasttime)>1 ORDER BY lasttime limit 1");
        //        $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM proxy WHERE status=1 ORDER BY lasttime limit 1");
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
                $sessionData->proxy = $row['proxy'];
                $sessionData->proxy_auth = \strlen($row['proxy_auth']) > 1 ? $row['proxy_auth'] : false;
                $mysqli->executeStatement('UPDATE session SET lasttime=now(),used=ifnull(used,0)+1 WHERE id='.$sessionData->id);
                //                $mysqli->query("UPDATE proxy SET lasttime=now() WHERE id=".$row['proxyid']);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        if (!isset($initData['email'])) {
            $rContext->setFinished();
            //            $rContext->setError('Не указаны параметры для поиска (email)');
            return false;
        }
        /*
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
                return false;
        */
        //        if(!isset($swapData['session'])) {
        $swapData['session'] = $this->getSessionData($params);
        if (!$swapData['session']) {
            if (isset($swapData['iteration']) && $swapData['iteration'] >= 10) {
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
            } else {
                !isset($swapData['iteration']) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
                $rContext->setSwapData($swapData);
                $rContext->setSleep(3);
            }

            return false;
        }
        $rContext->setSwapData($swapData);
        //        }
        // //////////////////////////////////////////////////////////////////////////////////////////////////
        $ch = $rContext->getCurlHandler();
        $host = 'https://iforgot.apple.com';
        $url = $host.'/password/verify/appleid';
        $params = ['id' => $initData['email']];
        $header = ['Accept: application/json, text/javascript, */*; q=0.01', 'Content-Type: application/json', 'Origin: '.$host, 'Referer: '.$url, 'X-Requested-With: XMLHttpRequest'];
        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_POST, true);
        \curl_setopt($ch, \CURLOPT_POSTFIELDS, \json_encode($params));
        \curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);
        //        curl_setopt($ch, CURLOPT_HEADER, true);
        //        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        //        curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
        \curl_setopt($ch, \CURLOPT_COOKIEFILE, '');
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
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        $swapData['iteration'] = !isset($swapData['iteration']) ? 1 : $swapData['iteration'] + 1;
        $rContext->setSwapData($swapData);
        $error = $swapData['iteration'] > 3 ? \curl_error($rContext->getCurlHandler()) : false;
        if (!$error) {
            $content = \curl_multi_getcontent($rContext->getCurlHandler());
            //            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/apple/apple_'.$swapData['iteration'].'_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
            $start = \strpos($content, '{');
            $content = \trim(\substr($content, $start, \strlen($content) - $start + 1));
            $res = \json_decode($content, true);
            if ($res && \is_array($res)) {
                $resultData = new ResultDataList();
                if (isset($res['forgotPasswordFlow'])) {
                    $data['result'] = new ResultDataField('string', 'result', $initData['email'].' является AppleID', 'Результат', 'Результат');
                    $data['result_code'] = new ResultDataField('string', 'result_code', 'FOUND', 'Код результата', 'Код результата');
                    if (isset($res['supportsUnlock'])) {
                        $data['locked'] = new ResultDataField('string', 'locked', $res['supportsUnlock'] ? 'Да' : 'Нет', 'Заблокирован', 'Заблокирован');
                    }
                    if (isset($res['is2FAEligible'])) {
                        $data['auth'] = new ResultDataField('string', 'auth', $res['is2FAEligible'] ? 'Двухфакторная' : 'Только пароль', 'Аутентификация', 'Аутентификация');
                    }
                    if (isset($res['paidAccount'])) {
                        $data['paid'] = new ResultDataField('string', 'paid', $res['paidAccount'] ? 'Да' : 'Нет', 'Платный аккаунт', 'Платный аккаунт');
                    }
                    if (isset($res['trustedPhones'][0]['number'])) {
                        $data['phone'] = new ResultDataField('string', 'phone', \strtr($res['trustedPhones'][0]['number'], ['•' => '*']), 'Телефон', 'Телефон');
                    }
                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                return true;
            } elseif (\strpos($content, 'unavailabe') || \strpos($content, '502 Bad Gateway')) {
                unset($swapData['session']);
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/apple/apple_err_'.\time().'.txt', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);
                if ($swapData['iteration'] >= 10) {
                    $error = 'Сервис временно недоступен';
                }
            } elseif (!$content) {
                unset($swapData['session']);
            } else {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/apple/apple_err_'.\time().'.txt', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);
                $error = 'Некорректный ответ';
            }
        }
        if (!$error && isset($swapData['iteration']) && $swapData['iteration'] > 10) {
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

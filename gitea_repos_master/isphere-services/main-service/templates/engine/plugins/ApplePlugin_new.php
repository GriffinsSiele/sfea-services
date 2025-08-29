<?php

class ApplePlugin_new implements PluginInterface
{
    public function getName()
    {
        return 'Apple';
    }

    public function getTitle($checktype = '')
    {
        $title = [
            '' => 'Поиск в Apple',
            'apple_email' => 'Apple - проверка email на наличие пользователя',
        ];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск в HH';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=46 AND unix_timestamp(now())-unix_timestamp(lasttime)>1 ORDER BY lasttime limit 1");
        //        $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE status=1 ORDER BY lasttime limit 1");

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
                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = \strlen($row->proxy_auth) > 1 ? $row->proxy_auth : false;

                $mysqli->query('UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1 WHERE id='.$sessionData->id);
                //                $mysqli->query("UPDATE isphere.proxy SET lasttime=now() WHERE id=".$row->proxyid);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        global $mysqli;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if (!isset($initData['email'])) {
            $rContext->setFinished();
            //            $rContext->setError('Не указаны параметры для поиска (email)');

            return false;
        }

        //        if(!isset($swapData['session'])) {
        $swapData['session'] = $this->getSessionData();

        if (!$swapData['session']) {
            if (isset($swapData['iteration']) && ($swapData['iteration'] >= 10)) {
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
            } else {
                (!isset($swapData['iteration'])) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
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
        $header = [
          'Accept: application/json, text/javascript, */*; q=0.01',
          'Content-Type: application/json',
          'Origin: '.$host,
          'Referer: '.$url,
          'X-Requested-With: XMLHttpRequest',
//          'sstt: '.urlencode($swapData['session']->token),
//          'X-Apple-I-FD-Client-Info: {"U":"Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:70.0) Gecko/20100101 Firefox/70.0","L":"ru-RU","Z":"GMT+03:00","V":"1.1","F":"VWa44j1e3NlY5BSo9z4ofjb75PaK4Vpjt.gEngMQBTuX38.WUMnGWVQdg1kzDlSgyyIT1n3wL6k03x0.5w2SCVL6yXyjaY1WMsiZRPrwVL6tqAhbrmQkLNbXky.7bc7.XTrLjtNQRUybYb3tG13VFwKgrmUsc8ogDQ2SD2I3pxUC56PUshuU52E9XXTneNufuyPBDjaY2ftckuyPBB2SCVZXnN9PK9I9___C91uQkmqkOeLarTcfx9MsFr9O7AxF0jUchYjS8Qs1xLB1Vg4Wgmte7ShrkaUe.zAASFQ_BZ4yeVMNW5CfUXtStKjE4PIDxO9sPrsiMTKQnlLZnjzchyr1BNlrJNNlY5QB4bVNjMk.B.t"}',
        ];
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

        $swapData['iteration'] = (!isset($swapData['iteration'])) ? 1 : $swapData['iteration'] + 1;
        $rContext->setSwapData($swapData);

        $error = ($swapData['iteration'] > 3) ? \curl_error($rContext->getCurlHandler()) : false;
        if (!$error) {
            $content = \curl_multi_getcontent($rContext->getCurlHandler());
            \file_put_contents('./logs/apple/apple_'.$swapData['iteration'].'_'.\time().'.txt', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);

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
                \file_put_contents('./logs/apple/apple_err_'.\time().'.txt', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);
                if ($swapData['iteration'] >= 10) {
                    $error = 'Сервис временно недоступен';
                }
            } elseif (!$content) {
                unset($swapData['session']);
            } else {
                \file_put_contents('./logs/apple/apple_err_'.\time().'.txt', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);
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

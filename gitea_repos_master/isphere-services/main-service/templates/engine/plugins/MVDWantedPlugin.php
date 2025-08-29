<?php

class MVDWantedPlugin implements PluginInterface
{
    public function getName()
    {
        return 'MVD';
    }

    public function getTitle($checktype = '')
    {
        return 'МВД РФ - Федеральный розыск';
    }

    public function getSessionData(array $params)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $sessionData = null;
        $result = $mysqli->executeQuery("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM session s WHERE sessionstatusid=2 AND sourceid=35 ORDER BY lasttime limit 1");
        if ($result) {
            $row = $result->fetchAssociative();
            if ($row) {
                $sessionData = new \stdClass();
                $sessionData->proxy = $row['proxy'];
                $sessionData->proxy_auth = \strlen($row['proxy_auth']) > 1 ? $row['proxy_auth'] : false;
                $sessionData->id = $row['id'];
                $sessionData->code = $row['captcha'];
                $sessionData->token = $row['token'];
                $sessionData->starttime = $row['starttime'];
                $sessionData->lasttime = $row['lasttime'];
                $sessionData->cookies = $row['cookies'];
                $sessionData->proxyid = $row['proxyid'];
                $sessionData->proxy = $row['proxy'];
                $sessionData->proxy_auth = \strlen($row['proxy_auth']) > 1 ? $row['proxy_auth'] : false;
                $mysqli->executeStatement("UPDATE session SET lasttime=now(),used=ifnull(used,0)+1,endtime=now(),sessionstatusid=3,statuscode='used' WHERE id=".$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        if (!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date'])) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (ФИО, дата рождения)');
            return false;
        }
        if (isset($initData['last_name']) && isset($initData['first_name']) && \preg_match('/[^А-Яа-яЁё\\s\\-\\.]/ui', $initData['last_name'].' '.$initData['first_name'].(isset($initData['patronymic']) ? ' '.$initData['patronymic'] : ''))) {
            $rContext->setFinished();
            $rContext->setError('Имя может содержать только русские буквы');

            return false;
        }
        /*
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
                return false;
        */
        // //////////////////////////////////////////////////////////////////////////////////////////////////
        !isset($swapData['iteration']) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
        //        if (!isset($swapData['session'])) {
        $swapData['session'] = $this->getSessionData($params);
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
        $swapData['iteration'] = 1;
        $rContext->setSwapData($swapData);
        //        }
        $ch = $rContext->getCurlHandler();
        $initData['date'] = \date('d.m.Y', \strtotime($initData['date']));
        $birth = \explode('.', $initData['date']);
        $params = ['s_family' => $initData['last_name'], 'fio' => $initData['first_name'], 's_patr' => isset($initData['patronymic']) ? $initData['patronymic'] : '', 'd_year' => $birth[2], 'd_month' => $birth[1], 'd_day' => $birth[0], 'email' => 'noreply@mail.ru', 'captcha' => $swapData['session']->code];
        $url = 'https://xn--b1aew.xn--p1ai/wanted?'.\http_build_query($params);
        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
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
        !isset($swapData['iteration']) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
        $rContext->setSwapData($swapData);
        $content = \curl_multi_getcontent($rContext->getCurlHandler());
        if (1) {
            if (!$content) {
                $error = $swapData['iteration'] > 5 && \curl_error($rContext->getCurlHandler());
            }
            //            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/mvd/wanted_'.time().'.html',$content);
            if (\preg_match('/<div class="b-search-result [^>]+>/', $content)) {
                $resultData = new ResultDataList();
                if (\preg_match_all('/<div class="bs-item /', $content, $matches)) {
                    $data['Result'] = new ResultDataField('string', 'Result', 'Найден в базе федерального розыска', 'Результат', 'Результат');
                    $data['ResultCode'] = new ResultDataField('string', 'ResultCode', 'FOUND', 'Код результата', 'Код результата');
                    if (\preg_match('/<div class="bs-item-title">([^<]+)</', $content, $matches)) {
                        $data['name'] = new ResultDataField('string', 'name', \trim(\html_entity_decode($matches[1])), 'ФИО', 'ФИО');
                    }
                    if (\preg_match('/<div class="bs-item-image">[^<]+<img src="([^"]+)"/', $content, $matches)) {
                        $data['photo'] = new ResultDataField('image', 'photo', \strtr(\trim($matches[1]), ['-98xx98' => '']), 'Фото', 'Фото');
                    }
                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                $mysqli->executeStatement("UPDATE session SET sessionstatusid=3,success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);

                return true;
            } else {
                if (\strpos($content, 'неверный код')) {
                    if (isset($swapData['session'])) {
                        $mysqli->executeStatement("UPDATE session SET endtime=now(),sessionstatusid=4,statuscode='invalidcaptcha' WHERE id=".$swapData['session']->id);
                        //                        unset($swapData['session']);
                    }
                    //                    $rContext->setSwapData($swapData);
                    return true;
                } elseif (\strpos($content, 'временно') || \strpos($content, 'ведутся работы')) {
                    $error = 'Сервис временно недоступен';
                } elseif (\preg_match('/<span style="color:red;font-weight:bold">([^<]=)/', $content, $matches)) {
                    $error = $matches[1];
                } else {
                    if ($content) {
                        \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/mvd/wanted_err_'.\time().'.html', $content);
                    }
                    if ($swapData['iteration'] > 3) {
                        $error = 'Некорректный ответ сервиса';
                    }
                }
            }
        }
        if ($error || $swapData['iteration'] > 10) {
            $rContext->setFinished();
            $rContext->setError('' == $error ? 'Превышено количество попыток получения ответа' : $error);

            return false;
        }
        $rContext->setSleep(1);

        return true;
    }
}

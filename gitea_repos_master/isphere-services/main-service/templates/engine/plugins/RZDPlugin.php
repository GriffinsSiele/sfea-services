<?php

class RZDPlugin implements PluginInterface
{
    public function getName()
    {
        return 'RZD';
    }

    public function getTitle($checktype = '')
    {
        $title = ['' => 'Поиск в РЖД', 'rzd_email' => 'РЖД - проверка email на наличие пользователя'];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск в HH';
    }

    public function getSessionData(array $params)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $sessionData = null;
        //        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM session s WHERE sessionstatusid=2 AND sourceid=0 AND unix_timestamp(now())-unix_timestamp(lasttime)>1 ORDER BY lasttime limit 1");
        $result = $mysqli->executeQuery("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM proxy WHERE enabled=1 AND status=1 ORDER BY lasttime limit 1");
        if ($result) {
            $row = $result->fetchAssociative();
            if ($row) {
                $sessionData = new \stdClass();
                $sessionData->proxy = $row['proxy'];
                $sessionData->proxy_auth = \strlen($row['proxy_auth']) > 1 ? $row['proxy_auth'] : false;
                /*
                                $sessionData->id = $row['id'];
                                $sessionData->code = $row['captcha'];
                                $sessionData->token = $row['token'];
                                $sessionData->starttime = $row['starttime'];
                                $sessionData->lasttime = $row['lasttime'];
                                $sessionData->cookies = $row['cookies'];

                                $mysqli->query("UPDATE session SET lasttime=now(),used=ifnull(used,0)+1 WHERE id=".$sessionData->id);
                */
                $mysqli->executeStatement('UPDATE proxy SET lasttime=now() WHERE id='.$row['proxyid']);
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
        $swapData['session'] = $this->getSessionData($params);
        $rContext->setSwapData($swapData);
        if (!$swapData['session']) {
            //            $rContext->setFinished();
            //            $rContext->setError('Нет актуальных сессий');
            $rContext->setSleep(1);

            return false;
        }
        // //////////////////////////////////////////////////////////////////////////////////////////////////
        $ch = $rContext->getCurlHandler();
        $url = 'https://www.rzd.ru/selfcare/uniqueEmail';
        $params = ['EMAIL' => $initData['email']];
        $header = ['Origin: https://www.rzd.ru', 'Referer: https://www.rzd.ru/selfcare/register/ru', 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8', 'X-Requested-With: XMLHttpRequest'];
        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_POST, true);
        \curl_setopt($ch, \CURLOPT_POSTFIELDS, \http_build_query($params));
        \curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);
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
        $error = $swapData['iteration'] > 5 && \curl_error($rContext->getCurlHandler());
        if (!$error) {
            $content = \curl_multi_getcontent($rContext->getCurlHandler());
            //            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/rzd/rzd_'.time().'.txt',$content);
            $res = \json_decode($content, true);
            if ($res) {
                $resultData = new ResultDataList();
                if (isset($res['code'])) {
                    if ('EMAIL_NOT_UNIQUE' == $res['code']) {
                        $data['result'] = new ResultDataField('string', 'result', $initData['email'].' зарегистрирован на сайте rzd.ru', 'Результат', 'Результат');
                        $data['result_code'] = new ResultDataField('string', 'result_code', 'FOUND', 'Код результата', 'Код результата');
                        $resultData->addResult($data);
                    }
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            } elseif (\strpos($content, 'недоступен') || \strpos($content, 'технически') || \strpos($content, '502 Bad Gateway')) {
                $error = 'Сервис временно недоступен';
            } else {
                if ($content) {
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/rzd/rzd_err_'.\time().'.txt', $content);
                }
                //                $error = "Некорректный ответ";
            }
        }
        if (!$error && isset($swapData['iteration']) && $swapData['iteration'] > 5) {
            $error = 'Превышено количество попыток получения ответа';
        }
        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();

            return false;
        }

        return true;
    }
}

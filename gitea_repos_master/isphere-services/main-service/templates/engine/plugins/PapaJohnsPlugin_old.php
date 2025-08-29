<?php

class PapaJohnsPlugin_old implements PluginInterface
{
    public function getName()
    {
        return 'PapaJohns';
    }

    public function getTitle($checktype = '')
    {
        $title = [
            '' => 'Поиск в Papa Johns',
            'papajohns_phone' => 'Papa Johns - проверка телефона на наличие пользователя',
            'papajohns_email' => 'Papa Johns - проверка email на наличие пользователя',
        ];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск в Papa Johns';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=52 AND unix_timestamp(now())-unix_timestamp(lasttime)>1 ORDER BY lasttime limit 1");
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
                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1,statuscode='used' WHERE id=".$sessionData->id);

                if (!$row->proxyid) {
                    //                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE enabled=1 AND status=1 AND country='ru' AND (rotation>0 OR (SELECT COUNT(*) FROM session WHERE proxyid=proxy.id AND sourceid=48 AND sessionstatusid IN (1,2,6,7))<1) ORDER BY lasttime limit 1");
                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE enabled=1 AND status=1 AND country='ru' AND rotation>0 ORDER BY lasttime limit 1");
                    if ($result) {
                        $row = $result->fetch_object();
                        if ($row) {
                            $sessionData->proxyid = $row->proxyid;
                            $sessionData->proxy = $row->proxy;
                            $sessionData->proxy_auth = \strlen($row->proxy_auth) > 1 ? $row->proxy_auth : false;

                            $mysqli->query('UPDATE isphere.proxy SET lasttime=now() WHERE id='.$row->proxyid);
                            $mysqli->query('UPDATE isphere.session SET proxyid='.$row->proxyid.' WHERE id='.$sessionData->id);
                        }
                    }
                }
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        global $mysqli;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if (!isset($initData['phone']) && !isset($initData['email'])) {
            $rContext->setFinished();
            //            $rContext->setError('Не указаны параметры для поиска (телефон или email)');

            return false;
        }

        if (isset($initData['phone'])) {
            //            if (strlen($initData['phone'])==10)
            //                $initData['phone']='7'.$initData['phone'];
            //            if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
            //                $initData['phone']='7'.substr($initData['phone'],1);
        }

        $rContext->setFinished();
        $rContext->setError('Сервис временно недоступен');

        return false;

        // //////////////////////////////////////////////////////////////////////////////////////////////////

        //        if(!isset($swapData['session'])) {
        $swapData['session'] = $this->getSessionData();
        if (isset($swapData['session'])) {
            $swapData['iteration'] = 1;
            $rContext->setSwapData($swapData);
        }
        //        }
        if (!$swapData['session']) {
            if (isset($swapData['iteration']) && ($swapData['iteration'] >= 10)) {
                $rContext->setFinished();
                $rContext->setError('Слишком много запросов в очереди');
            } else {
                (!isset($swapData['iteration'])) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
                $rContext->setSwapData($swapData);
                $rContext->setSleep(1);
            }

            return false;
        }

        // //////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $url = 'https://api.papajohns.ru/user/';
        $params = [
            'platform' => 'web',
            'lang' => 'ru',
            'city_id' => 1,
        ];
        if (isset($initData['phone'])) {
            $url .= 'has-phone';
            $params['phone'] = $initData['phone'];
        } else {
            $url .= 'has-email';
            $params['email'] = $initData['email'];
        }
        $url .= '?'.\http_build_query($params);
        \curl_setopt($ch, \CURLOPT_URL, $url);
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
        global $mysqli;
        global $serviceurl;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration'])) ? 1 : $swapData['iteration'] + 1;
        $rContext->setSwapData($swapData);
        $error = $swapData['iteration'] > 3 ? \curl_error($rContext->getCurlHandler()) : false;
        if (\strpos($error, 'timed out') || \strpos($error, 'connection')) {
            $error = false;
            //                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 10 minute),sessionstatusid=6,statuscode='connectionerror' WHERE id=" . $swapData['session']->id);
        }
        $content = false;
        if (!$error) {
            $content = \curl_multi_getcontent($rContext->getCurlHandler());
            //            file_put_contents('./logs/papajohns/papajohns_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);

            $start = \strpos($content, '{');
            $content = \trim(\substr($content, $start, \strlen($content) - $start + 1));
            $res = \json_decode($content, true);
        }

        if (isset($res) && \is_array($res) && isset($res['status']) && !isset($res['message'])) {
            $resultData = new ResultDataList();
            if ($res['status']) {
                if (isset($initData['phone'])) {
                    $data['phone'] = new ResultDataField('string', 'Phone', $initData['phone'], 'Телефон', 'Телефон');
                //                    $data['email'] = new ResultDataField('string','Email',$res['email'],'E-mail','E-mail');
                } else {
                    //                    $data['phone'] = new ResultDataField('string','Phone',$res['phone'],'Телефон','Телефон');
                    $data['email'] = new ResultDataField('string', 'Email', $initData['email'], 'E-mail', 'E-mail');
                }
                $data['result'] = new ResultDataField('string', 'Result', 'Найден', 'Результат', 'Результат');
                $data['result_code'] = new ResultDataField('string', 'ResultCode', 'FOUND', 'Код результата', 'Код результата');
                $resultData->addResult($data);
            }
            $mysqli->query("UPDATE isphere.session SET statuscode='success',success=ifnull(success,0)+1 WHERE id=".$swapData['session']->id);
            $rContext->setResultData($resultData);
            $rContext->setFinished();
        } elseif (isset($res['message'])) {
            if (\strpos($res['message'], 'лимит')) {
                //                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(current_date(),interval 1 day),sessionstatusid=6,statuscode='limit' WHERE id=" . $swapData['session']->id);
                $mysqli->query('UPDATE isphere.session SET proxyid=NULL,unlocktime=date_add(now(),interval '.($swapData['session']->proxyid < 100 ? '30 second' : '5 minute')."),sessionstatusid=6,statuscode='limit' WHERE sourceid=52 AND proxyid=".$swapData['session']->proxyid.' ORDER BY lasttime DESC LIMIT 10');
            } else {
                $error = $res['message'];
            }
        } else {
            if ($content) {
                \file_put_contents('./logs/papajohns/papajohns_err_'.\time().'.txt', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);
            } else {
                $mysqli->query('UPDATE isphere.session SET proxyid=NULL,unlocktime=date_add(now(),interval '.($swapData['session']->proxyid < 100 ? '30 second' : '5 minute')."),sessionstatusid=6,statuscode='empty' WHERE id=".$swapData['session']->id);
            }
            $rContext->setSleep(1);
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

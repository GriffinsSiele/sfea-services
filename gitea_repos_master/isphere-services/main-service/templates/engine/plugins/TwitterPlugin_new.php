<?php

class TwitterPlugin_new implements PluginInterface
{
    public function getName()
    {
        return 'Twitter';
    }

    public function getTitle($checktype = '')
    {
        $title = [
            '' => 'Поиск в Twitter',
            'twitter_phone' => 'Twitter - проверка телефона на наличие пользователя',
            'twitter_email' => 'Twitter - проверка email на наличие пользователя',
            'twitter_url' => 'Twitter - профиль пользователя',
        ];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];

        //        return 'Поиск в Twitter';
    }

    public function getSessionData()
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

        $mysqli->query("UPDATE session s SET request_id=$reqId WHERE sessionstatusid=2 AND sourceid=50 AND unix_timestamp(now())-unix_timestamp(lasttime)>10 ORDER BY lasttime limit 1");
        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,data,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=50 AND request_id=$reqId ORDER BY lasttime limit 1");

        if ($result) {
            $row = $result->fetch_object();

            if ($row) {
                $sessionData = new \stdClass();

                $sessionData->id = $row->id;
                $sessionData->code = $row->captcha;
                $sessionData->token = $row->token;
                $sessionData->data = $row->data;
                $sessionData->starttime = $row->starttime;
                $sessionData->lasttime = $row->lasttime;
                $sessionData->cookies = $row->cookies;
                $sessionData->proxyid = $row->proxyid;
                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = \strlen($row->proxy_auth) > 1 ? $row->proxy_auth : false;

                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1,statuscode='used',request_id=NULL WHERE id=".$sessionData->id);

                if (!$row->proxyid) {
                    //                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE enabled=1 AND status=1 AND country='ru' AND (rotation>0 OR (SELECT COUNT(*) FROM session WHERE proxyid=proxy.id AND sourceid=48 AND sessionstatusid IN (1,2,6,7))<1) ORDER BY lasttime limit 1");
                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE status=1 AND country<>'ru' AND rotation>0 ORDER BY lasttime limit 1");
                    if ($result) {
                        $row = $result->fetch_object();
                        if ($row) {
                            $sessionData->proxyid = $row->proxyid;
                            $sessionData->proxy = $row->proxy;
                            $sessionData->proxy_auth = \strlen($row->proxy_auth) > 1 ? $row->proxy_auth : false;

                            //                            $mysqli->query("UPDATE isphere.proxy SET lasttime=now() WHERE id=".$row->proxyid);
                            $mysqli->query('UPDATE isphere.session SET proxyid='.$row->proxyid.' WHERE id='.$sessionData->id);
                        }
                    }
                }

                $mysqli->query('UPDATE isphere.proxy SET lasttime=now(),used=ifnull(used,0)+1 WHERE id='.$row->proxyid);
                //                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3 WHERE used>=1 AND id=".$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        global $mysqli;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if (!isset($initData['url']) && !isset($initData['phone']) && !isset($initData['email'])) {
            $rContext->setFinished();
            $rContext->setError('Не указаны параметры для поиска (ссылка, телефон или email)');

            return false;
        }

        if (isset($initData['phone'])) {
            //            if (strlen($initData['phone'])==10)
            //                $initData['phone']='7'.$initData['phone'];
            //            if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
            //                $initData['phone']='7'.substr($initData['phone'],1);
            $swapData['phone'] = $initData['phone'];
        }

        if (isset($initData['email'])) {
            $swapData['email'] = $initData['email'];
        }

        if (isset($initData['url'])) {
            if (false === \strpos($initData['url'], 'twitter.com/')) {
                $rContext->setFinished();

                return false;
            }
            $swapData['path'] = $initData['url'];
        }
        $rContext->setSwapData($swapData);

        // //////////////////////////////////////////////////////////////////////////////////////////////////

        (!isset($swapData['iteration'])) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
        if (!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData();
            if (!$swapData['session']) {
                if (isset($swapData['iteration']) && ($swapData['iteration'] >= 30)) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                } else {
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);
                }

                return false;
            }
            if (($swapData['iteration'] > 5) && \rand(0, 2)) {
                $astro = ['193.23.50.59:10451', '94.247.132.131:10127'];
                $swapData['session']->proxyid = 0;
                $swapData['session']->proxy = $astro[\rand(0, \count($astro) - 1)];
                $swapData['session']->proxy_auth = 'isphere:e6eac1';
            }
            $rContext->setSwapData($swapData);
        }

        // //////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $site = 'https://twitter.com';
        if (isset($swapData['path'])) {
            $url = $swapData['path'];
        } else {
            $url = $site.'/account/begin_password_reset?lang=ru';
            $post = [
                'authenticity_token' => $swapData['session']->token,
                'account_identifier' => isset($initData['phone']) ? $initData['phone'] : $initData['email'],
            ];
            $header = [
                'Referer: '.$url,
                'Origin: '.$site,
                'Content-Type: application/x-www-form-urlencoded',
            ];
        }

        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_TIMEOUT, 5);
        if (isset($header)) {
            \curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);
        }
        //        curl_setopt($ch, CURLOPT_HEADER, true);
        //        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        if (isset($post)) {
            \curl_setopt($ch, \CURLOPT_POST, true);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, \http_build_query($post));
        }
        \curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, 0);
        \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
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
        global $mysqli;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $error = false;
        $curl_error = \curl_error($rContext->getCurlHandler());
        $swapData['iteration'] = (!isset($swapData['iteration'])) ? 1 : $swapData['iteration'] + 1;

        if (!$curl_error) {
            $content = \curl_multi_getcontent($rContext->getCurlHandler());

            if (!isset($swapData['path'])) {
                \file_put_contents('./logs/twitter/twitter_'.\time().'.html', /* curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n". */ $content);

                if (\strpos($content, 'send_password_reset') || \strpos($content, 'verify_user_info') || \strpos($content, 'нашли несколько') || \strpos($content, 'нашли более') || \strpos($content, 'не можем найти') || \strpos($content, "couldn't find") || \strpos($content, 'tidak dapat menemukan')) {
                    $resultData = new ResultDataList();
                    if (!\strpos($content, 'не можем найти') && !\strpos($content, "couldn't find") && !\strpos($content, 'tidak dapat menemukan')) {
                        if (isset($initData['phone'])) {
                            $data['phone'] = new ResultDataField('string', 'Phone', $initData['phone'], 'Телефон', 'Телефон');
                        } else {
                            $data['email'] = new ResultDataField('string', 'Email', $initData['email'], 'E-mail', 'E-mail');
                        }
                        $data['result'] = new ResultDataField('string', 'Result', \strpos($content, 'нашли несколько') || \strpos($content, 'нашли более') ? 'Найдено несколько учетных записей' : 'Найдена учетная запись', 'Результат', 'Результат');
                        $data['result_code'] = new ResultDataField('string', 'ResultCode', \strpos($content, 'нашли несколько') ? 'FOUND_SEVERAL' : 'FOUND', 'Код результата', 'Код результата');
                        $resultData->addResult($data);
                    }
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    //                    $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='success' WHERE id=" . $swapData['session']->id);
                    $mysqli->query('UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                } elseif (\strpos($content, 'количество попыток') || \strpos($content, 'password_reset_help')) {
                    //                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='limit' WHERE id=" . $swapData['session']->id);
                    $mysqli->query('UPDATE isphere.session SET proxyid=NULL WHERE id='.$swapData['session']->id);
                    $mysqli->query('UPDATE isphere.proxy SET successtime=now(),success=ifnull(success,0)+1 WHERE id='.$swapData['session']->proxyid);
                    unset($swapData['session']);
                } elseif (!$content) {
                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='empty' WHERE id=".$swapData['session']->id);
                    unset($swapData['session']);
                } else {
                    $error = 'Невозможно обработать ответ';
                    \file_put_contents('./logs/twitter/twitter_err_'.\time().'.html', $content);
                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 30 minute),sessionstatusid=6,statuscode='invalid' WHERE id=".$swapData['session']->id);
                    unset($swapData['session']);
                }
            } else {
                //                file_put_contents('./logs/twitter/twitter_url_'.time().'.html',$content);

                $resultData = new ResultDataList();
                //                    $rContext->setResultData($resultData);
                $rContext->setFinished();
            }
            $rContext->setSwapData($swapData);
        }

        if (!$error && ($swapData['iteration'] >= 30)) {
            $error = 'Превышено количество попыток получения ответа';
        }
        if ($error && ($swapData['iteration'] >= 5)) {
            $rContext->setError($error);
            $rContext->setFinished();

            return false;
        }

        $rContext->setSleep(2);

        return true;
    }
}

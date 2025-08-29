<?php

class RZDPlugin implements PluginInterface
{
    public function getName()
    {
        return 'RZD';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Поиск в РЖД',
            'rzd_email' => 'РЖД - проверка email на наличие пользователя',
            'rzd_nick' => 'РЖД - проверка логина на наличие пользователя',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Поиск в РЖД';
    }

    public function getSessionData()
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

        $mysqli->query("UPDATE isphere.session s SET request_id=".$reqId." WHERE request_id IS NULL AND sessionstatusid=2 AND sourceid=68 AND lasttime<DATE_SUB(now(), INTERVAL 5 SECOND) ORDER BY lasttime limit 1");
        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sourceid=68 AND request_id=".$reqId." ORDER BY lasttime limit 1");

        if($result)
        {
            $row = $result->fetch_object();

            if ($row)
            {
                $sessionData = new \StdClass;
                $sessionData->id = $row->id;
                $sessionData->code = $row->captcha;
                $sessionData->token = $row->token;
                $sessionData->starttime = $row->starttime;
                $sessionData->lasttime = $row->lasttime;
                $sessionData->cookies = $row->cookies;
                $sessionData->proxyid = $row->proxyid;
                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;
                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1,statuscode='used',request_id=NULL WHERE id=".$sessionData->id);
//                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3 WHERE used>=1 AND id=".$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        global $mysqli;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],4);

        if($checktype=='email' && !isset($initData['email'])) {
            $rContext->setFinished();
//            $rContext->setError('Не указаны параметры для поиска (email)');

            return false;
        }

        if($checktype=='nick' && !isset($initData['nick'])) {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (псевдоним)');
            return false;
        }

        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        $swapData['session'] = $this->getSessionData();
        if(!$swapData['session']) {
            if ($swapData['iteration']>=10) {
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
            } else {
                $rContext->setSwapData($swapData);
                $rContext->setSleep(1);
            }
            return false;
        }
        $rContext->setSwapData($swapData);

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

//        $params = array('EMAIL' => $initData['email']);
//        $url = 'https://www.rzd.ru/selfcare/uniqueEmail';
        $url = 'https://www.rzd.ru/auth/check/'.($checktype=='email'?'email':'login').'?'.http_build_query($checktype=='email'?array('email'=>$initData['email']):array('login'=>$initData['nick']));
        $header = array(
          'Origin: https://www.rzd.ru',
//          'Referer: https://www.rzd.ru/selfcare/register/ru',
          'Referer: https://www.rzd.ru/',
//          'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
//          'X-Requested-With: XMLHttpRequest',
        );
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
//        curl_setopt($ch, CURLOPT_POST, true);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        if ($swapData['session']->proxy) {
            curl_setopt($ch,CURLOPT_PROXY,$swapData['session']->proxy);
            if ($swapData['session']->proxy_auth) {
                curl_setopt($ch,CURLOPT_PROXYUSERPWD,$swapData['session']->proxy_auth); 
                curl_setopt($ch,CURLOPT_PROXYAUTH,CURLAUTH_ANY); 
            }
        }

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],4);

        $error = ($swapData['iteration']>5) && false; //curl_error($rContext->getCurlHandler());
        if(!$error) {
            $content = curl_multi_getcontent($rContext->getCurlHandler());
            file_put_contents('./logs/rzd/rzd_'.time().'.txt',$content);

            $res = json_decode($content, true);               
            if($res && isset($res['ldapExist'])){
                $resultData = new ResultDataList();
/*
                if (isset($res['code'])){
                    if ($res['code']=='EMAIL_NOT_UNIQUE') {
                        $data['result'] = new ResultDataField('string','result', $initData['email'].' зарегистрирован на сайте rzd.ru', 'Результат', 'Результат');
                        $data['result_code'] = new ResultDataField('string','result_code', 'FOUND', 'Код результата', 'Код результата');
                        $resultData->addResult($data);
                    }
                }
*/
                if ($res['ldapExist']) {
                    $data['result'] = new ResultDataField('string','result', ($checktype=='email'?$initData['email']:$initData['nick']).' зарегистрирован на сайте rzd.ru', 'Результат', 'Результат');
                    $data['result_code'] = new ResultDataField('string','result_code', 'FOUND', 'Код результата', 'Код результата');
                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
                return true;
            } elseif (strpos($content,'недоступен') || strpos($content,'технически') || strpos($content,'502 Bad Gateway')) {
                $error = "Сервис временно недоступен";
                $mysqli->query("UPDATE isphere.session SET statuscode='unavailable' WHERE statuscode='used' AND id=".$swapData['session']->id);
            } elseif (!$content) {
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 10 minute),sessionstatusid=6,statuscode='empty' WHERE statuscode='used' AND id=".$swapData['session']->id);
            } else {
                file_put_contents('./logs/rzd/rzd_err_'.time().'.txt',$content);
                $mysqli->query("UPDATE isphere.session SET statuscode='invalid' WHERE statuscode='used' AND id=".$swapData['session']->id);
                if ($swapData['iteration']>=3) $error = "Некорректный ответ";
            }
        }

        if(!$error && $swapData['iteration']>10)
            $error='Превышено количество попыток получения ответа';

        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();
            return false;
        }

        return true;
    }
}

?>
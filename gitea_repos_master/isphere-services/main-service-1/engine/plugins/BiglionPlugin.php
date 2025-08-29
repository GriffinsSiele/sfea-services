<?php

class BiglionPlugin implements PluginInterface
{
    public function getName()
    {
        return 'Biglion';
    }

    public function getTitle()
    {
        return 'Биглион - проверка телефона на наличие учетной записи';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=51 AND unix_timestamp(now())-unix_timestamp(lasttime)>3 ORDER BY lasttime limit 1");

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

                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1 WHERE id=".$sessionData->id);
//                $mysqli->query("UPDATE isphere.proxy SET lasttime=now() WHERE id=".$row->proxyid);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $rContext->setFinished();
        $rContext->setError('Сервис временно недоступен');
        return false;

        if(!isset($initData['phone'])) {
            $rContext->setFinished();
            $rContext->setError('Указаны не все обязательные параметры (телефон)');

            return false;
        }

        if (strlen($initData['phone'])==10)
            $initData['phone']='7'.$initData['phone'];
        if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
            $initData['phone']='7'.substr($initData['phone'],1);

/*
        if(substr($initData['phone'],0,2)!='79')
        {
            $rContext->setFinished();
            $rContext->setError('Поиск производится только по мобильным телефонам в коде 9xx');

            return false;
        }
*/
        if(substr($initData['phone'],0,1)!='7'){
            $rContext->setFinished();
//            $rContext->setError('Поиск производится только по российским телефонам');
            return false;
        }

        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
        $swapData['session'] = $this->getSessionData();
        if(!$swapData['session']) {
            if (isset($swapData['iteration']) && ($swapData['iteration']>=10)) {
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
            } else {
                $rContext->setSwapData($swapData);
                $rContext->setSleep(1);
            }
            return false;
        }
        $rContext->setSwapData($swapData);

/*
        $rContext->setFinished();
        $rContext->setError('Сервис временно недоступен)');
        return false;
*/
        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $url = 'https://1.elecsnet.ru/NotebookFront/services/0mhp/GetMerchantInfo';
        $header = array(
          'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
          'Origin: https://1.elecsnet.ru',
          'Referer: https://1.elecsnet.ru/NotebookFront/services/0mhp/default.aspx?merchantId=8187',
          'X-Requested-With: XMLHttpRequest',
        );
        $params = array(
            "merchantId" => "5886",
            "paymentTool" => "9",
            "merchantFields[1]" => substr($initData['phone'],1),
        );
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
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

//        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
//        $rContext->setSwapData($swapData);
        $error = ($swapData['iteration']>=5)?curl_error($rContext->getCurlHandler()):false;
        if(!$error) {
            $content = curl_multi_getcontent($rContext->getCurlHandler());
//            file_put_contents('./logs/elecsnet/biglion_'.time().'.txt',$content);

            $res = json_decode($content, true);               
            if($res){
                $resultData = new ResultDataList();
                if (isset($res['isSuccess'])){
                    if ($res['isSuccess']) {
                        $data['result'] = new ResultDataField('string','result', 'По телефону '.$initData['phone'].' найдена учетная запись Биглион', 'Результат', 'Результат');
                        $data['result_code'] = new ResultDataField('string','result_code', 'FOUND', 'Код результата', 'Код результата');
                        $resultData->addResult($data);
                    }
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
            } elseif ($res && !isset($res['message'])) {
                $error = "Сервис временно недоступен";
                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
            } elseif (strpos($content,'недоступен') || strpos($content,'невозможно') || strpos($content,'техническим') || strpos($content,'502 Bad Gateway') || strpos($content,'Service Unavailable')) {
                $error = "Сервис временно недоступен";
                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
            } elseif ($content=="Too many requests") {
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 hour),sessionstatusid=6,statuscode='limit' WHERE id=" . $swapData['session']->id);
                unset($swapData['session']);
            } else {
                if ($content) {
                    file_put_contents('./logs/elecsnet/biglion_err_'.time().'.txt',$content);
                    $error = "Некорректный ответ";
                }
//                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=5,statuscode='invalidanswer' WHERE id=".$swapData['session']->id);
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 10 minute),sessionstatusid=6,statuscode='invalidanswer' WHERE id=" . $swapData['session']->id);
                unset($swapData['session']);
            }
        }

        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>=3)
            $error='Превышено количество попыток получения ответа';

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

?>
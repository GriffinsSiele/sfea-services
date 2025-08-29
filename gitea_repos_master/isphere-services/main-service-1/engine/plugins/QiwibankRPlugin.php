<?php

class QiwibankRPlugin implements PluginInterface
{
    public function getName()
    {
        return 'Qiwibank';
    }

    public function getTitle()
    {
        return 'Поиск в КИВИ Банк';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=36 ORDER BY used limit 1");

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

//                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1 WHERE id=".$sessionData->id);
                $mysqli->query("UPDATE isphere.session SET statuscode='used',used=ifnull(used,0)+1 WHERE id=".$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

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
            $rContext->setError('Поиск производится только по российским телефонам');
            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $swapData['session'] = $this->getSessionData();
        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        if(!$swapData['session']) {
//            if ($swapData['iteration']>10) {
                $rContext->setFinished();
                $rContext->setError('Нет доступных аккаунтов для выполнения запроса');
//            }
//            $rContext->setSleep(3);
            return false;
        }

        $rContext->setSwapData($swapData);

        ////////////////////////////////////////////////////////////////////////////////////////////////////
        $ch = $rContext->getCurlHandler();

        $params = json_encode(array(
            'bankId' => 100000000009,
            'phone' => $initData['phone'],
            'srcCba' => '40817810701003645985',
        ));
        $header[] = 'Authorization: Bearer '.$swapData['session']->token;
        $header[] = 'Content-Type: application/json;charset=UTF-8';
        $url = 'https://online.raiffeisen.ru/rest/1/transfer/contact/pam';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $rContext->setCurlHandler($ch);
//        echo implode("\n",$header)."\n".$params."\n";
        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $error = ($swapData['iteration']>5) && curl_error($rContext->getCurlHandler());
        if(!$error) {
            $content = curl_multi_getcontent($rContext->getCurlHandler());
//            file_put_contents('./logs/raiffeisen/qiwibank_'.time().'.txt',$content);

            $res = json_decode($content, true);               
            if(isset($res['recipient']) || isset($res['_form'])){
                if (isset($swapData['session']))
                    $mysqli->query("UPDATE isphere.session SET statuscode='success',success=ifnull(success,0)+1 WHERE id=".$swapData['session']->id);
                $resultData = new ResultDataList();
                if (isset($res['recipient'])) {
                    $data['name'] = new ResultDataField('string','name',$res['recipient'].'.','ФИО','ФИО');
                    $data['result'] = new ResultDataField('string','result', 'По телефону '.$initData['phone'].' найден 1 клиент', 'Результат', 'Результат');
                    $data['result_code'] = new ResultDataField('string','result_code', 'FOUND', 'Код результата', 'Код результата');

                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
/*
            } elseif (isset($res['resultCode']) && ($res['resultCode']=='REQUEST_RATE_LIMIT_EXCEEDED')) {
                if (isset($swapData['session']))
                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 hour),sessionstatusid=6,statuscode='exceeded' WHERE id=" . $swapData['session']->id);
                unset($swapData['session']);
                $rContext->setSwapData($swapData);
                return true;
            } elseif (isset($res['errorMessage'])) {
                file_put_contents('./logs/banks/'.$swapData['source'].($swapData['source']=='sbp'?'_'.$swapData['sbp']:'').'_err_'.time().'.txt',$content);
                $error = $res['errorMessage'];
*/
            } elseif ($content) {
                file_put_contents('./logs/raiffeisen/qiwibank_err_'.time().'.txt',$content);
                $error = "Некорректный ответ";
            }
        }
        $rContext->setSwapData($swapData);

        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>3)
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
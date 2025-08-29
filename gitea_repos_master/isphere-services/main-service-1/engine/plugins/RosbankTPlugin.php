<?php

class RosbankTPlugin implements PluginInterface
{
    public function getName()
    {
        return 'Rosbank';
    }

    public function getTitle()
    {
        return 'Поиск в Росбанк';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=29 ORDER BY lasttime limit 1");

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

        $swapData['source'] = 'sbp';

        $rContext->setSwapData($swapData);

        ////////////////////////////////////////////////////////////////////////////////////////////////////
        $ch = $rContext->getCurlHandler();

        $params = array(
            'pointer' => '+'.$initData['phone'],
            'pointerType' => 'phone',
            'pointerSource' => $swapData['source'],
            'sessionid' => $swapData['session']->token,
        );
        if ($swapData['source']=='sbp') {
            $params['bankMemberId'] = 100000000012;
        }
        $url = 'https://api.tinkoff.ru/v1/get_requisites?'.http_build_query($params);
        curl_setopt($ch, CURLOPT_URL, $url);
        $rContext->setCurlHandler($ch);

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
//            file_put_contents('./logs/banks/'.$swapData['source'].($swapData['source']=='sbp'?'_'.$swapData['sbp']:'').'_'.time().'.txt',$content);

            $res = json_decode($content, true);               
            if($res && isset($res['payload'])){
                if (isset($swapData['data'])) {
                    $resultData = $swapData['data'];
                } else {
                    $resultData = new ResultDataList();
                }
                foreach($res['payload'] as $card) {
                    $data = array();
                    foreach($card['displayFields'] as $elem) {
                        if ($elem['name']=='maskedFIO')
                            $data['name'] = new ResultDataField('string','name',$elem['value'],'ФИО','ФИО');
                        if ($elem['name']=='maskedPAN')
                            $data['card'] = new ResultDataField('string','card',$elem['value'],'Номер карты','Номер карты');
                    }
//                    if (isset($card['brand']['name']))
//                        $data['bank'] = new ResultDataField('string','bank',$card['brand']['name'],'Банк','Банк');
                    $data['result'] = new ResultDataField('string','result', 'По телефону '.$initData['phone'].' найден 1 клиент', 'Результат', 'Результат');
                    $data['result_code'] = new ResultDataField('string','result_code', 'FOUND', 'Код результата', 'Код результата');

                    $resultData->addResult($data);
                }
//                $swapData['iteration']--;
//                $swapData['data'] = $resultData;
//                if ($swapData['source']=='external') {
//                    $swapData['source'] = 'internal';
//                } elseif ($swapData['source']=='internal') {
//                    $swapData['source'] = 'sbp';
//                    $swapData['sbp'] = 0;
//                } elseif ($swapData['source']=='sbp' && $swapData['sbp']+1<sizeof($this->sbp_banks)) {
//                    $swapData['sbp']++;
//                } else {
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
//                }
            } elseif (isset($res['resultCode']) && ($res['resultCode']=='REQUEST_RATE_LIMIT_EXCEEDED')) {
                if (isset($swapData['session']))
                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 hour),sessionstatusid=6,statuscode='exceeded' WHERE id=" . $swapData['session']->id);
//                    $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='exceeded' WHERE id=" . $swapData['session']->id);
                unset($swapData['session']);
                $rContext->setSwapData($swapData);
                return true;
            } elseif (isset($res['errorMessage'])) {
                file_put_contents('./logs/banks/'.$swapData['source'].($swapData['source']=='sbp'?'_'.$swapData['sbp']:'').'_err_'.time().'.txt',$content);
                $error = $res['errorMessage'];
            } else {
                file_put_contents('./logs/banks/'.$swapData['source'].($swapData['source']=='sbp'?'_'.$swapData['sbp']:'').'_err_'.time().'.txt',$content);
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
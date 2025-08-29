<?php

class AlfabankSPlugin implements PluginInterface
{
    private $aes;

    public function getName()
    {
        return 'Alfabank';
    }

    public function getTitle()
    {
        return 'Поиск в Alfabank';
    }

    public function encrypt($text)
    {
        $key = hex2bin(substr($this->aes,0,32));
        $iv = hex2bin(substr($this->aes,32,32));
        $cipher = "aes-128-cbc";
        $raw = openssl_encrypt($text, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
        $result = base64_encode($raw);
        return $result;
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,data,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND data>'' AND sourceid=33 AND unix_timestamp(now())-unix_timestamp(lasttime)>3 ORDER BY lasttime limit 1");

        if($result)
        {
            $row = $result->fetch_object();

            if ($row)
            {
                $sessionData = new \StdClass;

                $sessionData->id = $row->id;
                $sessionData->code = $row->captcha;
                $sessionData->token = $row->token;
                $sessionData->data = $row->data;
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
//            $rContext->setError('Поиск производится только по российским телефонам');
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

        $host = 'https://online.sovcombank.ru';
        $url = $host.'/ib.php?';
        $this->aes = $swapData['session']->data;

        if (!isset($swapData['operationId'])) {
            $url .= 'do=sbp_identification';
            $params = array(
                'ibPaySBP_sum' => $this->encrypt('100'),
                'ibPaySBP_phone' => $this->encrypt(substr($initData['phone'],1)),
                'ibPaySBP_text' => $this->encrypt(''),
                'ibPaySBP_account' => $this->encrypt('40817810050117793356'),
                '_ts' => round(microtime(true)*1000),
                '_nts' => $swapData['session']->token,
            );
        } else {
            $url .= 'do=sbp_payment_check';
            $params = array(
                'bank_code' => $this->encrypt('100000000008'),
                'operationId' => $this->encrypt($swapData['operationId']),
                'message' => $this->encrypt(''),
                'sum' => $this->encrypt('100'),
                'phone' => $this->encrypt(substr($initData['phone'],1)),
                'account' => $this->encrypt('40817810050117793356'),
                '_ts' => round(microtime(true)*1000),
                '_nts' => $swapData['session']->token,
            );
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
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

            $res = json_decode($content, true);               
            if($res && !isset($swapData['operationId'])) {
//                file_put_contents('./logs/sovcombank/alfabank_id_'.time().'.txt',$content);
                if (isset($res['data']['operationId'])) {
                    $swapData['operationId'] = $res['data']['operationId'];
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);
                } else {
                    file_put_contents('./logs/sovcombank/alfabank_id_err_'.time().'.txt',$content);
                    $error = "Ошибка при отправке запроса";
                }
            } elseif($res && isset($swapData['operationId'])) {
                file_put_contents('./logs/sovcombank/alfabank_'.time().'.txt',$content);
                $resultData = new ResultDataList();
                if (isset($res['data'])) {
                    $data = array();
                    if (isset($res['data']['fullName'])) {
                        $data['name'] = new ResultDataField('string','name',$res['data']['fullName'].'.','ФИО','ФИО');
                        $data['result'] = new ResultDataField('string','result', 'По телефону '.$initData['phone'].' найден 1 клиент', 'Результат', 'Результат');
                        $data['result_code'] = new ResultDataField('string','result_code', 'FOUND', 'Код результата', 'Код результата');
                        $resultData->addResult($data);
                    }
                } 
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            } elseif (isset($res['message']) && strpos($res['message'],'истекло')) {
                if (isset($swapData['session']))
//                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(current_date(),interval 1 day),sessionstatusid=6,statuscode='exceeded' WHERE id=" . $swapData['session']->id);
                    $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='expired' WHERE id=" . $swapData['session']->id);
                unset($swapData['session']);
                $rContext->setSwapData($swapData);
                return true;
            } elseif (isset($res['message'])) {
                file_put_contents('./logs/sovcombank/alfabank_err_'.time().'.txt',$content);
                if (!strpos($res['message'],'administrator'))
                    $error = $res['message'];
            } else {
                file_put_contents('./logs/sovcombank/alfabank_err_'.time().'.txt',$content);
                $error = "Некорректный ответ";
            }
        }

        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>10)
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
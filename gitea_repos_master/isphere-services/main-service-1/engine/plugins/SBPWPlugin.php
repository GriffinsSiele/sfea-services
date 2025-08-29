<?php

class SBPWPlugin implements PluginInterface
{
    private $banks = array(
        'gazprombank_phone' => array('id'=>1,'name'=>'Gazprombank','title'=>'Газпромбанк'),
        'rnko_phone' => array('id'=>2,'name'=>'RNKO','title'=>'РНКО Платежный центр'),
        'skb_phone' => array('id'=>3,'name'=>'SKB','title'=>'СКБ'),
        'tinkoff_phone' => array('id'=>4,'name'=>'Tinkoff','title'=>'Тинькофф'),
        'vtb_phone' => array('id'=>5,'name'=>'VTB','title'=>'ВТБ'),
        'akbars_phone' => array('id'=>6,'name'=>'AkBars','title'=>'Ак Барс Банк'),
        'raiffeisen_phone' => array('id'=>7,'name'=>'Raiffeisen','title'=>'Райффайзенбанк'),
        'alfabank_phone' => array('id'=>8,'name'=>'Alfabank','title'=>'Альфа Банк'),
        'qiwibank_phone' => array('id'=>9,'name'=>'Qiwibank','title'=>'Киви Банк'),
        'psbank_phone' => array('id'=>10,'name'=>'PSBank','title'=>'Промсвязьбанк'),
        'rosbank_phone' => array('id'=>12,'name'=>'Rosbank','title'=>'Росбанк'),
        'sovcombank_phone' => array('id'=>13,'name'=>'Sovcombank','title'=>'Совкомбанк'),
        'rsb_phone' => array('id'=>14,'name'=>'RSB','title'=>'Русский стандарт'),
        'openbank_phone' => array('id'=>15,'name'=>'Openbank','title'=>'Открытие'),
        'pochtabank_phone' => array('id'=>16,'name'=>'Pochtabank','title'=>'Почта Банк'),
        'rshb_phone' => array('id'=>20,'name'=>'RSHB','title'=>'Россельхозбанк'),
        'yandexmoney_phone' => array('id'=>22,'name'=>'YandexMoney','title'=>'Яндекс.Деньги'),
        'mkb_phone' => array('id'=>25,'name'=>'MKB','title'=>'МКБ'),
        'avangard_phone' => array('id'=>28,'name'=>'Avangard','title'=>'Банк Авнгард'),
        'unicredit_phone' => array('id'=>30,'name'=>'Unicredit','title'=>'ЮниКредит Банк'),
        'finam_phone' => array('id'=>40,'name'=>'Finam','title'=>'Банк ФИНАМ'),
        'gazenergobank_phone' => array('id'=>43,'name'=>'Gazenergobank','title'=>'Газэнергобанк'),
        'zenit_phone' => array('id'=>45,'name'=>'Zenit','title'=>'Банк Зенит'),
        'absolutbank_phone' => array('id'=>47,'name'=>'Absolutbank','title'=>'Абсолютбанк'),
        'platina_phone' => array('id'=>48,'name'=>'Platina','title'=>'Банк Платина'),
        'vbrr_phone' => array('id'=>49,'name'=>'VBRR','title'=>'ВБРР'),
        'levoberezhniy_phone' => array('id'=>52,'name'=>'Levoberezhniy','title'=>'Банк Левобережный'),
        'vestabank_phone' => array('id'=>53,'name'=>'Vestabank','title'=>'Банк Веста'),
        'neyvabank_phone' => array('id'=>63,'name'=>'Neyvabank','title'=>'Банк Нейва'),
    );

    public function getName($checktype = '')
    {
        return ($checktype && isset($this->banks[$checktype]))?$this->banks[$checktype]['name']:'SBP';
//        return 'SBP';
    }

    public function getTitle($checktype = '')
    {
        return 'Поиск в '.(($checktype && isset($this->banks[$checktype]))?$this->banks[$checktype]['title']:'СБП');
//        return 'Поиск в СБП';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=47 AND unix_timestamp(now())-unix_timestamp(lasttime)>3 ORDER BY lasttime limit 1");

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

        $checktype = $initData['checktype'];

        if (!isset($this->banks[$checktype])) {
            $rContext->setFinished();
            $rContext->setError('Неверный код банка-участника СБП '.$checktype);

            return false;
        }

        $bank_id = 100000000000+$this->banks[$checktype]['id'];

        if(!isset($initData['phone'])) {
            $rContext->setFinished();
            $rContext->setError('Указаны не все обязательные параметры (телефон)');

            return false;
        }

        if (strlen($initData['phone'])==10)
            $initData['phone']='7'.$initData['phone'];
        if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
            $initData['phone']='7'.substr($initData['phone'],1);


        if(substr($initData['phone'],0,2)!='79')
        {
            $rContext->setFinished();
//            $rContext->setError('Поиск производится только по мобильным телефонам в коде 9xx');
            return false;
        }
/*
        if(substr($initData['phone'],0,1)!='7'){
            $rContext->setFinished();
//            $rContext->setError('Поиск производится только по российским телефонам');
            return false;
        }
*/
        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $swapData['session'] = $this->getSessionData();
        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
        $rContext->setSwapData($swapData);

        if(!$swapData['session']) {
            if ($swapData['iteration']>10) {
                $rContext->setFinished();
                $rContext->setError('Нет доступных аккаунтов для выполнения запроса');
            }
            $rContext->setSleep(1);
            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////
        $ch = $rContext->getCurlHandler();

        $params = json_encode(array(
            'amount' => '1',
            'fields' => array(
                '{account}' => $initData['phone'],
                '{bank}' => $bank_id,
            ),
        ));
        $header[] = 'Content-Type: application/json;charset=UTF-8';
        $url = 'https://wallet.webmoney.ru/srv/telepay/search/5247/amount';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = $initData['checktype'];

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $error = ($swapData['iteration']>5) && curl_error($rContext->getCurlHandler());
        if(!$error) {
            $content = curl_multi_getcontent($rContext->getCurlHandler());
            file_put_contents('./logs/webmoney/'.$checktype.'_'.time().'.txt',$content);

            $res = json_decode($content, true);               
            if($res && isset($res['result']['output'][0]['value'])){
                if (isset($swapData['session']))
                    $mysqli->query("UPDATE isphere.session SET statuscode='success',success=ifnull(success,0)+1 WHERE id=".$swapData['session']->id);

                $resultData = new ResultDataList();
                $data = array();
                $data['name'] = new ResultDataField('string','name',$res['result']['output'][0]['value'],'ФИО','ФИО');
                $data['result'] = new ResultDataField('string','result', 'По телефону '.$initData['phone'].' найден 1 клиент', 'Результат', 'Результат');
                $data['result_code'] = new ResultDataField('string','result_code', 'FOUND', 'Код результата', 'Код результата');

                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            } elseif (isset($res['message']) && strpos($res['message'],'уточните')) {
                $resultData = new ResultDataList();
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            } elseif (isset($res['Message'])) {
                if (strpos($res['Message'],'denied')) {
                    unset($swapData['session']);
                    $mysqli->query("UPDATE isphere.session SET statuscode='finished',endtime=now(),sessionstatusid=5 WHERE id=".$swapData['session']->id);
                } else {
                    file_put_contents('./logs/webmoney/'.$checktype.'_err_'.time().'.txt',$content);
                    $error = $res['errorMessage'];
                }
            } elseif ($content=='api_session_end' || $content=='logout') {
                $mysqli->query("UPDATE isphere.session SET statuscode='finished',endtime=now(),sessionstatusid=5 WHERE id=".$swapData['session']->id);
                unset($swapData['session']);
            } else {
                file_put_contents('./logs/webmoney/'.$checktype.'_err_'.time().'.txt',$content);
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
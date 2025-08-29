<?php

class SberSPlugin implements PluginInterface
{
    public function getName()
    {
        return 'Sberbank';
    }

    public function getTitle()
    {
        return 'Поиск в Сбербанк Онлайн';
    }

     public function str_uprus($text) {
        $up = array(
                'а' => 'А',
                'б' => 'Б',
                'в' => 'В',
                'г' => 'Г',
                'д' => 'Д',
                'е' => 'Е',
                'ё' => 'Е',
                'ж' => 'Ж',
                'з' => 'З',
                'и' => 'И',
                'й' => 'Й',
                'к' => 'К',
                'л' => 'Л',
                'м' => 'М',
                'н' => 'Н',
                'о' => 'О',
                'п' => 'П',
                'р' => 'Р',
                'с' => 'С',
                'т' => 'Т',
                'у' => 'У',
                'ф' => 'Ф',
                'х' => 'Х',
                'ц' => 'Ц',
                'ч' => 'Ч',
                'ш' => 'Ш',
                'щ' => 'Щ',
                'ъ' => 'Ъ',
                'ы' => 'Ы',
                'ь' => 'Ь',
                'э' => 'Э',
                'ю' => 'Ю',
                'я' => 'Я',
        );
        if (preg_match("/[а-яё]/", $text))
            $text = strtr($text, $up);
        return $text;
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=33 AND unix_timestamp(now())-unix_timestamp(lasttime)>3 ORDER BY lasttime limit 1");

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
//            $rContext->setError('Поиск производится только по российским телефонам');
            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $swapData['session'] = $this->getSessionData();
        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        if(!$swapData['session']) {
//            if ($swapData['iteration']>5) {
                $rContext->setFinished();
                $rContext->setError('Нет доступных аккаунтов для выполнения запроса');
//            }
//            $rContext->setSleep(1);
            return false;
        }

        $rContext->setSwapData($swapData);

        ////////////////////////////////////////////////////////////////////////////////////////////////////
        $ch = $rContext->getCurlHandler();

        $params = array(
            'do' => 'sberGet',
            'ibPaySber_sum' => 100,
            'ibPaySber_phone' => substr($initData['phone'],1),
            'ibPaySber_text' => 1,
            'ibPaySber_account' => 1,
            '_nts' => $swapData['session']->token,
        );
        $url = 'https://online.sovcombank.ru/ib.php?'.http_build_query($params);
        curl_setopt($ch, CURLOPT_URL, $url);
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
//            file_put_contents('./logs/sovcombank/sber_'.time().'.txt',$content);

            $res = json_decode($content, true);               
            if($res && isset($res['data']['options'])){
                $resultData = new ResultDataList();
                foreach($res['data']['options'] as $card) {
                    $data = array();
                    if (isset($card['card']))
                        $data['card'] = new ResultDataField('string','card',$card['card'],'Номер карты','Номер карты');
                    if (isset($card['name'])) {
                        $data['name'] = new ResultDataField('string','name',$card['name'],'ФИО','ФИО');

                        if (isset($initData['first_name'])) {
                            $split_name = explode(' ',$this->str_uprus($card['name']));
                            $first_name='';
                            for($i=0; $i<sizeof($split_name)-1; $i++)
                                $first_name = trim($first_name.' '.$split_name[$i]);
                            $last_name = $split_name[sizeof($split_name)-1];

                            if ($this->str_uprus($initData['first_name'])==$first_name) {
                                if (isset($initData['last_name']) && ($this->str_uprus(mb_substr($initData['last_name'],0,1))==mb_substr($last_name,0,1))) {
                                    $match_code = 'MATCHED';
                                } else {
                                    $match_code = 'MATCHED_NAME_ONLY';
                                }
                            } else {
                                $match_code = 'NOT_MATCHED';
                            }
                            $data['match_code'] = new ResultDataField('string','match_code', $match_code, 'Результат сравнения имени', 'Результат сравнения имени');
                        }
                    }
                    $data['result'] = new ResultDataField('string','result', 'По телефону '.$initData['phone'].' найден 1 клиент', 'Результат', 'Результат');
                    $data['result_code'] = new ResultDataField('string','result_code', 'FOUND', 'Код результата', 'Код результата');

                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            } elseif (isset($res['message']) && strpos($res['message'],'невозможен')) {
                $resultData = new ResultDataList();
                $rContext->setResultData($resultData);
                $rContext->setFinished();
/*
            } elseif (isset($res['message']) && strpos($res['message'],'лимит')) {
                if (isset($swapData['session']))
                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(current_date(),interval 1 day),sessionstatusid=6 WHERE id=" . $swapData['session']->id);
                unset($swapData['session']);
                $rContext->setSwapData($swapData);
*/
            } elseif (isset($res['message']) && strpos($res['message'],'истекло')) {
                if (isset($swapData['session']))
//                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(current_date(),interval 1 day),sessionstatusid=6,statuscode='exceeded' WHERE id=" . $swapData['session']->id);
                    $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='expired' WHERE id=" . $swapData['session']->id);
                unset($swapData['session']);
                $rContext->setSwapData($swapData);
            } elseif (isset($res['message'])) {
                file_put_contents('./logs/sovcombank/sber_err_'.time().'.txt',$content);
                if (!strpos($res['message'],'administrator'))
                    $error = $res['message'];
            } elseif ($content) {
                file_put_contents('./logs/sovcombank/sber_err_'.time().'.txt',$content);
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
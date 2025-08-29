<?php

class PFRPlugin implements PluginInterface
{
    public function getName($checktype = '')
    {
        return 'PFR';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'ПФР - проверка СНИЛС',
            'pfr_person' => 'ПФР - проверка СНИЛС',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'ПФР - проверка СНИЛС';
    }

    public function getSessionData()
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

        $mysqli->query("UPDATE isphere.session s SET request_id=".$reqId." WHERE sourceid=63 AND request_id IS NULL AND sessionstatusid=2 ORDER BY lasttime limit 1");
        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sourceid=63 AND request_id=".$reqId." ORDER BY lasttime limit 1");

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

                $mysqli->query("UPDATE isphere.session SET statuscode='used',lasttime=now(),used=ifnull(used,0)+1,captcha='',request_id=NULL WHERE id=".$sessionData->id);
            }
        }

        return $sessionData;
    }


    public function prepareRequest(&$rContext)
    {
        global $clientId;
        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],4);

        if(($checktype=='person') && (!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date']) || !isset($initData['snils']) || !$initData['snils']))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (ФИО, дата рождения, СНИЛС)');

            return false;
        }

        if(isset($initData['last_name']) && isset($initData['first_name']) && preg_match("/[^А-Яа-яЁё\s\-\.]/ui", $initData['last_name'].' '.$initData['first_name'].(isset($initData['patronymic'])?' '.$initData['patronymic']:''))){
            $rContext->setFinished();
            $rContext->setError('Имя может содержать только русские буквы');
            return false;
        }

//        $rContext->setError('Сервис временно недоступен');
//        $rContext->setFinished();
//        return false;

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        if(!isset($swapData['session']))
            $swapData['session'] = $this->getSessionData();

        if(!$swapData['session']) {
            if($swapData['iteration']>30) {
                $rContext->setError('Сервис временно недоступен');
                $rContext->setFinished();
                return false;
            }
            $rContext->setSleep(1);
            return false;
        }
        $rContext->setSwapData($swapData);

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();
        $url = 'https://es.pfrf.ru/api/service_checkSnils';

        $params = array(
            'userData[nameLast]' => $initData['last_name'],
            'userData[nameFirst]' => $initData['first_name'],
            'userData[patronymic]' => isset($initData['patronymic'])?$initData['patronymic']:'',
            'userData[birthDate]' => date('d.m.Y',strtotime($initData['date'])),
            'userData[snils]' => substr($initData['snils'],0,3).'-'.substr($initData['snils'],3,3).'-'.substr($initData['snils'],6,3).' '.substr($initData['snils'],9,2),
            'simpleCheck' => 'true',
        );

        $header = array(
            'Accept: application/json, text/plain, */*',
            'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
            'DNT: 1',
            'Connection: keep-alive',
            'Referer: https://es.pfrf.ru/checkSnils',
            'Sec-Fetch-Dest: script',
            'Sec-Fetch-Mode: no-cors',
            'Sec-Fetch-Site: same-site',
        );

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
//        curl_setopt($ch, CURLOPT_COOKIEFILE, '');
//        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
//        curl_setopt($ch, CURLOPT_HEADER, true);
//        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        if ($swapData['session']->proxy) {
            curl_setopt($ch,CURLOPT_PROXY,$swapData['session']->proxy);
            if ($swapData['session']->proxy_auth) {
                curl_setopt($ch,CURLOPT_PROXYUSERPWD,$swapData['session']->proxy_auth); 
                curl_setopt($ch,CURLOPT_PROXYAUTH,CURLAUTH_ANY); 
            }
        }

//        echo date('H:i:s')." ".$swapData['iteration']." ".$swapData['session']->id.": $url\n";
//        var_dump($params); echo "\n\n";

        $rContext->setCurlHandler($ch);
        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;
        global $reqId;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

//        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
//        $rContext->setSwapData($swapData);

        $content = $full_content = curl_multi_getcontent($rContext->getCurlHandler());
        $res = false;

        $error = false; //curl_error($rContext->getCurlHandler());
        if($error){
              $rContext->setError($error);
        }

        $start = strpos($full_content,'{');
        $finish = strrpos($full_content,'}');
        if ($start!==false && $finish!==false && strpos($full_content,'<!DOCTYPE')!==0) {
            $content = substr($full_content,$start,$finish-$start+1);
            $res = json_decode($content, true);
            file_put_contents('./logs/pfr/'.$initData['checktype'].'_'.$swapData['iteration'].'_'.time().'.txt', $content);
        } elseif ($full_content) {
            file_put_contents('./logs/pfr/'.$initData['checktype'].'_err_'.$swapData['iteration'].'_'.time().'.htm', $content);
        }

        if (is_array($res) && isset($res['error']) && $res['error']) {
            if ($res['error']==9107) {
                if (isset($swapData['session'])) {
                    $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,statuscode='needcaptcha',endtime=now() WHERE id=" . $swapData['session']->id);
                }
                unset($swapData['session']);
            } elseif (isset($res['errorMsg']) && $res['errorMsg']) {
                file_put_contents('./logs/pfr/'.$initData['checktype'].'_err_'.time().'.txt',$content);
                if ($swapData['iteration']>=3) $error = $res['errorMsg'];
            }
        } elseif ($res && isset($res['data']['isValid'])) {
            if (isset($swapData['session']))
                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=" . $swapData['session']->id);
//                $mysqli->query("UPDATE isphere.session SET endtime=now(),statuscode='success',sessionstatusid=3 WHERE id=" . $swapData['session']->id);

            $data['SNILS'] = new ResultDataField('string','SNILS', $initData['snils'], 'СНИЛС', 'СНИЛС');
            if ($res['data']['isValid'] && (!isset($initData['patronymic']) || !isset($res['data']['personFIO']['patronymic']) || !$res['data']['personFIO']['patronymic'] || mb_strtoupper(trim($res['data']['personFIO']['patronymic']))==mb_strtoupper(trim($initData['patronymic'])))) {
                $data['Result'] = new ResultDataField('string','Result', 'СНИЛС соответствует ФИО и дате рождения', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string','ResultCode', 'MATCHED', 'Код результата', 'Код результата');
            } else {
                $data['Result'] = new ResultDataField('string','Result', 'СНИЛС не соответствует ФИО и дате рождения', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string','ResultCode', 'NOT_MATCHED', 'Код результата', 'Код результата');
            }

            $matched = array('nameFirst'=>true,'nameLast'=>true,'birthDate'=>true);
            if (isset($res['data']['errors']))
                foreach($res['data']['errors'] as $err)
                    if (isset($err['field'])) $matched[$err['field']]=false;
            if (isset($res['data']['personFIO']['patronymic']) && $res['data']['personFIO']['patronymic']) {
                $data['Patronymic'] = new ResultDataField('string','Patronymic', $res['data']['personFIO']['patronymic'], 'Отчество', 'Отчество');
                if (isset($initData['patronymic']))
                    $matched['patronymic'] = (mb_strtoupper(trim($res['data']['personFIO']['patronymic']))==mb_strtoupper(trim($initData['patronymic'])));
            }
            $data['BirthDateMatched'] = new ResultDataField('string','BirthDateMatched', $matched['birthDate']?'Да':'Нет', 'Дата рождения совпадает с ПФР', 'Дата рождения совпадает с ПФР');
            $data['LastNameMatched'] = new ResultDataField('string','LastNameMatched', $matched['nameLast']?'Да':'Нет', 'Фамилия совпадает с ПФР', 'Фамилия совпадает с ПФР');
            $data['FirstNameMatched'] = new ResultDataField('string','FirstNameMatched', $matched['nameFirst']?'Да':'Нет', 'Имя совпадает с ПФР', 'Имя совпадает с ПФР');
            if (isset($matched['patronymic']))
                $data['PatronymicMatched'] = new ResultDataField('string','PatronymicMatched', $matched['patronymic']?'Да':'Нет', 'Отчество совпадает с ПФР', 'Отчество совпадает с ПФР');

            $resultData = new ResultDataList();
            $resultData->addResult($data);
            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return true;
        } elseif(preg_match('/502 Bad Gateway/', $content)) {
            if (isset($swapData['session'])) {
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='bad' WHERE id=" . $swapData['session']->id);
            }
            unset($swapData['session']);
        } elseif(preg_match('/403 Forbidden/', $content)) {
            if (isset($swapData['session'])) {
                if ($swapData['session']->proxyid>100) {
                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 10 minute),sessionstatusid=6,statuscode='forbidden' WHERE id=" . $swapData['session']->id);
                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 30 minute) WHERE used>=3 AND success/used*3<1 AND id=" . $swapData['session']->id);
                } else {
                    $mysqli->query("UPDATE isphere.session SET proxyid=NULL WHERE sourceid=3 AND sessionstatusid=2 AND proxyid=" . $swapData['session']->proxyid . " AND id<>" . $swapData['session']->id . " ORDER BY lasttime LIMIT 3");
                }
            }
            unset($swapData['session']);
        } elseif(preg_match('/503 Service Temporarily Unavailable/', $content)) {
            if (isset($swapData['session'])) {
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='unavailable' WHERE id=" . $swapData['session']->id);
            }
            unset($swapData['session']);
        } else {
            file_put_contents('./logs/pfr/'.$initData['checktype'].'_err_'.time().'.txt',$content);
            if ($content && ($swapData['iteration']>=3)) $error = "Некорректный ответ ПФР";
            if (isset($swapData['session'])) {
                if ($swapData['session']->proxyid>100)
                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 30 minute),sessionstatusid=6,statuscode='empty' WHERE id=" . $swapData['session']->id);
            }
            unset($swapData['session']);
        }

        if($error || $swapData['iteration']>=10) {
            $rContext->setFinished();
            $rContext->setError($error?$error:'Превышено количество попыток получения ответа');
            return false;
        }

        $rContext->setSwapData($swapData);
        $rContext->setSleep(1);
        return true;
    }
}

?>
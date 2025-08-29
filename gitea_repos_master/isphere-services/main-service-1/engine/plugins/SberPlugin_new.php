<?php

class SberPlugin implements PluginInterface
{
    public function getName()
    {
        return 'Sber';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Поиск учетной записи в Sber',
            'sber_phone' => 'Sber - проверка телефона на наличие пользователя',
            'sber_email' => 'Sber - проверка email на наличие пользователя',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Поиск в Sber';
    }

    public function getSessionData()
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

        $mysqli->query("UPDATE isphere.session s SET request_id=$reqId WHERE sessionstatusid=2 AND sourceid=7 AND unix_timestamp(now())-unix_timestamp(lasttime)>5 ORDER BY lasttime limit 1");
        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sourceid=7 AND request_id=$reqId ORDER BY lasttime limit 1");
        if($result) {
            $row = $result->fetch_object();
            if ($row) {
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
//                $mysqli->query("UPDATE isphere.proxy SET lasttime=now() WHERE id=".$row->proxyid);

                if (!$row->proxyid) {
                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE proxygroup=5 AND enabled=1 AND status=1 AND country='ru' ORDER BY lasttime limit 1");
                    if ($result) {
                        $row = $result->fetch_object();
                        if ($row) {
                            $sessionData->proxyid = $row->proxyid;
                            $sessionData->proxy = $row->proxy;
                            $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;

//                            $mysqli->query("UPDATE isphere.proxy SET lasttime=now() WHERE id=".$row->proxyid);
                            $mysqli->query("UPDATE isphere.session SET proxyid=".$row->proxyid." WHERE id=".$sessionData->id);
                        }
                    }
                }
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        global $mysqli;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if(!isset($initData['phone']) && !isset($initData['email'])) {
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

        ////////////////////////////////////////////////////////////////////////////////////////////////////
/*
        $rContext->setFinished();
        $rContext->setError('Сервис временно недоступен');
        return false;
*/
        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
//        if(!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData();
            if (isset($swapData['session'])) {
                $rContext->setSwapData($swapData);
            }
//        }
        if(!$swapData['session']) {
            if (isset($swapData['iteration']) && ($swapData['iteration']>=30)) {
                $rContext->setFinished();
                $rContext->setError('Слишком много запросов в очереди');
            } else {
                $rContext->setSwapData($swapData);
/*
                if($swapData['iteration']>30) {
                    $rContext->setError('Сервис временно недоступен');
                    $rContext->setFinished();
                    return false;
                }
*/
                $rContext->setSleep(1);
            }
            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $url = 'https://securepayments.sberbank.ru/sbersafe/client/find';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);

        $params = array();
        if (isset($initData['phone']))
            $params['phone'] = $initData['phone'];
        else
            $params['email'] = $initData['email'];
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $header = array(
//            'Accept: application/json',
//            'Accept-: application/json',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
//            'Origin: https://securepayments.sberbank.ru',
//            'Referer: https://securepayments.sberbank.ru/',
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
        curl_setopt($ch, CURLOPT_COOKIEFILE, '');
        curl_setopt($ch, CURLOPT_ENCODING, '');
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

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $error = false; //$swapData['iteration']>3 ? curl_error($rContext->getCurlHandler()) : false;
        if (strpos($error,'timed out') || strpos($error,'connection')) {
            $error = false;
//                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 10 minute),sessionstatusid=6,statuscode='connectionerror' WHERE id=" . $swapData['session']->id);
        }
        if(!$error) {
            $content = curl_multi_getcontent($rContext->getCurlHandler());
            file_put_contents('./logs/sber/sber_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);

            $start = strpos($content,'{');
            $content = trim(substr($content,$start,strlen($content)-$start+1));
            $res = json_decode($content, true);
/*
            $cookies = str_cookies($swapData['session']->cookies);
            foreach (curl_getinfo($rContext->getCurlHandler(),CURLINFO_COOKIELIST) as $cookie) {
                $arr = explode("	",$cookie);
//                if ($arr[0]=='www.sber.ru')
                    $cookies[$arr[5]] = $arr[6];
            }
            $new_cookies = cookies_str($cookies);
            $swapData['session']->cookies = $new_cookies;
            $rContext->setSwapData($swapData);
            $mysqli->query("UPDATE isphere.session SET cookies='$new_cookies' WHERE id=" . $swapData['session']->id);
*/
        }

        if (isset($res) && is_array($res) && isset($res['error']['code']) && $res['error']['code']=='client.find.failed') {
            $resultData = new ResultDataList();
            $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=" . $swapData['session']->id);
            $rContext->setResultData($resultData);
            $rContext->setFinished();
        } elseif (isset($res) && is_array($res) && isset($res['status']) && $res['status']=='SUCCESS') {
            $resultData = new ResultDataList();
            if (isset($initData['phone'])) {
//                $data['phone'] = new ResultDataField('string','Phone',$initData['phone'],'Телефон','Телефон');
            } else {
//                $data['email'] = new ResultDataField('string','Email',$initData['email'],'E-mail','E-mail');
            }
            if (isset($res['client']['createdDate'])) {
                $data['created_date'] = new ResultDataField('string','created_date',$res['client']['createdDate'],'Дата регистрации','Дата регистрации');
            }
            if (isset($res['client']['maskedPhone'])) {
                $data['masked_phone'] = new ResultDataField('string','masked_phone',$res['client']['maskedPhone'],'Телефон','Телефон');
            }
            if (isset($res['client']['uuid'])) {
                $data['uuid'] = new ResultDataField('string','uuid',$res['client']['uuid'],'ID пользователя','ID пользователя');
            }
            $data['result'] = new ResultDataField('string','Result','Найден','Результат','Результат');
            $data['result_code'] = new ResultDataField('string','ResultCode','FOUND','Код результата','Код результата');
            $resultData->addResult($data);
            $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=" . $swapData['session']->id);
            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return false;
/*
        } elseif (strpos($content,'Vaildation Failed')) {
            $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,statuscode='failed',endtime=now() WHERE id=" . $swapData['session']->id);
//            $rContext->setSleep(1);
        } elseif (strpos($content,'Access Denied')) {
            $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,statuscode='denied',endtime=now() WHERE id=" . $swapData['session']->id);
//            $rContext->setSleep(1);
        } elseif (strpos($content,'403 Forbidden')) {
            $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,statuscode='forbidden',endtime=now() WHERE id=" . $swapData['session']->id);
//            $rContext->setSleep(1);
        } elseif (strpos($content,'заблокирован')) {
            $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,statuscode='blocked',endtime=now() WHERE id=" . $swapData['session']->id);
//            $rContext->setSleep(1);
*/
        } elseif (isset($res) && is_array($res) && isset($res['error']['code']) && isset($swapData['iteration']) && $swapData['iteration']>=3) {
            $error=$res['error']['code'];
            if ($content) file_put_contents('./logs/sber/sber_err_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
        } else {
            $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 2 minute),sessionstatusid=6,statuscode='error',proxyid=NULL WHERE id=" . $swapData['session']->id);
//            $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,statuscode='error',endtime=now() WHERE id=" . $swapData['session']->id);
            if ($content) file_put_contents('./logs/sber/sber_err_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
        }

        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>3)
            $error='Превышено количество попыток получения ответа';

        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();
            return false;
        }

        $rContext->setSleep(1);
        return true;
    }
}

?>
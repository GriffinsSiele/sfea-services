<?php

class PochtaPlugin implements PluginInterface
{
    public function getName()
    {
        return 'Pochta';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Поиск в Почте России',
            'pochta_phone' => 'Почта России - проверка телефона на наличие пользователя',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Поиск в Почте России';
    }

    public function getSessionData()
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

        $mysqli->query("UPDATE isphere.session s SET request_id=$reqId WHERE sessionstatusid=2 AND sourceid=56 AND lasttime<DATE_SUB(now(), INTERVAL 1 SECOND) ORDER BY lasttime limit 1");
        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sourceid=56 AND request_id=$reqId ORDER BY lasttime limit 1");
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
/*
                if (!$row->proxyid) {
                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE enabled=1 AND status=1 AND country='ru' ORDER BY lasttime limit 1");
                    if ($result) {
                        $row = $result->fetch_object();
                        if ($row) {
                            $sessionData->proxyid = $row->proxyid;
                            $sessionData->proxy = $row->proxy;
                            $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;

                            $mysqli->query("UPDATE isphere.proxy SET lasttime=now() WHERE id=".$row->proxyid);
                            $mysqli->query("UPDATE isphere.session SET proxyid=".$row->proxyid." WHERE id=".$sessionData->id);
                        }
                    }
                }
*/
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        global $mysqli;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if(!isset($initData['phone'])) {
            $rContext->setFinished();
//            $rContext->setError('Не указаны параметры для поиска (телефон)');

            return false;
        }

        if(isset($initData['phone']) && substr($initData['phone'],0,2)!='79') {
            $rContext->setFinished();
//            $rContext->setError('Поиск производится только по мобильным телефонам в коде 9xx');
            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////
/*
        $rContext->setFinished();
        $rContext->setError('Сервис временно недоступен');
        return false;
*/
//        if(!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData();
            if (isset($swapData['session'])) {
                $swapData['iteration']=1;
                $rContext->setSwapData($swapData);
            }
//        }
        if(!$swapData['session']) {
            if (isset($swapData['iteration']) && ($swapData['iteration']>=30)) {
                $rContext->setFinished();
                $rContext->setError('Слишком много запросов в очереди');
            } else {
                (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
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

//        $url = 'https://www.pochta.ru/parcels?p_p_id=commonPortletV2_WAR_portalportlet&p_p_lifecycle=2&p_p_state=normal&p_p_mode=view&p_p_resource_id=session.get-recipient-data-by-phone&p_p_cacheability=cacheLevelPage&phone='.$initData['phone'];
        $url = 'https://www.pochta.ru/api/nano-apps/api/v1/delivery.recipient-by-phone/'.$initData['phone'];
        curl_setopt($ch, CURLOPT_URL, $url);

        $header = array(
            'Origin: https://www.pochta.ru',
            'Referer: https://www.pochta.ru/parcels',
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
//        curl_setopt($ch, CURLOPT_HEADER, true);
//        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
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
        global $serviceurl;

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
            file_put_contents('./logs/pochta/pochta_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);

            $start = strpos($content,'{');
            $content = trim(substr($content,$start,strlen($content)-$start+1));
            $res = json_decode($content, true);
        }

        if (empty(trim($content))) {
            if ($swapData['iteration']>=5) {
                $error = 'Сервис не отвечает';
            }
        } else {
            $cookies = str_cookies($swapData['session']->cookies);
            foreach (curl_getinfo($rContext->getCurlHandler(),CURLINFO_COOKIELIST) as $cookie) {
//                print 'Response cookie '.$cookie."\n";
                $arr = explode("	",$cookie);
                if (!isset($cookies[$arr[5]]) || $cookies[$arr[5]]!=$arr[6]) {
                    $cookies[$arr[5]] = $arr[6];
//                    print 'New cookie '.$arr[5].' = '.$arr[6]."\n";
                }
            }
            $new_cookies = cookies_str($cookies);
            $swapData['session']->cookies = $new_cookies;
            $rContext->setSwapData($swapData);
//            file_put_contents('./logs/pochta/pochta_'.time().'.cookies',$new_cookies);
            $mysqli->query("UPDATE isphere.session SET cookies='$new_cookies' WHERE id=" . $swapData['session']->id);
        }

        if (strpos($content,'Ошибка 404') || (isset($res) && is_array($res) && sizeof($res)==0)) {
            $resultData = new ResultDataList();
            $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=" . $swapData['session']->id);
            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return true;
        } elseif (isset($res) && is_array($res) && (isset($res['postalCode']) || isset($res['address']))) {
            $resultData = new ResultDataList();
            $data['phone'] = new ResultDataField('string','Phone',$initData['phone'],'Телефон','Телефон');
            if (isset($res['recipientName']))
                $data['name'] = new ResultDataField('string','Name',$res['recipientName'],'Имя','Имя');
            if (isset($res['address']))
                $data['address'] = new ResultDataField('string','Address',$res['address'],'Адрес','Адрес');
            if (isset($res['postalCode']))
                $data['index'] = new ResultDataField('string','Index',$res['postalCode'],'Почтовый индекс','Почтовый индекс');
            if (isset($res['region']))
                $data['region'] = new ResultDataField('string','Region',$res['region'],'Регион','Регион');
            if (isset($res['city']))
                $data['city'] = new ResultDataField('string','City',$res['city'],'Город','Город');
            if (isset($res['settlement']))
                $data['settlement'] = new ResultDataField('string','Settlement',$res['settlement'],'Населенный пункт','Населенный пункт');

            $data['result'] = new ResultDataField('string','Result','Найден','Результат','Результат');
            $data['result_code'] = new ResultDataField('string','ResultCode','FOUND','Код результата','Код результата');
            $resultData->addResult($data);
            $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=" . $swapData['session']->id);
            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return true;
/*
        } elseif (strpos($content,'403 Forbidden')) {
            $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,statuscode='forbidden',endtime=now() WHERE id=" . $swapData['session']->id);
//            $rContext->setSleep(1);
        } elseif (strpos($content,'заблокирован')) {
            $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,statuscode='blocked',endtime=now() WHERE id=" . $swapData['session']->id);
//            $rContext->setSleep(1);
*/
        } elseif (isset($res) && is_array($res) && $swapData['iteration']>1 && (isset($res['error']) || isset($res['humanReadableMessage']))) {
            $error = 'Внутренняя ошибка источника';
            if ($content) file_put_contents('./logs/pochta/pochta_err_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
        } elseif (strpos($content,'Делаем сайт')) {
            $error = 'Сервис временно недоступен';
        } else {
            $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 2 minute),sessionstatusid=6,statuscode='error' WHERE id=" . $swapData['session']->id);
//            $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,statuscode='error',endtime=now() WHERE id=" . $swapData['session']->id);
            if ($content) file_put_contents('./logs/pochta/pochta_err_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
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
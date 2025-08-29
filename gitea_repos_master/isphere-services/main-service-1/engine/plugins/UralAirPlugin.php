<?php

class UralAirPlugin implements PluginInterface
{
    public function getName()
    {
        return 'UralAirlines';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Поиск в Ural Airlines',
            'uralair_phone' => 'Ural Airlines - проверка телефона на наличие пользователя',
            'uralair_email' => 'Ural Airlines - проверка email на наличие пользователя',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Поиск в Ural Airlines';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=41 AND unix_timestamp(now())-unix_timestamp(lasttime)>1 ORDER BY lasttime limit 1");
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
                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1,statuscode='used' WHERE id=".$sessionData->id);
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
            if (strlen($initData['phone'])==10)
                $initData['phone']='7'.$initData['phone'];
            if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
                $initData['phone']='7'.substr($initData['phone'],1);
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

//        if(!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData();
            if (isset($swapData['session'])) {
                $swapData['iteration']=1;
                $rContext->setSwapData($swapData);
            }
//        }
        if(!$swapData['session']) {
            if (isset($swapData['iteration']) && ($swapData['iteration']>=10)) {
                $rContext->setFinished();
                $rContext->setError('Слишком много запросов в очереди');
            } else {
                (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
                $rContext->setSwapData($swapData);
                $rContext->setSleep(1);
            }
            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $url = 'https://my.uralairlines.ru/ajax/registration.php?c=recovery&m=getRecoverySteps';
        $params = array(
            'form-recovery__email' => isset($initData['phone'])?$initData['phone']:$initData['email'],
            'sessid' => $swapData['session']->token,
            'lang' => 'ru',
        );
        $header = array(
          'Accept: application/json, text/javascript, */*; q=0.01',
          'Origin: https://my.uralairlines.ru',
          'Referer: https://my.uralairlines.ru/personal/recovery/',
          'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
          'X-Requested-With: XMLHttpRequest',
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
//        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
        curl_setopt($ch, CURLOPT_COOKIEFILE, '');
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
        $rContext->setSwapData($swapData);
        $error = false; //$swapData['iteration']>3 ? curl_error($rContext->getCurlHandler()) : false;
        if (strpos($error,'timed out') || strpos($error,'connection')) {
            $error = false;
//                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 10 minute),sessionstatusid=6,statuscode='connectionerror' WHERE id=" . $swapData['session']->id);
        }
        if(!$error) {
            $content = curl_multi_getcontent($rContext->getCurlHandler());
//            file_put_contents('./logs/uralair/uralair_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);

            $start = strpos($content,'{');
            $content = trim(substr($content,$start,strlen($content)-$start+1));
            $res = json_decode($content, true);
/*
            $cookies = str_cookies($swapData['session']->cookies);
            foreach (curl_getinfo($rContext->getCurlHandler(),CURLINFO_COOKIELIST) as $cookie) {
                $arr = explode("	",$cookie);
//                if ($arr[0]=='www.uralairlines.ru')
                    $cookies[$arr[5]] = $arr[6];
            }
            $new_cookies = cookies_str($cookies);
            $swapData['session']->cookies = $new_cookies;
            $rContext->setSwapData($swapData);
            $mysqli->query("UPDATE isphere.session SET cookies='$new_cookies' WHERE id=" . $swapData['session']->id);
*/           
        }

        if (isset($res) && is_array($res) && isset($res['status'])) {
            $resultData = new ResultDataList();
            if ($res['status']==0) {
                if (isset($initData['phone'])) {
                    $data['phone'] = new ResultDataField('string','Phone',$initData['phone'],'Телефон','Телефон');
                    $data['email'] = new ResultDataField('string','Email',$res['email'],'E-mail','E-mail');
                } else {
                    $data['phone'] = new ResultDataField('string','Phone',$res['phone'],'Телефон','Телефон');
                    $data['email'] = new ResultDataField('string','Email',$initData['email'],'E-mail','E-mail');
                }
                $data['result'] = new ResultDataField('string','Result','Найден','Результат','Результат');
                $data['result_code'] = new ResultDataField('string','ResultCode','FOUND','Код результата','Код результата');
                $resultData->addResult($data);
            }
            $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->id);
            $rContext->setResultData($resultData);
            $rContext->setFinished();
        } else {
            file_put_contents('./logs/uralair/uralair_err_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
            $rContext->setSleep(1);
        }

        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>5)
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
<?php

class TCPlugin implements PluginInterface
{
    public function getName()
    {
        return 'TrueCaller';
    }

    public function getTitle()
    {
        return 'Поиск в TrueCaller';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=24 ORDER BY lasttime limit 1");

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

                $mysqli->query("UPDATE isphere.session SET lasttime=now(),endtime=now(),sessionstatusid=3,statuscode='used',used=ifnull(used,0)+1 WHERE id=".$sessionData->id);
//                if ($sessionData->proxyid)
//                    $mysqli->query("UPDATE isphere.proxy SET lasttime=now(),used=used+1 WHERE id=".$sessionData->proxyid);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if(!isset($initData['phone']))
        {
            $rContext->setFinished();
            $rContext->setError('Не задан номер телефона');

            return false;
        }

//        if (strlen($initData['phone'])==10)
//            $initData['phone']='7'.$initData['phone'];
//        if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
//            $initData['phone']='7'.substr($initData['phone'],1);

        $country = false;

        if(preg_match("/7[3489]/",substr($initData['phone'],0,2))){
            $country = 'RU';
        }

        if(preg_match("/7[67]/",substr($initData['phone'],0,2))){
            $country = 'KZ';
        }

        if(preg_match("/1/",substr($initData['phone'],0,1))){
            $country = 'US';
        }

        if(preg_match("/30/",substr($initData['phone'],0,2))){
            $country = 'GR';
        }

        if(preg_match("/31/",substr($initData['phone'],0,2))){
            $country = 'NL';
        }

        if(preg_match("/32/",substr($initData['phone'],0,2))){
            $country = 'BE';
        }

        if(preg_match("/33/",substr($initData['phone'],0,2))){
            $country = 'FR';
        }

        if(preg_match("/34/",substr($initData['phone'],0,2))){
            $country = 'ES';
        }

        if(preg_match("/351/",substr($initData['phone'],0,3))){
            $country = 'PT';
        }

        if(preg_match("/352/",substr($initData['phone'],0,3))){
            $country = 'LU';
        }

        if(preg_match("/353/",substr($initData['phone'],0,3))){
            $country = 'IE';
        }

        if(preg_match("/354/",substr($initData['phone'],0,3))){
            $country = 'IS';
        }

        if(preg_match("/355/",substr($initData['phone'],0,3))){
            $country = 'AL';
        }

        if(preg_match("/356/",substr($initData['phone'],0,3))){
            $country = 'MT';
        }

        if(preg_match("/357/",substr($initData['phone'],0,3))){
            $country = 'CY';
        }

        if(preg_match("/358/",substr($initData['phone'],0,3))){
            $country = 'FI';
        }

        if(preg_match("/359/",substr($initData['phone'],0,3))){
            $country = 'BG';
        }

        if(preg_match("/36/",substr($initData['phone'],0,2))){
            $country = 'HU';
        }

        if(preg_match("/370/",substr($initData['phone'],0,3))){
            $country = 'LT';
        }

        if(preg_match("/371/",substr($initData['phone'],0,3))){
            $country = 'LV';
        }

        if(preg_match("/372/",substr($initData['phone'],0,3))){
            $country = 'EE';
        }

        if(preg_match("/373/",substr($initData['phone'],0,3))){
            $country = 'MD';
        }

        if(preg_match("/374/",substr($initData['phone'],0,3))){
            $country = 'AM';
        }

        if(preg_match("/375/",substr($initData['phone'],0,3))){
            $country = 'BY';
        }

        if(preg_match("/376/",substr($initData['phone'],0,3))){
            $country = 'AD';
        }

        if(preg_match("/377/",substr($initData['phone'],0,3))){
            $country = 'MC';
        }

        if(preg_match("/378/",substr($initData['phone'],0,3))){
            $country = 'SM';
        }

        if(preg_match("/379/",substr($initData['phone'],0,3))){
            $country = 'VA';
        }

        if(preg_match("/380/",substr($initData['phone'],0,3))){
            $country = 'UA';
        }

        if(preg_match("/381/",substr($initData['phone'],0,3))){
            $country = 'RS';
        }

        if(preg_match("/382/",substr($initData['phone'],0,3))){
            $country = 'ME';
        }

        if(preg_match("/385/",substr($initData['phone'],0,3))){
            $country = 'HR';
        }

        if(preg_match("/386/",substr($initData['phone'],0,3))){
            $country = 'SI';
        }

        if(preg_match("/387/",substr($initData['phone'],0,3))){
            $country = 'BA';
        }

        if(preg_match("/389/",substr($initData['phone'],0,3))){
            $country = 'MK';
        }

        if(preg_match("/39/",substr($initData['phone'],0,2))){
            $country = 'IT';
        }

        if(preg_match("/40/",substr($initData['phone'],0,2))){
            $country = 'RO';
        }

        if(preg_match("/41/",substr($initData['phone'],0,2))){
            $country = 'CH';
        }

        if(preg_match("/420/",substr($initData['phone'],0,3))){
            $country = 'CZ';
        }

        if(preg_match("/421/",substr($initData['phone'],0,3))){
            $country = 'SK';
        }

        if(preg_match("/423/",substr($initData['phone'],0,3))){
            $country = 'LI';
        }

        if(preg_match("/43/",substr($initData['phone'],0,2))){
            $country = 'AT';
        }

        if(preg_match("/44/",substr($initData['phone'],0,2))){
            $country = 'GB';
        }

        if(preg_match("/45/",substr($initData['phone'],0,2))){
            $country = 'DK';
        }

        if(preg_match("/46/",substr($initData['phone'],0,2))){
            $country = 'SE';
        }

        if(preg_match("/47/",substr($initData['phone'],0,2))){
            $country = 'NO';
        }

        if(preg_match("/48/",substr($initData['phone'],0,2))){
            $country = 'PL';
        }

        if(preg_match("/49/",substr($initData['phone'],0,2))){
            $country = 'DE';
        }

        if(preg_match("/51/",substr($initData['phone'],0,2))){
            $country = 'PE';
        }

        if(preg_match("/52/",substr($initData['phone'],0,2))){
            $country = 'MX';
        }

        if(preg_match("/53/",substr($initData['phone'],0,2))){
            $country = 'CU';
        }

        if(preg_match("/54/",substr($initData['phone'],0,2))){
            $country = 'AR';
        }

        if(preg_match("/55/",substr($initData['phone'],0,2))){
            $country = 'BR';
        }

        if(preg_match("/56/",substr($initData['phone'],0,2))){
            $country = 'CL';
        }

        if(preg_match("/57/",substr($initData['phone'],0,2))){
            $country = 'CO';
        }

        if(preg_match("/58/",substr($initData['phone'],0,2))){
            $country = 'VE';
        }

        if(preg_match("/84/",substr($initData['phone'],0,2))){
            $country = 'VN';
        }

        if(preg_match("/90/",substr($initData['phone'],0,2))){
            $country = 'TR';
        }

        if(preg_match("/972/",substr($initData['phone'],0,3))){
            $country = 'IL';
        }

        if(preg_match("/992/",substr($initData['phone'],0,3))){
            $country = 'TJ';
        }

        if(preg_match("/993/",substr($initData['phone'],0,3))){
            $country = 'TM';
        }

        if(preg_match("/994/",substr($initData['phone'],0,3))){
            $country = 'AZ';
        }

        if(preg_match("/995/",substr($initData['phone'],0,3))){
            $country = 'GE';
        }

        if(preg_match("/996/",substr($initData['phone'],0,3))){
            $country = 'KG';
        }

        if(preg_match("/998/",substr($initData['phone'],0,3))){
            $country = 'UZ';
        }

        if(!$country){
            $rContext->setFinished();
//            $rContext->setError('Эта страна пока не поддерживается');
            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        $swapData['session'] = $this->getSessionData();
        if(!$swapData['session']) {
            if ($swapData['iteration']>=30) {
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
            } else {
                $rContext->setSwapData($swapData);
                $rContext->setSleep(3);
            }
            return false;
        }
        $rContext->setSwapData($swapData);

        $phone = $initData['phone'];
        $params = array(
            'code' => $country,
            'mobile' => '+'.$phone,
            'captchacode' => $swapData['session']->code,
            'submit' => '',
        );
        $url = 'https://www.emobiletracker.com/trace-process.php';

        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
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
        $error = ($swapData['iteration']>3) ? curl_error($rContext->getCurlHandler()) : '';
        if (strpos($error,'timed out') || strpos($error,'connection')) {
            $error = false;
//                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 10 minute),sessionstatusid=6 WHERE id=" . $swapData['session']->id);
        }
        if(!$error) {
            $content = curl_multi_getcontent($rContext->getCurlHandler());
            file_put_contents('./logs/emt/emt_'.time().'.html',$content);

            if (preg_match("/<strong>   Name:([^<]+)</",$content,$matches)) {
                $resultData = new ResultDataList();
                if (($matches[1]!='No matches found') && ($matches[1]!='Not Found') && ($matches[1]!='t')) {
                    if (strpos($matches[1],'Ð')!==false) $matches[1]=iconv('utf-8','iso-8859-1',$matches[1]);
                    $data['name'] = new ResultDataField('string','Name',$matches[1],'Имя','Имя');
                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
                return true;
            } elseif (preg_match("/<div class='alert alert-dismissable alert-danger'>(.*?)<\/div>/",$content,$matches)) {
                if (strpos($matches[1],"so soon")) {
                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='waiting' WHERE id=".$swapData['session']->id);
                } else {
                    $error = trim(strip_tags($matches[1]));
                    $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
                }
            } elseif (preg_match("/<span class='label label-warning'>[^<]+<\/span>([^<]+)</",$content,$matches)) {
                if (strpos($matches[1],"captcha code")) {
                    $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=4,statuscode='invalidcaptcha' WHERE statuscode='used' AND id=".$swapData['session']->id);
                } else {
                    $error = trim($matches[1]);
                    $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
                }
            } else {
                file_put_contents('./logs/emt/emt_err_'.time().'.html',$content);
                if (!$content)
                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 hour),sessionstatusid=6,statuscode='empty' WHERE id=".$swapData['session']->id);
            }
        }

        if ($error || $swapData['iteration']>30) {
            $rContext->setFinished();
            $rContext->setError($error?$error:'Превышено количество попыток получения ответа');
        }

        $rContext->setSleep(1);
        return false;
    }
}

?>
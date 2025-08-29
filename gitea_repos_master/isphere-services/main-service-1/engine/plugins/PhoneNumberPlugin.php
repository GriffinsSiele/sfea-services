<?php

class PhoneNumberPlugin implements PluginInterface
{
    public function getName()
    {
        return 'PhoneNumber';
    }

    public function getTitle()
    {
        return 'Поиск телефона в phonenumber.to';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=26 ORDER BY lasttime limit 1");

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

                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1 WHERE id=".$sessionData->id);
//                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3 WHERE used=1 AND id=".$sessionData->id);
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
        if (strlen($initData['phone'])==10)
            $initData['phone']='7'.$initData['phone'];
        if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
            $initData['phone']='7'.substr($initData['phone'],1);
/*
        if(substr($initData['phone'],0,1)!='7'){
            $rContext->setFinished();
            $rContext->setError('Поиск производится только по российским телефонам');
            return false;
        }
*/
        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $swapData['session'] = $this->getSessionData();
        if(!$swapData['session']) {
            if (isset($swapData['iteration']) && ($swapData['iteration']>=10)) {
                $rContext->setFinished();
                $rContext->setError('Сервис не отвечает');
            } else {
                (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
                $rContext->setSwapData($swapData);
                $rContext->setSleep(1);
            }
            return false;
        }
        $rContext->setSwapData($swapData);

        $url = 'https://phonenumber.to/phone/'.$initData['phone'];
//        $url = 'https://phonenumber.to/search?text='.$initData['phone'];
        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt($ch, CURLOPT_REFERER, 'https://phonenumber.to/search');
        curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
        if ($swapData['session']->proxy) {
            curl_setopt($ch,CURLOPT_PROXY,$swapData['session']->proxy);
            if ($swapData['session']->proxy_auth) {
                curl_setopt($ch,CURLOPT_PROXYUSERPWD,$swapData['session']->proxy_auth); 
                curl_setopt($ch,CURLOPT_PROXYAUTH,CURLAUTH_ANY); 
            }
        }
/*
        $proxy = '54.245.73.4:5236';
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
*/
        $rContext->setCurlHandler($ch);
        return true;
    }

    public function computeRequest(&$rContext)
    {
        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
        $rContext->setSwapData($swapData);

        $curlError = curl_error($rContext->getCurlHandler());
        if($curlError && $swapData['iteration']>3)
        {
            $rContext->setFinished();
            $rContext->setError($curlError==''?'Превышено количество попыток получения ответа':$curlError);
            return false;
        }

        $content = curl_multi_getcontent($rContext->getCurlHandler());

        if($content){
//            file_put_contents('./logs/phonenumber/phonenumber_'.time().'.html',$content);

            if(preg_match("/<div class=\"search_results\">/", $content) || preg_match("/<div id=\"main_content\">/", $content)){
                if(preg_match("/itemprop=\"name\">([^<]+)/", $content, $matches)){
                    $data['name'] = new ResultDataField('string','Name',trim(html_entity_decode(strip_tags($matches[1]))),'Имя','Имя');
                }
                if(preg_match("/<p id=\"address\" [^>]+>([^<]+)/", $content, $matches)){
                    $data['address'] = new ResultDataField('string','Address',trim(html_entity_decode(strip_tags($matches[1]))),'Адрес','Адрес');
                }
                if(preg_match("/itemprop=\"url\"><a href=\"([^\"]+)/", $content, $matches)){
                    $data['url'] = new ResultDataField('url:recursive','URL',trim($matches[1]),'Сайт','Сайт');
                }
                if(preg_match("/itemprop=\"email\">([^<]+)/", $content, $matches)){
                    $data['email'] = new ResultDataField('email','Email',trim($matches[1]),'E-mail','E-mail');
                }
                if(preg_match("/prewrap\">([^<]+)<\/p>/", $content, $matches)){
                    $data['description'] = new ResultDataField('string','Description',trim(html_entity_decode(strip_tags($matches[1]))),'Описание','Описание');
                }
                $resultData = new ResultDataList();
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            } elseif(preg_match("/<title>404/", $content, $matches)){
                $resultData = new ResultDataList();
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            } elseif(preg_match("/<title>500/", $content, $matches)){
                if($swapData['iteration']>3) {
                  $rContext->setFinished();
                  $rContext->setError('Сервис недоступен');
                }
            } else {
                file_put_contents('./logs/phonenumber/phonenumber_err_'.time().'.html',$content);
                $rContext->setFinished();
                $rContext->setError('Некорректный ответ сервиса');
                return false;
            }
        }
    }
}

?>
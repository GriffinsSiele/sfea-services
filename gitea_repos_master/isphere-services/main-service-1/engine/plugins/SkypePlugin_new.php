<?php

class SkypePlugin implements PluginInterface
{
    public function __construct()
    {
    }

    public function getName()
    {
        return 'Skype';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Поиск в Skype',
            'skype_phone' => 'Skype - поиск по номеру телефона',
            'skype_email' => 'Skype - поиск по email',
            'skype' => 'Skype - профиль пользователя',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Поиск в Skype';
    }

    public function getSessionData()
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

        $mysqli->query("UPDATE isphere.session s SET request_id=$reqId WHERE sessionstatusid=2 AND sourceid=16 AND lasttime<DATE_SUB(now(), INTERVAL 1 SECOND) ORDER BY lasttime limit 1");
        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sourceid=16 AND request_id=$reqId ORDER BY lasttime limit 1");

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

                $mysqli->query("UPDATE isphere.session SET lasttime=now(),statuscode='used',used=ifnull(used,0)+1 WHERE id=".$sessionData->id);

                if (!$row->proxyid) {
                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE enabled=1 AND status=1 AND proxygroup=1 AND id NOT IN (SELECT proxyid FROM session WHERE sourceid=16 AND proxyid IS NOT NULL) ORDER BY lasttime limit 1");
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

        if(!isset($initData['nick']) && !isset($initData['phone']) && !isset($initData['email']) && !isset($initData['text'])) {
            $rContext->setFinished();
            $rContext->setError('Указаны не все обязательные параметры (skype, телефон или email)');

            return false;
        }
/*
        if (isset($initData['nick'])) {
            $swapData['login'] = $initData['nick'];
        }
*/
        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $swapData['session'] = $this->getSessionData();
        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        $rContext->setSwapData($swapData);

        if(!$swapData['session'])
        {
            if ($swapData['iteration']>20) {
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
            }
            $rContext->setSleep(1);
            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

/*
        if (isset($initData['nick'])) {
            $resultData = new ResultDataList();
            $data['avatar'] = new ResultDataField('image','Avatar','https://api.skype.com/users/'.$initData['nick'].'/profile/avatar','Аватар','Аватар');
            $resultData->addResult($data);
            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return false;
        }
*/
        if (isset($initData['phone'])) {
//            if (strlen($initData['phone'])==10)
//                $initData['phone']='7'.$initData['phone'];
//            if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
//                $initData['phone']='7'.substr($initData['phone'],1);

            $swapData['phone'] = $initData['phone'];
            $rContext->setSwapData($swapData);
        }

        $params = false;
        if (!isset($initData['nick'])) {
//            $url = 'https://login.skype.com/login/suggestions?username='.urlencode(isset($initData['phone']) ? $initData['phone'] : (isset($initData['email']) ? $initData['email'] : $initData['nick']));
//            $url = "https://skypegraph.skype.com/search/v1.1/namesearch/swx/?requestid=skype.com-1.117.21-7396b2a1-3253-4076-cde4-7cdb30c03737&searchstring=".urlencode(isset($initData['nick']) ? $initData['nick'] : (isset($initData['phone']) ? $initData['phone'] : (isset($initData['email']) ? $initData['email'] : $initData['text'])));
//            $url = "https://skypegraph.skype.com/v2.0/search?requestId=Query1&locale=ru-RU&sessionId=44cb1694-e918-4abc-8348-20664e57d800&searchstring=".urlencode(isset($initData['nick']) ? $initData['nick'] : (isset($initData['phone']) ? $initData['phone'] : (isset($initData['email']) ? $initData['email'] : $initData['text'])));
            $url = "https://skypegraph.skype.com/v2.0/search?requestId=Query1&locale=ru-RU&sessionId=&searchstring=".urlencode(isset($initData['nick']) ? $initData['nick'] : (isset($initData['phone']) ? $initData['phone'] : (isset($initData['email']) ? $initData['email'] : $initData['text'])));

//            $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 10 minute),sessionstatusid=6 WHERE id=".$swapData['session']->id);
            curl_setopt($ch,CURLOPT_TIMEOUT,5);
        } elseif (!isset($swapData['photo'])) {
            $url = 'https://api.skype.com/users/batch/profiles';
            $params = '{"usernames":["'.$initData['nick'].'"]}';
//            $url = 'https://people.skype.com/v2/profiles';
//            $params = '{"mris":["8:'.$initData['nick'].'"],"locale":"ru-RU"}';
            curl_setopt($ch,CURLOPT_TIMEOUT,5);
        } else {
            $url = $swapData['photo'];
        }
        curl_setopt($ch, CURLOPT_URL, $url);
//        echo $url."\n";
        curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
        if ($params) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        if (!isset($swapData['photo'])) {
            $header[] = 'Content-Type: application/json; ver=1.0';

//                $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';
/*
                $headers[] = 'Accept-Language: ru-RU,ru;q=0.9';
                $headers[] = 'Cache-Control: max-age=0';
                $headers[] = 'Connection: keep-alive';

                $headers[] = 'sec-ch-ua: " Not A;Brand";v="99", "Chromium";v="96", "Google Chrome";v="96"';
                $headers[] = 'sec-ch-ua-mobile: ?0';
                $headers[] = 'sec-ch-ua-platform: "Windows"';
                $headers[] = 'Sec-Fetch-Dest: document';
                $headers[] = 'Sec-Fetch-Mode: navigate';
                $headers[] = 'Sec-Fetch-Site: same-origin';
                $headers[] = 'Sec-Fetch-User: ?1';
                $headers[] = 'Upgrade-Insecure-Requests: 1';
*/

            $header[] = 'Accept: application/json';
            $header[] = 'Origin: https://web.skype.com';
            $header[] = 'Referer: https://web.skype.com/';
            $header[] = 'X-ECS-ETag: "ihK4VD1OFR0qyFIYgCb5Dzx1oyC39Y+EKrJ4ccHVGE4="';
            $header[] = 'X-Skype-Client: 1418/8.96.0.212';
            $header[] = 'X-SkypeGraphServiceSettings: {"experiment":"MinimumFriendsForAnnotationsEnabled","geoProximity":"disabled","minimumFriendsForAnnotationsEnabled":"true","minimumFriendsForAnnotations":2,"demotionScoreEnabled":"true"}';
            $header[] = 'X-Skypetoken: '.$swapData['session']->token;
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
//            curl_setopt($ch, CURLOPT_HEADER, true);
//            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
//            var_dump($header);
//            echo "\n";
        }
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.45 Safari/537.36");
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

//        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
//        $rContext->setSwapData($swapData);

        $error = false;
        $content = $full_content = curl_multi_getcontent($rContext->getCurlHandler());
/*
        $start = strpos($full_content,'{');
        $content = trim(substr($full_content,$start,strlen($full_content)-$start+1));
*/
        $res = json_decode($content, true);

        if (!$content) {
            $error = false; //curl_error($rContext->getCurlHandler());
            $code = curl_getinfo($rContext->getCurlHandler(),CURLINFO_HTTP_CODE);
            file_put_contents('./logs/skype/skype_empty_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$full_content."\n".$error."\n".$code);
            if ($swapData['iteration']<=5) $error=false;
            if ($code>=400 || strpos($error,'timed out') || strpos($error,'connection')) {
                $error = false;
            }
            $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='empty',proxyid=NULL WHERE id=" . $swapData['session']->id);
        } elseif (!isset($initData['nick'])) {
            file_put_contents('./logs/skype/skypesearch'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$full_content);
            $res = json_decode($content, true);               
            if($res && isset($res['results'])){
                $resultData = new ResultDataList();
                foreach($res['results'] as $result) {
                    $data = array();
                    if(isset($result['nodeProfileData'])) { 
                        $p = $result['nodeProfileData'];
                        if (in_array($p['contactType'],array('Skype','Skype4Consumer'))) {
                            $data['login'] = new ResultDataField(in_array($p['contactType'],array('Skype','Skype4Consumer'))?'skype':'string','Login',$p['skypeId'],'Логин','Логин');
                            $data['type'] = new ResultDataField('string','Type',$p['contactType'],'Тип','Тип учетной записи');
                        }
/*
                        if(isset($p['name'])){
                            $data['name'] = new ResultDataField('string','Name',$p['name'],'Имя','Имя');
                        }
                        if(isset($p['avatarUrl'])){
                            $swapData['avatar'] = $p['avatarUrl'];
                            $data['avatar'] = new ResultDataField('image','Avatar',$p['avatarUrl'],'Аватар','Аватар');
                        }
*/
                    }

                    if (sizeof($data)) $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                $mysqli->query("UPDATE isphere.session SET statuscode='success',success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->id);
                return true;
            } else {
                $error = 'Ошибка при отправке запроса';
                file_put_contents('./logs/skype/skypesearch_err_' . time() . '.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$full_content);
            }
        } elseif (!isset($swapData['photo'])) {
            file_put_contents('./logs/skype/skypeprofile'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$full_content);
            $res = json_decode($content, true);               
            if($res && isset($res[0])){
                $data = array();
                if(isset($res[0]['status']['text'])){
                    if (strpos($res[0]['status']['text'],'invalid username')!==false || strpos($res[0]['status']['text'],'not found')!==false || strpos($res[0]['status']['text'],'required parameters')!==false) {
                        $resultData = new ResultDataList();
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
                        $mysqli->query("UPDATE isphere.session SET statuscode='success',success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->id);
                        return true;
                    } else {
                        $error = $res[0]['status']['text'];
                        file_put_contents('./logs/skype/skypeprofile_err_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$full_content);
                    }
                } elseif(isset($res[0]) && isset($res[0]['username'])){
                    $user = $res[0];
                    $data['login'] = new ResultDataField('string','Login',$user['username'],'Логин','Логин');
                    if (isset($user['displayname']) && $user['displayname'])
                        $data['name'] = new ResultDataField('string','Name',iconv('windows-1251','utf-8',iconv('utf-8','windows-1251//IGNORE',$user['displayname'])),'Имя','Имя');
                    if (isset($user['birthday']) && $user['birthday'])
                        $data['birthday'] = new ResultDataField('string','Birthday',date("d.m.Y",strtotime($user['birthday'])),'Дата рождения','Дата рождения');
                    if (isset($user['city']) && $user['city'])
                        $data['city'] = new ResultDataField('string','City',$user['city'],'Город','Город');
                    if (isset($user['country']) && $user['country'])
                        $data['country'] = new ResultDataField('string','Country',$user['country'],'Страна','Страна');
                    if (isset($user['language']) && $user['language'])
                        $data['language'] = new ResultDataField('string','Language',$user['language'],'Язык','Язык');
                    if (isset($user['jobtitle']) && $user['jobtitle'])
                        $data['jobtitle'] = new ResultDataField('string','JobTitle',$user['jobtitle'],'Работа','Работа');
                    if (isset($user['phoneHome']) && $user['phoneHome'])
                        $data['phoneHome'] = new ResultDataField('phone','HomePhone',$user['phoneHome'],'Домашний телефон','Домашний телефон');
                    if (isset($user['phoneMobile']) && $user['phoneMobile'])
                        $data['phoneMobile'] = new ResultDataField('phone','MobilePhone',$user['phoneMobile'],'Мобильный телефон','Мобильный телефон');
                    if (isset($user['phoneOffice']) && $user['phoneOffice'])
                        $data['phoneOffice'] = new ResultDataField('phone','WorkPhone',$user['phoneOffice'],'Рабочий телефон','Рабочий телефон');
                    if (isset($user['about']) && $user['about'])
                        $data['about'] = new ResultDataField('string','About',iconv('windows-1251','utf-8',iconv('utf-8','windows-1251//IGNORE',$user['about'])),'Обо мне','Обо мне');
                    if (isset($user['homepage']) && $user['homepage'])
                        $data['homepage'] = new ResultDataField('url','HomePage',$user['homepage'],'Сайт','Сайт');
                    if (isset($user['avatarUrl']) && $user['avatarUrl']) {
                        $data['avatar'] = new ResultDataField('image','Avatar',$user['avatarUrl'].'?size=m','Аватар','Аватар');
//                        $swapData['data'] = $data;
//                        $swapData['photo'] = $user['avatarUrl'].'?size=m';
//                        $rContext->setSwapData($swapData);
//                    } else {
                    }
                        $resultData = new ResultDataList();
                        if (sizeof($data)) $resultData->addResult($data);
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
//                    }
                    $mysqli->query("UPDATE isphere.session SET statuscode='success',success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->id);
                    return true;
                } else {
                    $resultData = new ResultDataList();
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->query("UPDATE isphere.session SET statuscode='success',success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->id);
                    return true;
                }
            }elseif($res && isset($res['profiles'])){
                $data = array();
                if(isset($res['status']['text'])){
                    if (strpos($res['status']['text'],'invalid username')!==false || strpos($res['status']['text'],'not found')!==false || strpos($res['status']['text'],'required parameters')!==false) {
                        $resultData = new ResultDataList();
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
                        $mysqli->query("UPDATE isphere.session SET statuscode='success',success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->id);
                        return true;
                    } else {
                        $error = $res['status']['text'];
                        file_put_contents('./logs/skype/skypeprofile_err_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$full_content);
                    }
                } elseif(isset($res['profiles']['8:'.$initData['nick']]['profile'])){
                    $data['login'] = new ResultDataField('string','Login',$initData['nick'],'Логин','Логин');
                    $profile = $res['profiles']['8:'.$initData['nick']]['profile'];
                    if (isset($profile['displayName']) && $profile['displayName'])
                        $data['name'] = new ResultDataField('string','Name',iconv('windows-1251','utf-8',iconv('utf-8','windows-1251//IGNORE',$profile['displayName'])),'Имя','Имя');
                    if (isset($profile['birthday']) && $profile['birthday'])
                        $data['birthday'] = new ResultDataField('string','Birthday',date("d.m.Y",strtotime($profile['birthday'])),'Дата рождения','Дата рождения');
                    if (isset($profile['city']) && $profile['city'])
                        $data['city'] = new ResultDataField('string','City',$profile['city'],'Город','Город');
                    if (isset($profile['about']) && $profile['about'])
                        $data['about'] = new ResultDataField('string','About',iconv('windows-1251','utf-8',iconv('utf-8','windows-1251//IGNORE',$profile['about'])),'Обо мне','Обо мне');
                    if (isset($profile['homepage']) && $profile['homepage'])
                        $data['homepage'] = new ResultDataField('url','HomePage',$profile['homepage'],'Сайт','Сайт');
                    if (isset($profile['avatarUrl']) && $profile['avatarUrl']) {
                        $data['avatar'] = new ResultDataField('image','Avatar',$profile['avatarUrl'].'?size=m','Аватар','Аватар');
//                        $swapData['data'] = $data;
//                        $swapData['photo'] = $profile['avatarUrl'].'?size=m';
//                        $rContext->setSwapData($swapData);
//                    } else {
                    }
                        $resultData = new ResultDataList();
                        if (sizeof($data)) $resultData->addResult($data);
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
//                    }
                    $mysqli->query("UPDATE isphere.session SET statuscode='success',success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->id);
                    return true;
                } else {
                    $resultData = new ResultDataList();
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $mysqli->query("UPDATE isphere.session SET statuscode='success',success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->id);
                    return true;
                }
            } elseif ($res && isset($res['status']['text'])) {
                $error = $res['status']['text'];
                file_put_contents('./logs/skype/skypeprofile_err_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$full_content);
            } else {
                $error = "Некорректный ответ";
                file_put_contents('./logs/skype/skypeprofile_err_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$full_content);
            }
        } else {
            $data = $swapData['data'];
//            if (sizeof($content)!=1871 || !strpos($content,'7PWE')) {
            if (!strpos($content,'W5M0MpCehiHzreSzNTczkc9d')) {
                $name = 'logs/skype/'.strtr($initData['nick'],array(':'=>'_',','=>'_')).'.jpg';
                file_put_contents('./'.$name,$content);
                $data['avatar'] = new ResultDataField('image', 'Avatar', $serviceurl.$name, 'Аватар', 'Аватар');
            }
            $resultData = new ResultDataList();
            $resultData->addResult($data);
            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return true;
        }

        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>=3) {
            $error='Превышено количество попыток получения ответа';
        }
        if (preg_match("/Skypetoken has/",$error)) {
            $error = false;
            $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 6 hour),sessionstatusid=6,statuscode='revoked' WHERE id=" . $swapData['session']->id);
        }
        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();
            return false;
        }

        return true;
    }
}

?>
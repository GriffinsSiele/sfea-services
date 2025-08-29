<?php

class MailRuPlugin implements PluginInterface
{
    public function __construct()
    {
    }

    public function getName()
    {
        return 'MailRu';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Поиск в Mail.Ru',
            'mailru_person' => 'Mail.Ru - поиск профилей по имени и дате рождения',
            'mailru_email' => 'Mail.Ru - поиск по email',
            'mailru_url' => 'Mail.Ru - профиль пользователя',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Поиск в Mail.Ru';
    }

    public function getSessionData($sourceid = 49)
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=$sourceid ORDER BY lasttime limit 1");

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
//                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3 WHERE used>=10 AND id=".$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],7);

        if($checktype=='email' && !isset($initData['email'])) {
            $rContext->setFinished();
            $rContext->setError('Не указаны параметры для поиска (email)');

            return false;
        }

        if($checktype=='url' && !isset($initData['url'])) {
            $rContext->setFinished();
            $rContext->setError('Не указаны параметры для поиска (ссылка)');

            return false;
        }

        if($checktype=='person' && (!isset($initData['last_name']) || !isset($initData['first_name']))) {
            $rContext->setFinished();
            $rContext->setError('Не указаны параметры для поиска (фамилия+имя)');

            return false;
        }

        if($checktype=='text' && !isset($initData['text'])) {
            $rContext->setFinished();
            $rContext->setError('Не указаны параметры для поиска');

            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $swapData['session'] = $this->getSessionData($checktype=='email'?49:50);
//        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        $rContext->setSwapData($swapData);

        if(!$swapData['session']) {
            if (isset($swapData['iteration']) && ($swapData['iteration']>=10)) {
                $rContext->setFinished();
                $rContext->setError('Нет актуальных сессий');
            } else {
                (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
                $rContext->setSwapData($swapData);
                $rContext->setSleep(3);
            }
            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();
 
        $params = false;
        $header = array();
        
        if ($checktype=='email') {
            if (!isset($swapData['restore'])) {
                $url = 'https://filin.mail.ru/pic?width=max&height=max&email='.$initData['email'];
            } else {
                $host = 'https://account.mail.ru';
                $url = $host.'/api/v1/user/'.($swapData['restore']=='support'?'access/support':'password/restore');
                $params = array(
                    'email' => $initData['email'],
                    'htmlencoded' => 'false',
                );
                $header = array(
                    'Content-Type: application/x-www-form-urlencoded',
                    'X-Requested-With: XMLHttpRequest',
                    'Origin: '.$host,
                    'Referer: '.$host.'/recovery',
                );
            }
        } elseif ($checktype=='url') {
                $url = $initData['url'];
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        if ($params) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
//        curl_setopt($ch, CURLOPT_HEADER, true);
//        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
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
        global $mysqli,$serviceurl;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],7);

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;

        $error = false;
        $content = curl_multi_getcontent($rContext->getCurlHandler());

        if (!$content) {
            $error = ($swapData['iteration']>5) && false; //curl_error($rContext->getCurlHandler());
        } elseif ($checktype=='email' && !isset($swapData['restore'])) {
            $last_url = curl_getinfo($rContext->getCurlHandler(), CURLINFO_EFFECTIVE_URL);
            $data = array();
            if (!strpos($last_url,'/letters/default/') && substr($content,1,3)!=='PNG') {
                $name = 'logs/mailru/'.strtr($initData['email'],array('@'=>'_at_')).'.jpg';
                file_put_contents('./'.$name,$content);
                $data['avatar'] = new ResultDataField('image', 'Avatar', $serviceurl.$name, 'Аватар', 'Аватар');
            }
            $domains = array('mail.ru','inbox.ru','list.ru','bk.ru');
            $parts = explode('@',$initData['email']);
            if (sizeof($parts)==2 && in_array($parts[1],$domains)) {
                $data['link'] = new ResultDataField('url:recursive', 'Link', 'https://my.mail.ru/'.strtr($parts[1],array('.ru'=>'')).'/'.$parts[0].'/','Ссылка','Ссылка на страницу в соцсети');
                $swapData['data'] = $data;
                $swapData['restore'] = 'restore';
            } else {
                $resultData = new ResultDataList();
                if (sizeof($data))
                    $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            }
        } elseif ($checktype=='email') {
            file_put_contents('./logs/mailru/'.$swapData['restore'].'_'.time().'.txt',$content);
            $data = $swapData['data'];
            $res = json_decode($content, true);               
            if($res && isset($res['body'])){
                if (isset($res['body']['phones'])) {
                    foreach($res['body']['phones'] as $i => $phone) {
                        $phone = strtr($phone,array(' '=>'','('=>'',')'=>'','-'=>'','X'=>'*'));
                        $data['phone'.($i?$i+1:'')] = new ResultDataField('string','Phone',$phone,'Телефон','Телефон');
                    }
                }
                if (isset($res['body']['emails'])) {
                    foreach($res['body']['emails'] as $i => $email) {
                        $data['email'.($i?$i+1:'')] = new ResultDataField('string','Email',$email,'E-mail','E-mail');
                    }
                }
                if (isset($res['body']['id'])) {
//                    $data['id'] = new ResultDataField('string','ID',$res['body']['id'],'ID пользователя','ID пользователя');
                }
                if (isset($res['body']['email']['error']) && $res['body']['email']['error']=='not_exists') {
                    unset($data['link']);
                }
                if (isset($res['body']['push']) && $res['body']['push']) {
                    $data['mobile'] = new ResultDataField('string','Mobile','Да','Мобильное приложение','Мобильное приложение');
                }
                if (isset($res['body']['email']['error']) && $res['body']['email']['error']=='not_available_for_mrim') {
                    $data['locked'] = new ResultDataField('string','Locked','Да','Заблокирован','Заблокирован');
                    $swapData['data'] = $data;
                    $swapData['restore'] = 'support';
                } else {
                    $resultData = new ResultDataList();
                    if (sizeof($data))
                        $resultData->addResult($data);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                }
            } else {
                file_put_contents('./logs/mailru/'.$swapData['restore'].'_err_'.time().'.txt',$content);
                $error = "Некорректный ответ";
            }
        }
        $rContext->setSwapData($swapData);

        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>10) {
            $error='Превышено количество попыток получения ответа';
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
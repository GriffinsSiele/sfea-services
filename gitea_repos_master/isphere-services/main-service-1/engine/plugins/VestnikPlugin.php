<?php

class VestnikPlugin implements PluginInterface
{
    public function getName()
    {
        return 'Vestnik';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Поиск сведений в вестнике государственной регистрации',
            'vestnik_org' => 'Поиск сообщений организации в вестнике государственной регистрации',
            'vestnik_fns' => 'Поиск решений ФНС о предстоящем исключении недействующей организации из ЕГРЮЛ',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Поиск публикаций в вестнике государственной регистрации';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT server FROM proxy WHERE id=s.proxyid) server,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=45 ORDER BY lasttime limit 1");
//        $result = $mysqli->query("SELECT id proxyid, server, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE status=1 ORDER BY lasttime limit 1");

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
                $sessionData->ip = $row->server;
                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;

                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1 WHERE id=".$sessionData->id);

//                $mysqli->query("UPDATE isphere.proxy SET lasttime=now() WHERE id=".$row->proxyid);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],8);

        if(!isset($initData['inn']) && !isset($initData['ogrn']))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (ИНН или ОГРН)');

            return false;
        }

        if (!isset($swapData['session'])) {
//            unset($swapData['captcha_id'.$swapData['num']]);
//            unset($swapData['captcha_token'.$swapData['num']]);
            $swapData['session'] = $this->getSessionData();
            $rContext->setSwapData($swapData);
            if(!$swapData['session']) {
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
//                $rContext->setSleep(3);
                return false;
            }
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();
/*
        if (!isset($swapData['captcha_token']) && !isset($swapData['captcha_id'])) {
            $token = neuro_token('vestnik-gosreg.ru');
            if (strlen($token)>30) {
                $swapData['captcha_token'] = $token;
            }
//            echo "Neuro token $token\n";
        }
*/
        $rContext->setSwapData($swapData);

        if ($checktype=='org') {
            $page = 'https://www.vestnik-gosreg.ru/search/';

            if (!isset($swapData['captcha_token'])) {
                $apikey = 'd167c71a9278312f184f17caa4e71050';
                $googlekey = '6LdNnxETAAAAAJB5cQTkfy7_5pvOeW32YhtZKqLh';
                if (!isset($swapData['captcha_id'])) {
                    $url = "http://rucaptcha.com/in.php?key=$apikey&method=userrecaptcha&googlekey=$googlekey&pageurl=$page";
                } else {
                    $url = "http://rucaptcha.com/res.php?key=$apikey&action=get&id=".$swapData['captcha_id'];
                }
            } else {
                $url = $page;
                $a = ip2long($swapData['session']->ip?$swapData['session']->ip:'78.140.221.69'); $b = 8369; $c = 4104; $d = 4786;
                $validate = round(abs((pow($a*$b/$c/$d+$d,3)/($b*$c))-$d)*100)/100;
                $post = array(
                    'query' => isset($initData['inn'])?$initData['inn']:$initData['ogrn'],
                    'validate' => $validate,
                    'page' => 1,
                    'key' => '6wg19hdz',
                    'g-recaptcha-response' => $swapData['captcha_token'],
                );
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
                curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
                if ($swapData['session']->proxy) {
                    curl_setopt($ch,CURLOPT_PROXY,$swapData['session']->proxy);
                    if ($swapData['session']->proxy_auth) {
                        curl_setopt($ch,CURLOPT_PROXYUSERPWD,$swapData['session']->proxy_auth); 
                        curl_setopt($ch,CURLOPT_PROXYAUTH,CURLAUTH_ANY); 
                    }
                }
            }
        } else {
            $rContext->setFinished();
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],8);

        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        $curlError = false; //curl_error($rContext->getCurlHandler());

        if($curlError && $swapData['iteration']>10)
        {
            $rContext->setFinished();
            $rContext->setError($curlError==''?'Превышено количество попыток получения ответа':$curlError);

            return false;
        }
        $rContext->setSwapData($swapData);

        $content = curl_multi_getcontent($rContext->getCurlHandler());

        if (!isset($swapData['captcha_token'])) {
            if (!isset($swapData['captcha_id'])) {
                if (strpos($content,'OK|')!==false){
                    $swapData['captcha_id'] = substr($content,3);
                    $rContext->setSleep(3);
                } else {
                    $rContext->setFinished();
                    $rContext->setError('Ошибка получения капчи');
                    file_put_contents('./logs/vestnik/'.$initData['checktype'].'_captcha_err_'.time().'.txt',/*curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".*/$content);
                }
            } else {
                if ($content=='CAPCHA_NOT_READY') {
                    $rContext->setSleep(3);
                } elseif (strpos($content,'OK|')!==false) {
                    $swapData['captcha_token'] = substr($content,3);
                } else {
                    $rContext->setFinished();
                    $rContext->setError('Ошибка распознавания капчи');
                }
            }
            $swapData['iteration']--;
            $rContext->setSwapData($swapData);
            return true;
        } else {
//            file_put_contents('./logs/vestnik/vestnik_org_'.time().'_'.$swapData['iteration'].'.html',/*curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".*/$content);
            $content = iconv('windows-1251','utf-8',$content);
            $resultData = new ResultDataList();
            if (preg_match("/<div class=\"search-result-entries\">/",$content)) {
                $records = explode('<div class="item bnc-item">', $content);
                array_shift($records);
                foreach($records as $i => $record) {
                    $data = array();
                    if (preg_match("/№([\d\(\)]+)\s/",$record,$matches)) {
                       $data['issue_number'] = new ResultDataField('string','issue_number', trim(html_entity_decode($matches[1])), 'Номер выпуска', 'Номер выпуска');
                    }
                    if (preg_match("/часть ([\d\(\)]+)\s/",$record,$matches)) {
                       $data['issue_part'] = new ResultDataField('string','issue_part', trim(html_entity_decode($matches[1])), 'Часть выпуска', 'Часть выпуска');
                    }
                    if (preg_match("/от ([\d\.]{10})/",$record,$matches)) {
                       $data['issue_date'] = new ResultDataField('string','issue_date', trim(html_entity_decode($matches[1])), 'Дата', 'Дата выпуска');
                    }
                    if (preg_match("/\/ ([\d]+)</",$record,$matches)) {
                       $data['message_number'] = new ResultDataField('string','message_number', trim(html_entity_decode($matches[1])), 'Номер сообщения', 'Номер сообщения');
                    }
                    if (preg_match("/ИНН: (\d+)/",$record,$matches)) {
                        $data['inn'] = new ResultDataField('string','inn', trim(html_entity_decode($matches[1])), 'ИНН', 'ИНН');
                    }
                    if (preg_match("/ОГРН: (\d+)/",$record,$matches)) {
                        $data['ogrn'] = new ResultDataField('string','ogrn', trim(html_entity_decode($matches[1])), 'ОГРН', 'ОГРН');
                    }
                    if (preg_match("/<b>([^<]+)<\/b>/",$record,$matches)) {
                        $data['subject'] = new ResultDataField('string','subject', trim(html_entity_decode($matches[1])), 'Тема', 'Тема сообщения');
                    }
                    if (preg_match("/<div class=\"body-massege-content\">(.*?)<br>/sim",$record,$matches)) {
                        $data['message'] = new ResultDataField('string','message', trim(html_entity_decode(strip_tags($matches[1]))), 'Сообщение', 'Сообщение');
                    }
                    $data['Type'] = new ResultDataField('string','Type', 'message', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            } elseif ($swapData['iteration']>=3) {
                $rContext->setFinished();
                $rContext->setError("Некорректный ответ сервиса");
                file_put_contents('./logs/vestnik/vestnik_org_err_'.time().'.html',/*curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".*/$content);
            }
            return false;
        }
    }
}

?>
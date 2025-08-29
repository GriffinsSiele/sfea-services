<?php
class GetContactPlugin implements PluginInterface
{
    private $googlekey = '6LdEhwAVAAAAAEtNI-1XIe1NSekwDwBwJNW9i9_J';
    private $captcha_service = array(
        array('host' => 'api.capmonster.cloud', 'key' => 'afb26cbb248d650ea8b8d88822984242'),
        array('host' => 'rucaptcha.com', 'key' => 'd167c71a9278312f184f17caa4e71050'),
        array('host' => 'api.anti-captcha.com', 'key' => '63def0a149a147e0d13e409cc8318fc3'),
    );
    private $minscore = 0.7;
    private $captcha_threads = 1;

    public function getName()
    {
        return 'GetContact';
    }

    public function getTitle()
    {
        return 'Поиск в GetContact';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=42 AND unix_timestamp(now())-unix_timestamp(lasttime)>60 ORDER BY lasttime limit 1");

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

                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1,statuscode='used',captcha='' WHERE id=".$sessionData->id);
//                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3 WHERE used=1 AND id=".$sessionData->id);

                if (!$row->proxyid) {
                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE status=1 ORDER BY lasttime limit 1");
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
            $rContext->setError('Эта страна пока не поддерживается');
            return false;
        }
/*
        $rContext->setFinished();
        $rContext->setError('Сервис временно недоступен');
        return false;
*/
        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        if (!isset($swapData['num'])) {
            $swapData['num']=1;
            $rContext->setSwapData($swapData);
        }

        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
        if (!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData();
            unset($swapData['captcha_action']);
            unset($swapData['captcha_token']);
            if(!$swapData['session']) {
                if (isset($swapData['iteration']) && ($swapData['iteration']>=20)) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                } else {
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);
                }
                return false;
            }
        }
        if (!isset($swapData['captcha_token']) && $swapData['session']->code) {
//            $swapData['captcha_token'] = $swapData['session']->code;
        }
        $rContext->setSwapData($swapData);

        $host = 'https://web.getcontact.com';
        $page = $host.'/search';

        if (!isset($swapData['tags']) && isset($swapData['captcha_action']) && !isset($swapData['captcha_token'])) {
            if (!isset($swapData['captcha_id'.$swapData['num']])) {
                $swapData['captcha_service'.$swapData['num']] = intval(($swapData['iteration']-1)/10)%sizeof($this->captcha_service);
//                echo $swapData['iteration'].": New captcha from ".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']."\n";
                $rContext->setSwapData($swapData);
                if ($this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']=='rucaptcha.com') {
                    $params = array(
                        'key' => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'],
                        'method' => 'userrecaptcha',
                        'googlekey' => $this->googlekey,
                        'pageurl' => $page,
                        'version' => 'v3',
                        'action' => $swapData['captcha_action'], //'searchNumber',
                        'min_score' => $this->minscore,
                    );      
/*
                    if ($swapData['session']->proxy) {
                        $params['proxytype'] = 'http';
                        $params['proxy'] = ($swapData['session']->proxy_auth ? $swapData['session']->proxy_auth.'@' : '').$swapData['session']->proxy;
                    }
*/
                    $url = "https://".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']."/in.php?".http_build_query($params);
                } else {
                    $params = array(
                        "clientKey" => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'],
                        "task" => array(
                            "type" => "RecaptchaV3TaskProxyless",
//                            "type" => "NoCaptchaTask",
                            "websiteURL" => $page,
                            "websiteKey" => $this->googlekey,
                            "minScore" => $this->minscore,
                            "pageAction" => $swapData['captcha_action'], //'searchNumber',
/*
                            "proxyType" => "http",
                            "proxyAddress" => "8.8.8.8",
                            "proxyPort" => 8080,
                            "proxyLogin" => "proxyLoginHere",
                            "proxyPassword" => "proxyPasswordHere",
                            "userAgent" => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36",
*/
                        ),
                    );
                    $url = "https://".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']."/createTask";
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params,JSON_UNESCAPED_UNICODE));
                }
            } else {
                if ($this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']=='rucaptcha.com') {
                    $params = array(
                        'key' => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'],
                        'action' => 'get',
                        'id' => $swapData['captcha_id'.$swapData['num']],
                    );      
                    $url = "https://".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']."/res.php?".http_build_query($params);
                } else {
                    $params = array(
                        "clientKey" => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'],
                        "taskId" => $swapData['captcha_id'.$swapData['num']],
                    );
                    $url = "https://".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']."/getTaskResult";
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params,JSON_UNESCAPED_UNICODE));
                }
            }
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch,CURLOPT_TIMEOUT, 2);
//            echo $swapData['iteration'].": $url\n";
//            var_dump($params);
//            echo "\n";
        } else {
            $header = array(
                'Origin: '.$host,
                'Referer: '.$host.'/search',
            );
            if (isset($swapData['tags'])) {
                $url = $host.'/list-tag';
                $header[] = 'X-Requested-With: XMLHttpRequest';
                $params = array(
                    'hash' => $swapData['session']->token,
                    'phoneNumber' => $initData['phone'],
                    'countryCode' => $country,
                );
            } elseif (isset($swapData['captcha_token'])) {
                $url = $host.'/search';
                $params = array(
                    'countryCode' => $country,
                    'phoneNumber' => $initData['phone'],
                    'hash' => $swapData['session']->token,
                    'g-recaptcha-response' => $swapData['captcha_token'],
                );
            } else {
                $params = false;
                $url = $host;
            }

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch,CURLOPT_TIMEOUT, 15);
            if (is_array($params)) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            }
            curl_setopt($ch, CURLOPT_COOKIE, 'lang=ru; '.$swapData['session']->cookies);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

            if ($swapData['session']->proxy) {
                curl_setopt($ch,CURLOPT_PROXY,$swapData['session']->proxy);
                if ($swapData['session']->proxy_auth) {
                    curl_setopt($ch,CURLOPT_PROXYUSERPWD,$swapData['session']->proxy_auth); 
                    curl_setopt($ch,CURLOPT_PROXYAUTH,CURLAUTH_ANY); 
                }
            }
//            echo $swapData['iteration'].": $url\n";
//            var_dump($params);
//            echo "\n";
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
        $error = ($swapData['iteration']>5) ? curl_error($rContext->getCurlHandler()) : '';
        if (strpos($error,'timed out') || strpos($error,'connection')) {
            $error = false;
//                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 10 minute),sessionstatusid=6 WHERE id=" . $swapData['session']->id);
        }
        $content = curl_multi_getcontent($rContext->getCurlHandler());

        if (!isset($swapData['tags']) && isset($swapData['captcha_action']) && !isset($swapData['captcha_token'])) {
//            echo "$content\n";
            $res = json_decode($content, true);
            if (!isset($swapData['captcha_id'.$swapData['num']])) {
//                echo "Thread ".$swapData['num']."  Getting new captcha\n";
                if ($this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']=='rucaptcha.com') {
                    if (strpos($content,'OK|')!==false){
                        $swapData['captcha_id'.$swapData['num']] = substr($content,3);
                        $swapData['captcha_time'.$swapData['num']] = time();
                    } elseif ($swapData['iteration']>5) {
                        $rContext->setFinished();
                        $rContext->setError('Ошибка получения капчи');
                        file_put_contents('./logs/getcontact/'.$initData['checktype'].'_captcha_err_'.$swapData['iteration'].'_'.time().'.txt',/*curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".*/$content."\r\n".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']);
                    }
                } else {
                    if (isset($res['taskId'])){
                        $swapData['captcha_id'.$swapData['num']] = $res['taskId'];
                        $swapData['captcha_time'.$swapData['num']] = time();
                    } elseif ($swapData['iteration']>5) {
                        $rContext->setFinished();
                        $rContext->setError('Ошибка получения капчи');
                        file_put_contents('./logs/getcontact/'.$initData['checktype'].'_captcha_err_'.$swapData['iteration'].'_'.time().'.txt',/*curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".*/$content."\r\n".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']);
                    }
                }
            } else {
                if ($this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']=='rucaptcha.com') {
                    if ($content=='CAPCHA_NOT_READY' && time()-$swapData['captcha_time'.$swapData['num']]<30) {
                    } else {
                        if (strpos($content,'OK|')!==false) {
                            $swapData['captcha_token'] = substr($content,3);
                            $swapData['captcha_id'] = $swapData['captcha_id'.$swapData['num']];
                            $swapData['captcha_service'] = $swapData['captcha_service'.$swapData['num']];
//                            echo "Thread ".$swapData['num']."  Received captcha ID ".$swapData['captcha_id'.$swapData['num']]."\n";
                        } elseif ($swapData['iteration']>5) {
//                            $rContext->setFinished();
//                            $rContext->setError('Ошибка распознавания капчи');
                        }
                        unset($swapData['captcha_id'.$swapData['num']]);
                    }
                } else {
                    if (isset($res['status']) && $res['status']!=='ready' && time()-$swapData['captcha_time'.$swapData['num']]<30) {
                    } else {
                        if (isset($res['solution']['gRecaptchaResponse'])) {
                            $swapData['captcha_token'] = $res['solution']['gRecaptchaResponse'];
                            $swapData['captcha_id'] = $swapData['captcha_id'.$swapData['num']];
                            $swapData['captcha_service'] = $swapData['captcha_service'.$swapData['num']];
//                            echo "Thread ".$swapData['num']."  Received captcha ID ".$swapData['captcha_id'.$swapData['num']]."\n";
                        } elseif ($swapData['iteration']>5) {
//                            $rContext->setFinished();
//                            $rContext->setError('Ошибка распознавания капчи');
                        }
                        unset($swapData['captcha_id'.$swapData['num']]);
                    }
                }
                $swapData['iteration']--;
            }
            if (++$swapData['num'] > $this->captcha_threads) {
                $swapData['num']=1;
            }
            $rContext->setSwapData($swapData);
            if (!isset($swapData['captcha_token']) && isset($swapData['captcha_id'.$swapData['num']])) $rContext->setSleep(5); else $rContext->setSleep(1);
            return true;
        }

        if(!$error && isset($swapData['tags'])) {
//            file_put_contents('./logs/getcontact/getcontact_tags_'.$swapData['iteration'].'_'.time().'.txt',$content);

            $res = json_decode($content, true);

            $mysqli->query("UPDATE isphere.session SET used_ext=ifnull(used_ext,0)+1 WHERE id=" . $swapData['session']->id);
            $resultData = new ResultDataList();
            $data = $swapData['data'];
            $resultData->addResult($data);
            if ($res && isset($res['status']) && $res['status']=='success') {
                if (isset($res['tags']) && is_array($res['tags'])) {
                    foreach ($res['tags'] as $tag) {
                        $data = array();
                        $data['name'] = new ResultDataField('string','Name',iconv('windows-1251','utf-8',iconv('utf-8','windows-1251//IGNORE',html_entity_decode($tag['tag']))),'Имя','Имя');
                        $data['count'] = new ResultDataField('string','Count',$tag['count'],'Количество упоминаний','Количество упоминаний');
                        $resultData->addResult($data);
                    }
                }
                $mysqli->query("UPDATE isphere.session SET success_ext=ifnull(success_ext,0)+1 WHERE id=" . $swapData['session']->id);
            } elseif ($res && isset($res['status']) && $res['status']=='maxQuery') {
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 12 hour),sessionstatusid=6,statuscode='tagslimitexceed' WHERE id=" . $swapData['session']->id);
                unset($swapData['session']);
                $rContext->setSwapData($swapData);
//                return false;
            } elseif ($res && isset($res['status']) && $res['status']=='error') {
//                $error = 'Ошибка при выполнении запроса';
                file_put_contents('./logs/getcontact/getcontact_tags_err_'.$swapData['iteration'].'_'.time().'.txt',$content);
            } elseif (strlen($content)) {
//                $error = 'Ошибка при выполнении запроса';
                file_put_contents('./logs/getcontact/getcontact_tags_err_'.$swapData['iteration'].'_'.time().'.txt',$content);
            }
            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return true;
        } elseif(!$error) {
//            file_put_contents('./logs/getcontact/getcontact_'.(isset($swapData['captcha_token'])?'search_':'').$swapData['iteration'].'_'.time().'.html',$content);

            if (preg_match("/ data-action=\"([^\"]+)/",$content,$matches)) {
                $swapData['captcha_action'] = $matches[1];
                unset($swapData['captcha_token']);
            }
            if (preg_match("/<p class=\"mb-0 alert-text\">([^<]+)<\/p>/ui",$content,$matches) && strlen(trim($matches[1]))) {
                if (strpos($matches[1],"Number is invalid")!==false || strpos($matches[1],"Недопустимый номер")!==false) {
                    $resultData = new ResultDataList();
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=" . $swapData['session']->id);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    return true;
                } elseif (strpos($matches[1],"limit") || strpos($matches[1],"предела использования")) {
                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 day),sessionstatusid=6,statuscode='limitexceed' WHERE id=" . $swapData['session']->id);
                    unset($swapData['session']);
                } elseif (strpos($matches[1],"confirm") || strpos($matches[1],"подтверждение")) {
                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(current_date(),interval 1 month),sessionstatusid=6,statuscode='needconfirmation' WHERE id=" . $swapData['session']->id);
                    unset($swapData['session']);
                } elseif (strpos($matches[1],"validation") || strpos($matches[1],"проверка")) {
//                    file_put_contents('./logs/getcontact/getcontact_alert_captcha_'.$swapData['iteration'].'_'.time().'.html',$content."\n\n".$this->captcha_service[$swapData['captcha_service']]['host']);
                    if (isset($swapData['captcha_id']) && isset($swapData['captcha_service']) && $this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com') {
                        $params = array(
                            'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                            'action' => 'reportbad',
                            'id' => $swapData['captcha_id'],
                        );      
                        $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/res.php?".http_build_query($params);
                        $res = file_get_contents($url);
//                        echo "Captcha ID ".$swapData['captcha_id']." reported as bad with result $res\n";
                    }
//                    echo "Bad captcha ID ".$swapData['captcha_id']." from ".$this->captcha_service[$swapData['captcha_service']]['host']."\n";
//                    $mysqli->query("UPDATE isphere.session SET sessionstatusid=7,statuscode='validation' WHERE id=" . $swapData['session']->id);
//                    unset($swapData['session']);
                } elseif (strpos($matches[1],"be visible") || strpos($matches[1],"быть невидимым")) {
                    $resultData = new ResultDataList();
//                    $data['invisible'] = new ResultDataField('string','Invisible','да','Невидимый','Невидимый');
//                    $resultData->addResult($data);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();

                    if (isset($swapData['captcha_id']) && isset($swapData['captcha_service']) && $this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com') {
                        $params = array(
                            'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                            'action' => 'reportgood',
                            'id' => $swapData['captcha_id'],
                        );      
                        $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/res.php?".http_build_query($params);
                        $res = file_get_contents($url);
//                        echo "Captcha ID ".$swapData['captcha_id']." reported as good with result $res\n";
                    }
//                    echo "Good captcha ID ".$swapData['captcha_id']." from ".$this->captcha_service[$swapData['captcha_service']]['host']."\n";

                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=" . $swapData['session']->id);
                    return true;
                } else {
                    $error = trim($matches[1]);
                    file_put_contents('./logs/getcontact/getcontact_alert_error_'.$swapData['iteration'].'_'.time().'.html',$content);
                }
//                file_put_contents('./logs/getcontact/getcontact_alert_'.$swapData['iteration'].'_'.time().'.html',$content);
            } elseif (preg_match("/Использовать Getcontact на компьютере/",$content,$matches)) {
//                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(current_date(),interval 1 month),sessionstatusid=6,statuscode='computer' WHERE id=" . $swapData['session']->id);
                $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,endtime=now(),statuscode='finished' WHERE id=" . $swapData['session']->id);
                unset($swapData['session']);
//                file_put_contents('./logs/getcontact/getcontact_finished_'.$swapData['iteration'].'_'.time().'.html',$content);
            } elseif (preg_match("/<h1>(.*?)<\/h1>/sim",$content,$matches)) {
                if (isset($swapData['captcha_id']) && isset($swapData['captcha_service']) && $this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com') {
                    $params = array(
                        'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                        'action' => 'reportgood',
                        'id' => $swapData['captcha_id'],
                    );      
                    $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/res.php?".http_build_query($params);
                    $res = file_get_contents($url);
//                    echo "Captcha ID ".$swapData['captcha_id']." reported as good with result $res\n";
                }
//                echo "Good captcha ID ".$swapData['captcha_id']." from ".$this->captcha_service[$swapData['captcha_service']]['host']."\n";

                $resultData = new ResultDataList();
                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=" . $swapData['session']->id);
                if (!strpos($matches[1],'еще не доступен.')) {
                    $data['name'] = new ResultDataField('string','Name',trim(html_entity_decode(strip_tags($matches[1]))),'Имя','Имя');
//                    $data['invisible'] = new ResultDataField('string','Invisible','нет','Невидимый','Невидимый');
                    if (preg_match("/<figure style=\"background-image\:url\(\'([^\']+)/",$content,$matches) && !strpos($matches[1],'/user-default')) {
                        $data['avatar'] = new ResultDataField('image','Avatar',$matches[1],'Аватар','Аватар');
                    }
                    if (preg_match("/badge:'([^']+)'/",$content,$matches)) {
                        if ($matches[1]=='spam')
                            $data['spam'] = new ResultDataField('string','Spam','Да','Спам','Спам');
                        if ($matches[1]=='gtc')
                            $data['user'] = new ResultDataField('string','IsUser','Да','Пользователь GetContact','Пользователь GetContact');
                    }
                    if (!isset($data['user']))
                        $data['user'] = new ResultDataField('string','IsUser','Нет','Пользователь GetContact','Пользователь GetContact');
                    if (preg_match("/tagged by ([\d]+)/",$content,$matches)) {
                        $data['tags'] = new ResultDataField('string','TagsCount',$matches[1],'Количество тегов','Количество тегов');
                    }
                    if (preg_match("/id=\"tagList\"/",$content)) {
                        $swapData['tags'] = true;
                        $swapData['data'] = $data;
                        $rContext->setSwapData($swapData);
//                        return true;
                    }
                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                return true;
            } elseif (strpos($content,"Search by number")) {
                if (preg_match("/<input type=\"hidden\" name=\"hash\" value=\"([^\"]+)\"/",$content,$matches) && ($swapData['session']->token!=$matches[1])) {
                    $swapData['session']->token = $matches[1];
                    $mysqli->query("UPDATE isphere.session SET token='".$matches[1]."' WHERE id=" . $swapData['session']->id);
                }
//                unset($swapData['session']);
            } elseif (strlen($content)) {
                $error = 'Ошибка при выполнении запроса';
                file_put_contents('./logs/getcontact/getcontact_err_'.$swapData['iteration'].'_'.time().'.html',$content);
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 hour),sessionstatusid=6,statuscode='error' WHERE id=" . $swapData['session']->id);
                unset($swapData['session']);
            } else {
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 10 minute),sessionstatusid=6,statuscode='empty',proxyid=null WHERE id=" . $swapData['session']->id);
                unset($swapData['session']);
           }
        }

        if ($error || $swapData['iteration']>30) {
            $rContext->setError($error?$error:'Превышено количество попыток получения ответа');
            $rContext->setFinished();
            return false;
        }

//        unset($swapData['session']);
        $rContext->setSwapData($swapData);
        $rContext->setSleep(1);
        return true;
    }
}

?>
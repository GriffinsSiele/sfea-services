<?php

class GIBDDPlugin implements PluginInterface
{
    private $googlekey = '6Lc66nwUAAAAANZvAnT-OK4f4D_xkdzw5MLtAYFL';
    private $captcha_service = array(
        array('host' => 'api.capmonster.cloud', 'key' => 'afb26cbb248d650ea8b8d88822984242'),
        array('host' => 'rucaptcha.com', 'key' => 'd167c71a9278312f184f17caa4e71050'),
        array('host' => 'api.anti-captcha.com', 'key' => '63def0a149a147e0d13e409cc8318fc3'),
    );
    private $captcha_threads = 1;
    private $minscore = array(
            'history' => 0.7,
            'register' => 0.7,
            'aiusdtp' => 0.7,
            'wanted' => 0.7,
            'restricted' => 0.7,
            'diagnostic' => 0.7,
            'fines' => 0.7,
            'driver' => 0.7,
        );
    private $timeout = array(
            'history' => 30,
            'register' => 30,
            'aiusdtp' => 30,
            'wanted' => 30,
            'restricted' => 30,
            'diagnostic' => 30,
            'fines' => 30,
            'driver' => 30,
            'captcha' => 3,
        );
    private $pages = array(
            'history' => 'auto',
            'register' => 'auto',
            'aiusdtp' => 'auto',
            'wanted' => 'auto',
            'restricted' => 'auto',
            'diagnostic' => 'auto',
            'fines' => 'fines',
            'driver' => 'driver',
        );
    private $actions = array(
            'history' => 'check_auto_history',
            'register' => 'check_auto_register',
            'aiusdtp' => 'check_auto_dtp',
            'wanted' => 'check_auto_wanted',
            'restricted' => 'check_auto_restricted',
            'diagnostic' => 'check_auto_diagnostic',
            'fines' => 'check_fines',
            'driver' => 'check_driver',
        );

    public function getName($checktype = '')
    {
        $name = array(
            '' => 'GIBDD',
            'gibdd_history' => 'GIBDD_history',
            'gibdd_register' => 'GIBDD_register',
            'gibdd_aiusdtp' => 'GIBDD_aiusdtp',
            'gibdd_wanted' => 'GIBDD_wanted',
            'gibdd_restricted' => 'GIBDD_restricted',
            'gibdd_diagnostic' => 'GIBDD_diagnostic',
            'gibdd_driver' => 'GIBDD_driver',
            'gibdd_fines' => 'GIBDD_fines',
        );
        return isset($name[$checktype])?$name[$checktype]:$name[''];
//        return 'GIBDD';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Проверка в ГИБДД',
            'gibdd_history' => 'ГИБДД - история автомобиля',
            'gibdd_register' => 'ГИБДД - регистрационные действия',
            'gibdd_aiusdtp' => 'ГИБДД - проверка на участие в дорожно-транспортных происшествиях',
            'gibdd_wanted' => 'ГИБДД - проверка на нахождение в розыске',
            'gibdd_restricted' => 'ГИБДД - проверка на ограничение регистрационных действий',
            'gibdd_diagnostic' => 'ГИБДД - проверка наличия диагностической карты технического осмотра',
            'gibdd_driver' => 'ГИБДД - проверка водительского удостоверения',
            'gibdd_fines' => 'ГИБДД - неоплаченные штрафы',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Проверка в ГИБДД';
    }

    public function getSessionData()
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

        $result = $mysqli->query("UPDATE isphere.session s SET request_id=".$reqId." WHERE sessionstatusid=2 AND sourceid=21 ORDER BY lasttime limit 1");
//        $result = $mysqli->query("UPDATE isphere.session s SET token='',captcha='',request_id=".$reqId." WHERE sessionstatusid=3 AND sourceid=21 ORDER BY lasttime limit 1");
        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sourceid=21 AND request_id=".$reqId." ORDER BY lasttime limit 1");
//        $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE proxygroup=1 AND status=1 ORDER BY lasttime limit 1");

        if($result)
        {
            $row = $result->fetch_object();

            if ($row)
            {
                $sessionData = new \StdClass;

                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;

                $sessionData->id = $row->id;
                $sessionData->code = $row->captcha;
                $sessionData->token = $row->token;
                $sessionData->starttime = $row->starttime;
                $sessionData->lasttime = $row->lasttime;
                $sessionData->cookies = $row->cookies;

//                $mysqli->query("UPDATE isphere.session SET sessionstatusid=3,endtime=now(),lasttime=now(),used=ifnull(used,0)+1,statuscode='used',request_id=NULL WHERE id=".$sessionData->id);
                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1,statuscode='used',request_id=NULL,captcha='',token='' WHERE id=".$sessionData->id);
//                $mysqli->query("UPDATE isphere.proxy SET lasttime=now() WHERE id=".$row->proxyid);

                if (!$row->proxyid) {
//                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM proxy WHERE enabled=1 AND status=1 AND country='ru' AND (rotation>0 OR (SELECT COUNT(*) FROM session WHERE proxyid=proxy.id AND sourceid=21 AND sessionstatusid IN (1,2,6,7))<1) ORDER BY lasttime limit 1");
                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM proxy WHERE enabled=1 AND status=1 AND country='ru' AND rotation>0 AND id<>(SELECT proxyid FROM session WHERE sourceid=21 AND proxyid>0 GROUP BY 1 ORDER BY COUNT(*) DESC LIMIT 1) ".(rand(0,3)?"AND id IN (SELECT DISTINCT proxyid FROM session WHERE sourceid=21 AND proxyid>0 AND statuscode='success' AND lasttime>DATE_SUB(now(), INTERVAL 60 SECOND)) ":"")."ORDER BY lasttime limit 1");
                    if ($result) {
                        $row = $result->fetch_object();
                        if ($row) {
                            $sessionData->proxyid = $row->proxyid;
                            $sessionData->proxy = $row->proxy;
                            $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;

                            $mysqli->query("UPDATE proxy SET lasttime=now() WHERE id=".$row->proxyid);
                            $mysqli->query("UPDATE session SET proxyid=".$row->proxyid." WHERE id=".$sessionData->id);
                        } else {
                            $sessionData = null;
/*
                            $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM proxy WHERE id=2 ORDER BY lasttime limit 1");
                            if ($result) {
                                $row = $result->fetch_object();
                                if ($row) {
                                    $sessionData->proxyid = $row->proxyid;
                                    $sessionData->proxy = $row->proxy;
                                    $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;
                                }
                            }
*/
                        }
                    }
                }
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        global $reqId;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],6);

        if (in_array($checktype,array('history','register','aiusdtp','wanted','restricted','diagnostic')) && !isset($initData['vin']) && !isset($initData['bodynum']) && !isset($initData['chassis'])) {
            $rContext->setFinished();
//            if ($checktype=='history')
//                $rContext->setError('Указаны не все обязательные параметры (VIN или номер кузова)');

            return false;
        }

        if(isset($initData['vin']) && !preg_match("/^[A-HJ-NPR-Z0-9]{17}$/i",$initData['vin']))
        {
            $rContext->setFinished();
            $rContext->setError('VIN должен состоять из 17 латинских букв или цифр кроме I,O,Q');

            return false;
        }

        if (($checktype=='fines') && (!isset($initData['regnum']) || !isset($initData['ctc']))) {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (госномер и св-во о регистрации ТС)');

            return false;
        }

        if (($checktype=='driver') && (!isset($initData['driver_number']) || !isset($initData['driver_date']))) {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (водительское удостоверение + дата выдачи)');

            return false;
        }

        if (isset($initData['driver_number']) && !preg_match("/[0-9]{2}[0-9А-Я]{2}[0-9]{6}/ui",$initData['driver_number'])) {
            $rContext->setFinished();
            $rContext->setError('Водительское удостоверение не соответствует формату');

            return false;
        }
/*
//        if ($checktype=='restricted' || $checktype=='wanted') {
            $rContext->setFinished();
            $rContext->setError('Сервис временно недоступен');
            return false;
//        }
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
//            unset($swapData['captcha_token']);
            if(!$swapData['session']) {
                if (isset($swapData['iteration']) && ($swapData['iteration']>=30)) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                } else {
//                    echo $swapData['iteration'].": No session available\n";
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);
                }
                return false;
            }
        }

//        $swapData['captcha_token'] = '';
        if (!isset($swapData['captcha_token']) && $swapData['session']->code) {
            $swapData['captcha_token'] = $swapData['session']->code;
        }

        $rContext->setSwapData($swapData);

        $site = 'https://xn--80aebkobnwfcnsfk1e0h.xn--p1ai';
        $page = $site.'/check/'.$this->pages[$checktype];
/*
        if (!isset($swapData['captcha_token'])) {
            if (!isset($swapData['captcha_id'.$swapData['num']])) {
                $swapData['captcha_service'.$swapData['num']] = intval(($swapData['iteration']-1)/4)%sizeof($this->captcha_service);
                $rContext->setSwapData($swapData);
                if ($this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']=='rucaptcha.com') {
                    $params = array(
                        'key' => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'],
                        'method' => 'userrecaptcha',
                        'googlekey' => $this->googlekey,
                        'pageurl' => $site.'/search',
                        'version' => 'v3',
                        'action' => $this->actions[$checktype],
                        'min_score' => $this->minscore[$checktype],
                    );      
                    $url = "https://".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']."/in.php?".http_build_query($params);
                } else {
                    $params = array(
                        "clientKey" => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'],
                        "task" => array(
                            "type" => "RecaptchaV3TaskProxyless",
//                            "type" => "NoCaptchaTask",
                            "websiteURL" => $site.'/search',
                            "websiteKey" => $this->googlekey,
                            "minScore" => $this->minscore[$checktype],
                            "pageAction" => $this->actions[$checktype],
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
            curl_setopt($ch, CURLOPT_TIMEOUT,2);
//            echo $swapData['iteration'].": $url\n";
//            var_dump($params);
//            echo "\n";
*/
        if (!$swapData['session']->code) {
            $url = 'https://check.gibdd.ru/captcha';

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
            curl_setopt($ch, CURLOPT_TIMEOUT, $swapData['iteration']>=10?30:$this->timeout['captcha']);

            if ($swapData['session']->proxy) {
                curl_setopt($ch,CURLOPT_PROXY,$swapData['session']->proxy);
//                echo "Proxy ".$swapData['session']->proxy."\n";
                if ($swapData['session']->proxy_auth) {
                    curl_setopt($ch,CURLOPT_PROXYUSERPWD,$swapData['session']->proxy_auth); 
                    curl_setopt($ch,CURLOPT_PROXYAUTH,CURLAUTH_ANY); 
                }
            }
        } else {
            $url = 'https://xn--b1afk4ade.xn--90adear.xn--p1ai/proxy/check/';
            $params = array();

            $header = array();
            $header[] = 'Accept: application/json, text/javascript, */*; q=0.01';
            $header[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
            $header[] = 'X-Requested-With: XMLHttpRequest';
            $header[] = 'Origin: '.$site;

            if ($checktype=='fines') {
                $url .= 'fines';
                if (isset($swapData['pic']) && sizeof($swapData['pics'])) {
                    $url .= '/pics';
                    $params['regnum'] = $initData['regnum'];
                    $params['post'] = $swapData['pics'][0]['num'];
                    $params['divid'] = $swapData['pics'][0]['div'];
                    if (isset($swapData['pics_token']))
                        $params['cafapPicsToken'] = $swapData['pics_token'];
                } else {
                    $params['regnum'] = mb_substr($initData['regnum'],0,6);
                    $params['regreg'] = mb_substr($initData['regnum'],6);
                    $params['stsnum'] = $initData['ctc'];
                }
                $header[] = 'Referer: '.$page;
            } elseif ($checktype=='driver') {
                $url .= 'driver';
                $params['num'] = $initData['driver_number'];
                $params['date'] = date("Y-m-d", strtotime($initData['driver_date']));
                $header[] = 'Referer: '.$page;
            } else {
                $part = array(
                    'history' => 'history',
                    'register' => 'register',
                    'aiusdtp' => 'dtp',
                    'wanted' => 'wanted',
                    'restricted' => 'restrict',
                    'diagnostic' => 'diagnostic',
                );
                $url .= 'auto/'.$part[$checktype];
                $params['vin'] = isset($initData['vin'])?$initData['vin']:(isset($initData['bodynum'])?$initData['bodynum']:$initData['chassis']);
                $header[] = 'Referer: '.$page;
            }
            $params['checkType'] = $checktype;
//            $params['reCaptchaToken'] = $swapData['captcha_token'];
            $params['captchaWord'] = $swapData['session']->code;
            $params['captchaToken'] = $swapData['session']->token;

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout[$checktype]);
//            echo "Using captcha token ".$swapData['captcha_token']."  Session ID ".$swapData['session']->id."\n";
//            echo $swapData['iteration'].": $url\n";
//            var_dump($params);
//            echo "\n";
            if ($swapData['session']->proxy) {
                curl_setopt($ch,CURLOPT_PROXY,$swapData['session']->proxy);
//                echo "Proxy ".$swapData['session']->proxy."\n";
                if ($swapData['session']->proxy_auth) {
                    curl_setopt($ch,CURLOPT_PROXYUSERPWD,$swapData['session']->proxy_auth); 
                    curl_setopt($ch,CURLOPT_PROXYAUTH,CURLAUTH_ANY); 
                }
            }
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_COOKIEFILE, '');
//            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
//            curl_setopt($ch, CURLOPT_HEADER, true);
//            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        }

        $rContext->setCurlHandler($ch);
        $rContext->setSwapData($swapData);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        $error = false;

        global $mysqli;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        $checktype = substr($initData['checktype'],6);
//        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        $rContext->setSwapData($swapData);

        $content = curl_multi_getcontent($rContext->getCurlHandler());
        if(!$content) {
            $error = /*($swapData['iteration']>10) && */false; //curl_error($rContext->getCurlHandler());
        } else {
//            $content = strtr($content,array(' '=''));
        }
/*
        if (!isset($swapData['captcha_token'])) {
//            echo "$content\n";
            $res = json_decode($content, true);
            if (!isset($swapData['captcha_id'.$swapData['num']])) {
//                echo "Thread ".$swapData['num']."  Getting new captcha\n";
                if ($this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']=='rucaptcha.com') {
                    if (strpos($content,'OK|')!==false){
                        $swapData['captcha_id'.$swapData['num']] = substr($content,3);
                        $swapData['captcha_time'.$swapData['num']] = time();
                    } elseif ($swapData['iteration']>10) {
                        $rContext->setFinished();
                        $rContext->setError('Ошибка получения капчи');
                        file_put_contents('./logs/gibdd/'.$initData['checktype'].'_captcha_err_'.$swapData['iteration'].'_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content."\r\n".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']);
                    } else {
                        file_put_contents('./logs/gibdd/'.$initData['checktype'].'_captcha_err_'.$swapData['iteration'].'_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content."\r\n".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']);
                    }
                } else {
                    if (isset($res['taskId'])){
                        $swapData['captcha_id'.$swapData['num']] = $res['taskId'];
                        $swapData['captcha_time'.$swapData['num']] = time();
                    } elseif ($swapData['iteration']>10) {
                        $rContext->setFinished();
                        $rContext->setError('Ошибка получения капчи');
                        file_put_contents('./logs/gibdd/'.$initData['checktype'].'_captcha_err_'.$swapData['iteration'].'_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content."\r\n".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']);
                    } else {
                        file_put_contents('./logs/gibdd/'.$initData['checktype'].'_captcha_err_'.$swapData['iteration'].'_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content."\r\n".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']);
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
                        } elseif ($swapData['iteration']>10) {
//                            $rContext->setFinished();
//                            $rContext->setError('Ошибка распознавания капчи');
                        }
                        unset($swapData['captcha_id'.$swapData['num']]);
                    }
                } else {
                    if (!$content) {
                    } elseif (isset($res['status']) && $res['status']!=='ready' && time()-$swapData['captcha_time'.$swapData['num']]<30) {
                    } else {
                        if (isset($res['solution']['gRecaptchaResponse'])) {
                            $swapData['captcha_token'] = $res['solution']['gRecaptchaResponse'];
                            $swapData['captcha_id'] = $swapData['captcha_id'.$swapData['num']];
                            $swapData['captcha_service'] = $swapData['captcha_service'.$swapData['num']];
//                            echo "Thread ".$swapData['num']."  Received captcha ID ".$swapData['captcha_id'.$swapData['num']]."\n";
                        } elseif ($swapData['iteration']>10) {
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
*/
        file_put_contents('./logs/gibdd/'.(!$swapData['session']->code?'captcha':$initData['checktype']).'_'.(isset($swapData['pic']) && sizeof($swapData['pics'])?'pic'.$swapData['pics'][0]['i'].'_':'').$swapData['iteration'].'_'.time().'.txt',/*curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".*/$content);

        $fullcontent = $content;
        $start = strpos($content,'{');
        $content = trim(substr($content,$start,strlen($content)-$start+1));
        $res = json_decode($content, true);

        if (!$swapData['session']->code) {
            if ($res && isset($res['token']) && isset($res['base64jpg'])) {
                    $captcha = base64_decode($res['base64jpg']);
//                    file_put_contents('./logs/gibdd/captcha_'.$swapData['iteration'].'_'.time().'.jpg', $captcha);
//                    if (rand(0,1)) {
//                    $value = neuro_post($captcha,'gibdddecode');
                    $value = nn_post($captcha,'gibdd');
//                    echo $value." ".$res['token']."\n";
                    if ($value && substr($value,0,5)<>'ERROR') {
                        $swapData['session']->code = strtr($value,array('-'=>'0'));
                        $swapData['session']->token = $res['token'];
                    }
//                    }
                    $rContext->setSleep(1);
            } elseif ($swapData['iteration']%5==0) {
                file_put_contents('./logs/gibdd/captcha_err_'.$swapData['iteration'].'_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='toomanyinvalid' WHERE used>=3 AND success IS NULL AND id=".$swapData['session']->id);
                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='toobad' WHERE used>=10 AND ifnull(success,0)<used/3 AND id=".$swapData['session']->id);
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='nocaptcha',proxyid=NULL WHERE sessionstatusid=2 AND id=".$swapData['session']->id);
                unset($swapData['session']);
                $rContext->setSwapData($swapData);
                if ($swapData['iteration']>40) {
                    $error = 'Ошибка получения капчи';
                } else {
//                    $rContext->setSleep(1);
//                    return false;
                }
            }
        } elseif (is_array($res) && array_key_exists('RequestResult',$res) && is_array($res['RequestResult'])) {
            $res = $res['RequestResult'];

            $resultData = new ResultDataList();

            if (isset($res['errorDescription']) && $res['errorDescription']) {
//                $error = trim($res['errorDescription']);
            }

            $data = array();
            if (array_key_exists('vehiclePassport',$res)) { 
                $rec = $res['vehiclePassport'];
                    if (array_key_exists('number',$rec))
                        $data['PTS'] = new ResultDataField('string','PTS', $rec['number'], 'Номер ПТС', 'Номер ПТС');
                    if (array_key_exists('issue',$rec))
                        $data['PTSIssue'] = new ResultDataField('string','PTSIssue', $rec['issue'], 'ПТС выдан', 'ПТС выдан');
            }
            if (array_key_exists('vehicle',$res)) { 
                $rec = $res['vehicle'];
                    $cartypes = array(
                        "01" => "Грузовые автомобили бортовые",
                        "02" => "Грузовые автомобили шасси",
                        "03" => "Грузовые автомобили фургоны",
                        "04" => "Грузовые автомобили тягачи седельные",
                        "05" => "Грузовые автомобили самосвалы",
                        "06" => "Грузовые автомобили рефрижераторы",
                        "07" => "Грузовые автомобили цистерны",
                        "08" => "Грузовые автомобили с гидроманипулятором",
                        "09" => "Грузовые автомобили прочие",
                        "21" => "Легковые автомобили универсал",
                        "22" => "Легковые автомобили комби (хэтчбек)",
                        "23" => "Легковые автомобили седан",
                        "24" => "Легковые автомобили лимузин",
                        "25" => "Легковые автомобили купе",
                        "26" => "Легковые автомобили кабриолет",
                        "27" => "Легковые автомобили фаэтон",
                        "28" => "Легковые автомобили пикап",
                        "29" => "Легковые автомобили прочие",
                        "41" => "Автобусы длиной не более 5 м",
                        "42" => "Автобусы длиной более 5 м, но не более 8 м",
                        "43" => "Автобусы длиной более 8 м, но не более 12 м",
                        "44" => "Автобусы сочлененные длиной более 12 м",
                        "49" => "Автобусы прочие",
                        "51" => "Специализированные автомобили автоцистерны",
                        "52" => "Специализированные автомобили санитарные",
                        "53" => "Специализированные автомобили автокраны",
                        "54" => "Специализированные автомобили заправщики",
                        "55" => "Специализированные автомобили мастерские",
                        "56" => "Специализированные автомобили автопогрузчики",
                        "57" => "Специализированные автомобили эвакуаторы",
                        "58" => "Специализированные пассажирские транспортные средства",
                        "59" => "Специализированные автомобили прочие",
                        "71" => "Мотоциклы",
                        "72" => "Мотороллеры и мотоколяски",
                        "73" => "Мотовелосипеды и мопеды",
                        "74" => "Мотонарты",
                        "80" => "Прицепы самосвалы",
                        "81" => "Прицепы к легковым автомобилям",
                        "82" => "Прицепы общего назначения к грузовым автомобилям",
                        "83" => "Прицепы цистерны",
                        "84" => "Прицепы тракторные",
                        "85" => "Прицепы вагоны-дома передвижные",
                        "86" => "Прицепы со специализированными кузовами",
                        "87" => "Прицепы трейлеры",
                        "88" => "Прицепы автобуса",
                        "89" => "Прицепы прочие",
                        "91" => "Полуприцепы с бортовой платформой",
                        "92" => "Полуприцепы самосвалы",
                        "93" => "Полуприцепы фургоны",
                        "95" => "Полуприцепы цистерны",
                        "99" => "Полуприцепы прочие",
                        "31" => "Трактора",
                        "32" => "Самоходные машины и механизмы",
                        "33" => "Трамваи",
                        "34" => "Троллейбусы",
                        "35" => "Велосипеды",
                        "36" => "Гужевой транспорт",
                        "38" => "Подвижной состав железных дорог",
                        "39" => "Иной"
                    );
                    if (array_key_exists('model',$rec))
                        $data['Model'] = new ResultDataField('string','Model', $rec['model'], 'Марка (модель)', 'Марка (модель)');
                    if (array_key_exists('year',$rec))
                        $data['Year'] = new ResultDataField('string','Year', $rec['year'], 'Год выпуска', 'Год выпуска');
                    if (array_key_exists('vin',$rec)) {
                        $rec['vin'] = strtr($rec['vin'],array(' '=>'','I'=>'1','O'=>'0','Q'=>'0','А'=>'A','В'=>'B','С'=>'C','Е'=>'E','Н'=>'H','К'=>'K','М'=>'M','О'=>'0','Р'=>'P','Т'=>'T','У'=>'Y','Х'=>'X','а'=>'a','с'=>'c','е'=>'e','к'=>'k','м'=>'m','о'=>'0','р'=>'p','т'=>'t','у'=>'y','х'=>'x'));
                        $data['VIN'] = new ResultDataField('string','VIN', $rec['vin'], 'VIN', 'VIN');
                    }
                    if (array_key_exists('chassisNumber',$rec)) {
                        $rec['chassisNumber'] = strtr($rec['chassisNumber'],array(' '=>'','А'=>'A','В'=>'B','С'=>'C','Е'=>'E','Н'=>'H','К'=>'K','М'=>'M','О'=>'0','Р'=>'P','Т'=>'T','У'=>'Y','Х'=>'X','а'=>'a','с'=>'c','е'=>'e','к'=>'k','м'=>'m','о'=>'0','р'=>'p','т'=>'t','у'=>'y','х'=>'x'));
                        $data['Chassis'] = new ResultDataField('string','Chassis', $rec['chassisNumber'], 'Шасси', 'Шасси');
                    }
                    if (array_key_exists('bodyNumber',$rec)) {
                        $rec['bodyNumber'] = strtr($rec['bodyNumber'],array(' '=>'','А'=>'A','В'=>'B','С'=>'C','Е'=>'E','Н'=>'H','К'=>'K','М'=>'M','О'=>'0','Р'=>'P','Т'=>'T','У'=>'Y','Х'=>'X','а'=>'a','с'=>'c','е'=>'e','к'=>'k','м'=>'m','о'=>'0','р'=>'p','т'=>'t','у'=>'y','х'=>'x'));
                        $data['Body'] = new ResultDataField('string','Body', $rec['bodyNumber'], 'Номер кузова', 'Номер кузова');
                    }
                    if (array_key_exists('color',$rec))
                        $data['Сolor'] = new ResultDataField('string','Color', $rec['color'], 'Цвет', 'Цвет');
                    if (array_key_exists('engineNumber',$rec))
                        $data['Engine'] = new ResultDataField('string','Engine', $rec['engineNumber'], 'Номер двигателя', 'Номер двигателя');
                    if (array_key_exists('engineVolume',$rec))
                        $data['EngineVolume'] = new ResultDataField('string','EngineVolume', $rec['engineVolume'], 'Объем двигателя', 'Объем двигателя');
                    if (array_key_exists('powerHp',$rec))
                        $data['Power'] = new ResultDataField('string','Power', $rec['powerHp'], 'Мощность двигателя, л.с.', 'Мощность двигателя, л.с.');
                    if (array_key_exists('powerKwt',$rec))
                        $data['PowerKWT'] = new ResultDataField('string','PowerKWT', $rec['powerKwt'], 'Мощность двигателя, кВт', 'Мощность двигателя, кВт');
                    if (array_key_exists('category',$rec))
                        $data['Category'] = new ResultDataField('string','Category', $rec['category'], 'Категория', 'Категория');
                    if (array_key_exists('type',$rec) && isset($cartypes[$rec['type']]))
                        $data['Type'] = new ResultDataField('string','Type', $cartypes[$rec['type']], 'Тип', 'Тип');
            }

            if (array_key_exists('ownershipPeriods',$res)) { 
                $ownerstext = '';
                $rec = $res['ownershipPeriods']['ownershipPeriod'];
                $data['Owners'] = new ResultDataField('string', 'Owners', sizeof($rec), 'Кол-во владельцев', 'Кол-во владельцев');
                foreach ($rec as $owner) {
                    $ownertypes = array(
                        "Natural" => "Физическое лицо",
                        "Legal" => "Юридическое лицо",
                    );
                    $operationtypes = array(
                        "00" => "",
                        "01" => "регистрация новых ТС, а также ввезенных в РФ бывших в эксплуатации ТС (в т.ч. временно на срок более 6 месяцев)",
                        "02" => "регистрация ранее зарегистрированных ТС",
                        "03" => "изменение владельца ТС (переход права собственности)",
                        "04" => "изменение данных владельца ТС",
                        "05" => "изменение данных ТС, в том числе изменение технических характеристик или типа ТС",
                        "06" => "выдача регистрационных знаков или документов взамен утраченных",
                        "07" => "прекращение регистрации ТС",
                        "08" => "снятие с учета в связи с убытием за пределы РФ",
                        "09" => "снятие с учета в связи с утилизацией",

                        "11" => "первичная регистрация ТС",
                        "12" => "регистрация ТС, снятых с учета",
                        "13" => "временная регистрация ТС (на срок проведения проверок, на срок временной прописки, регистрация испытательной техники)",
                        "14" => "временный учет (временная регистрация места пребывания ТС без выдачи документов)",
                        "15" => "регистрация ТС, ввезенных из-за пределов Российской Федерации",
                        "16" => "регистрация ТС, прибывших из других регионов Российской Федерации",
                        "17" => "регистрация ТС по новому месту жительства собственника, прибывшего из другого субъекта Российской Федерации",
                        "18" => "восстановление регистрации после аннулирования",
                        "19" => "выдача транзитных знаков в связи с убытием за пределы Российской Федерации",

                        "21" => "постановка на постоянный учет в связи со сверкой",
                        "22" => "постановка на оперативный учет",
                        "23" => "постановка на постоянный учет",
                        "24" => "постановка в розыск утраченной спецпродукции",
                        "25" => "постановка уничтоженной спецпродукции",
                        "26" => "учет изготовленной и отгруженной спецпродукции (по информации предприятий-изготовителей)",
                        "27" => "учет выданной и распределенной спецпродукции (по информации подразделений ГИБДД)",
                        "28" => "учет закрепленной спецпродукции",
                        "29" => "учет ПТС, выданных заводами-изготовителями",
                        "30" => "учет ПТС, выданных таможенными органами",
                        "31" => "резерв",
/*
                        "32" => "оформление первичного материала по административному правонарушению",
                        "33" => "учет лиц в розыске",
                        "34" => "учет утраченного оружия",
                        "35" => "первичная выдача после обучения",
                        "36" => "первичная выдача после самоподготовки",
                        "37" => "замена в связи с утратой",
                        "38" => "замена в связи с истечением срока действия",
                        "39" => "открытие новой категории",
                        "40" => "выдача международного водительского удостоверения",
*/
                        "41" => "замена государственного регистрационного знака",
                        "42" => "выдача дубликата регистрационного документа",
                        "43" => "выдача (замена) паспорта ТС",
                        "44" => "замена номерного агрегата, цвета, изменение конструкции ТС",
                        "45" => "изменение Ф.И.О. (наименования) владельца",
                        "46" => "изменение места жительства (юридического адреса) владельца в пределах территории обслуживания регистрационным пунктом",
                        "47" => "наличие запретов и ограничений",
                        "48" => "снятие запретов и ограничений",
                        "49" => "регистрация залога ТС",
                        "50" => "прекращение регистрации залога ТС",
                        "51" => "коррекция иных реквизитов ТС",
                        "52" => "выдача акта технического осмотра",
                        "53" => "проведение ГТО",
                        "54" => "постоянная регистрация ТС по окончанию временной",
                        "55" => "коррекция реквизитов по информации налоговых органов",
                        "56" => "коррекция реквизитов при проведении ГТО",
                        "61" => "снятие с учета в связи с изменением места регистрации",
                        "62" => "снятие с учета в связи с прекращением права собственности (отчуждение, конфискация ТС)",
                        "63" => "снятие с учета в связи с вывозом ТС за пределы Российской Федерации",
                        "64" => "снятие с учета в связи с окончанием срока временной регистрации",
                        "65" => "снятие с учета в связи с утилизацией",
                        "66" => "снятие с учета в связи с признанием регистрации недействительной",
                        "67" => "снятие с временного учета",
                        "68" => "снятие с учета в связи с кражей или угоном",
                        "69" => "постановка с одновременным снятием с учета",
                        "70" => "временное прекращение регистрации ТС",
                        "71" => "снятие с розыска в связи с обнаружением",
                        "72" => "снятие с розыска за давностью лет",
                        "73" => "снятие с розыска в связи с неподтверждением",
                        "74" => "снятие с оперативного учета в связи с переводом на постоянный учет",
                        "75" => "с ПУ в связи с обнаружением",
                        "76" => "с ПУ за давностью лет",
                        "77" => "чистка ФКУ \"ГИАЦ МВД России\"",
                        "78" => "наложенных ограничений",

                        "81" => "снятие спецпродукции с учета как утраченной, в связи с обнаружением",
                        "82" => "удаление ошибочно введенной записи",
                        "83" => "удаление в связи со сверкой",
                        "84" => "перевод в архив в связи с корректировкой",

                        "91" => "переход права собственности по наследству с заменой государственных регистрационных знаков",
                        "92" => "переход права собственности по наследству с сохранением государственных регистрационных знаков за новым собственником (наследником)",
                        "93" => "переход права собственности по сделкам, произведенным в любой форме (купля-продажа, дарение, др.) с заменой государственных регистрационных знаков",
                        "94" => "переход права собственности по сделкам, произведенным в любой форме (купля-продажа, дарение, др.) с сохранением государственных регистрационных",
                    );
//                    $ownerstext .= ($ownerstext?";\n":'').date('d.m.Y',strtotime($owner['from'])).'-'.(isset($owner['to'])?date('d.m.Y',strtotime($owner['to'])):'н/в').' '.$ownertypes[$owner['simplePersonType']].', '.$operationtypes[$owner['lastOperation']];
                    $ownerdata['from'] = new ResultDataField('string', 'StartDate', date('d.m.Y',strtotime($owner['from'])), 'Дата начала', 'Дата начала');
                    $ownerdata['to'] = new ResultDataField('string', 'EndDate', (isset($owner['to'])?date('d.m.Y',strtotime($owner['to'])):'н/в'), 'Дата окончания', 'Дата окончания');
                    if (isset($owner['simplePersonType']))
                        $ownerdata['ownertype'] = new ResultDataField('string', 'OwnerType', $ownertypes[$owner['simplePersonType']], 'Тип собственника', 'Тип собственника');
                    if (isset($owner['lastOperation']))
                        $ownerdata['lastoperation'] = new ResultDataField('string', 'LastOperation', isset($operationtypes[$owner['lastOperation']])?$operationtypes[$owner['lastOperation']]:'неизвестная операция '.$owner['lastOperation'], 'Последнее действие', 'Последнее действие');
                    $ownerdata['recordtype'] = new ResultDataField('string', 'RecordType', 'history', 'Тип записи', 'Тип записи');
                    $resultData->addResult($ownerdata);
                }
//                $data['OwnersText'] = new ResultDataField('text', 'OwnersText', $ownerstext, 'История владения', 'История владения');
            }

            if (array_key_exists('id',$res)) { 
                $rec = $res;
                    $data['id'] = new ResultDataField('string','ID', $rec['id'], 'Уникальный номер записи', 'Уникальный номер записи');
                    if (array_key_exists('reestr_status',$rec))
                        $data['Status'] = new ResultDataField('string','Status', $rec['reestr_status'], 'Статус записи', 'Статус записи');
                    if (array_key_exists('vehicle_brandmodel',$rec))
                        $data['Model'] = new ResultDataField('string','Model', $rec['vehicle_brandmodel'], 'Марка (модель)', 'Марка (модель)');
                    if (array_key_exists('vehicle_releaseyear',$rec))
                        $data['Year'] = new ResultDataField('string','Year', $rec['vehicle_releaseyear'], 'Год выпуска', 'Год выпуска');
                    if (array_key_exists('vehicle_vin',$rec) && $rec['vehicle_vin']) {
                        $rec['vehicle_vin'] = strtr($rec['vehicle_vin'],array(' '=>'','I'=>'1','O'=>'0','Q'=>'0','А'=>'A','В'=>'B','С'=>'C','Е'=>'E','Н'=>'H','К'=>'K','М'=>'M','О'=>'0','Р'=>'P','Т'=>'T','У'=>'Y','Х'=>'X','а'=>'a','с'=>'c','е'=>'e','к'=>'k','м'=>'m','о'=>'0','р'=>'p','т'=>'t','у'=>'y','х'=>'x'));
                        $data['VIN'] = new ResultDataField('string','VIN', $rec['vehicle_vin'], 'VIN', 'VIN');
                    }
                    if (array_key_exists('vehicle_chassisnumber',$rec) && $rec['vehicle_chassisnumber']) {
                        $rec['vehicle_chassisnumber'] = strtr($rec['vehicle_chassisnumber'],array(' '=>'','А'=>'A','В'=>'B','С'=>'C','Е'=>'E','Н'=>'H','К'=>'K','М'=>'M','О'=>'0','Р'=>'P','Т'=>'T','У'=>'Y','Х'=>'X','а'=>'a','с'=>'c','е'=>'e','к'=>'k','м'=>'m','о'=>'0','р'=>'p','т'=>'t','у'=>'y','х'=>'x'));
                        $data['Chassis'] = new ResultDataField('string','Chassis', $rec['vehicle_chassisnumber'], 'Шасси', 'Шасси');
                    }
                    if (array_key_exists('vehicle_body_number',$rec) && $rec['vehicle_body_number']) {
                        $rec['vehicle_body_number'] = strtr($rec['vehicle_body_number'],array(' '=>'','А'=>'A','В'=>'B','С'=>'C','Е'=>'E','Н'=>'H','К'=>'K','М'=>'M','О'=>'0','Р'=>'P','Т'=>'T','У'=>'Y','Х'=>'X','а'=>'a','с'=>'c','е'=>'e','к'=>'k','м'=>'m','о'=>'0','р'=>'p','т'=>'t','у'=>'y','х'=>'x'));
                        $data['Body'] = new ResultDataField('string','Body', $rec['vehicle_body_number'], 'Номер кузова', 'Номер кузова');
                    }
                    if (array_key_exists('vehicle_bodycolor',$rec))
                        $data['Сolor'] = new ResultDataField('string','Color', $rec['vehicle_bodycolor'], 'Цвет', 'Цвет');
                    if (array_key_exists('vehicle_enginenumber',$rec))
                        $data['Engine'] = new ResultDataField('string','Engine', $rec['vehicle_enginenumber'], 'Номер двигателя', 'Номер двигателя');
                    if (array_key_exists('vehicle_enclosedvolume',$rec))
                        $data['EngineVolume'] = new ResultDataField('string','EngineVolume', $rec['vehicle_enclosedvolume'], 'Объем двигателя', 'Объем двигателя');
                    if (array_key_exists('vehicle_enginepower',$rec))
                        $data['Power'] = new ResultDataField('string','Power', $rec['vehicle_enginepower'], 'Мощность двигателя, л.с.', 'Мощность двигателя, л.с.');
                    if (array_key_exists('vehicle_enginepowerkw',$rec))
                        $data['PowerKWT'] = new ResultDataField('string','PowerKWT', $rec['vehicle_enginepowerkw'], 'Мощность двигателя, кВт', 'Мощность двигателя, кВт');
                    if (array_key_exists('vehicle_eco_class',$rec))
                        $data['EcoClass'] = new ResultDataField('string','EcoClass', $rec['vehicle_eco_class'], 'Экологический класс', 'Экологический класс');
                    if (array_key_exists('vehicle_type_name',$rec))
                        $data['Type'] = new ResultDataField('string','Type', $rec['vehicle_type_name'], 'Тип', 'Тип');
            }

            if (array_key_exists('periods',$res)) { 
                $ownerstext = '';
                $rec = $res['periods'];
                $data['Owners'] = new ResultDataField('string', 'Owners', sizeof($rec), 'Кол-во владельцев', 'Кол-во владельцев');
                foreach ($rec as $owner) {
                    $ownerdata['from'] = new ResultDataField('string', 'StartDate', date('d.m.Y',strtotime($owner['startDate'])), 'Дата начала', 'Дата начала');
                    $ownerdata['to'] = new ResultDataField('string', 'EndDate', (isset($owner['endDate']) && $owner['endDate']?date('d.m.Y',strtotime($owner['endDate'])):'н/в'), 'Дата окончания', 'Дата окончания');
                    $ownerdata['ownertype'] = new ResultDataField('string', 'OwnerType', $owner['ownerType'], 'Тип собственника', 'Тип собственника');
                    $ownerdata['recordtype'] = new ResultDataField('string', 'RecordType', 'history', 'Тип записи', 'Тип записи');
                    $resultData->addResult($ownerdata);
                }
//                $data['OwnersText'] = new ResultDataField('text', 'OwnersText', $ownerstext, 'История владения', 'История владения');
            }

            if (sizeof($data)>0) {
                $data['recordtype'] = new ResultDataField('string', 'RecordType', 'vehicle', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);
            }

            $osn = array();

            if (array_key_exists('records',$res)) { 
                foreach($res['records'] as $rec) {
                    $divtypes = array("",
                        "Судебные органы",
                        "Судебный пристав",
                        "Таможенные органы",
                        "Органы социальной защиты",
                        "Нотариус",
                        "Органы внутренних дел или иные правоохранительные органы",
                        "Органы внутренних дел или иные правоохранительные органы (прочие)",
	            );
                    $restrs = array("",
                        "Запрет на регистрационные действия",
                        "Запрет на снятие с учета",
                        "Запрет на регистрационные действия и прохождение ГТО",
                        "Утилизация (для транспорта не старше 5 лет)",
                        "Аннулирование",
                    );

                    $data = array();
                    if (array_key_exists('ogrkod',$rec))
                        $data['Restriction'] = new ResultDataField('string','Restriction', $restrs[$rec['ogrkod']], 'Ограничение', 'Ограничение');
                    if (array_key_exists('dateogr',$rec))
                        $data['RestrictionDate'] = new ResultDataField('string','RestrictionDate', $rec['dateogr'], 'Дата наложения', 'Дата наложения ограничения');
                    if (array_key_exists('divtype',$rec))
                        $data['RestrictionDiv'] = new ResultDataField('string','RestrictionDiv', $divtypes[$rec['divtype']], 'Кем наложено', 'Кем наложено ограничение');
                    if (array_key_exists('regname',$rec))
                        $data['RestrictionRegion'] = new ResultDataField('string','RestrictionRegion', $rec['regname'], 'Регион', 'Регион наложения ограничения');
                    if (array_key_exists('osnOgr',$rec)) {
                        $data['RestrictionReason'] = new ResultDataField('string','RestrictionReason', $rec['osnOgr'], 'Основания', 'Основания');
                        if (preg_match("/([0-9]+[\-\/][0-9]+[\-\/][0-9]+)-ИП/", $rec['osnOgr'], $matches)){
                            $ip = strtr($matches[1],array('-'=>'/')).'-ИП';
                            $data['IPNumber'] = new ResultDataField('string','IPNumber', $ip, 'Номер ИП', 'Номер исполнительного производства');
                            $url = 'https://fssp.gov.ru/iss/ip?is%5Bvariant%5D=3&is%5Bip_number%5D='.urlencode($ip);
                            $data['IPSearch'] = new ResultDataField('url','IPSearch', $url, 'Поиск ИП', 'Поиск исполнительного производства');
                        }
                    }
                    if (array_key_exists('phone',$rec))
                        $data['Phone'] = new ResultDataField('string','Phone', $rec['phone'], 'Телефон', 'Телефон');
                    if (array_key_exists('tsmodel',$rec))
                        $data['Model'] = new ResultDataField('string','Model', $rec['tsmodel'], 'Марка (модель)', 'Марка (модель)');
                    if (array_key_exists('tsyear',$rec))
                        $data['Year'] = new ResultDataField('string','Year', $rec['tsyear'], 'Год выпуска', 'Год выпуска');
                    if (array_key_exists('tsVIN',$rec))
                        $data['VIN'] = new ResultDataField('string','VIN', $rec['tsVIN'], 'VIN', 'VIN');
                    if (array_key_exists('tsKuzov',$rec))
                        $data['Body'] = new ResultDataField('string','Body', $rec['tsKuzov'], 'Кузов', 'Кузов');
                    if (array_key_exists('regnum',$rec))
                        $data['RegNum'] = new ResultDataField('string','RegNum', $rec['regnum'], 'Госномер', 'Госномер');

                    if (array_key_exists('osnOgr',$rec)) {
                        if (in_array($rec['osnOgr'],$osn)) {
                            $data = array();
                        } else {
                            $osn[] = $rec['osnOgr'];
                        }
                    }

                    if (sizeof($data)) {
                        $data['recordtype'] = new ResultDataField('string', 'RecordType', 'restricted', 'Тип записи', 'Тип записи');
                        $resultData->addResult($data);
                    }

                    $data = array();
                    if (array_key_exists('w_data_pu',$rec))
                        $data['WantedPermDate'] = new ResultDataField('string','WantedPermDate', $rec['w_data_pu'], 'Дата постоянного учета', 'Дата постоянного учета');
                    if (array_key_exists('w_data_oper',$rec))
                        $data['WantedOperDate'] = new ResultDataField('string','WantedOperDate', $rec['w_data_oper'], 'Дата оперативного учета', 'Дата оперативного учета');
                    if (array_key_exists('w_reg_inic',$rec))
                        $data['WantedRegion'] = new ResultDataField('string','WantedRegion', $rec['w_reg_inic'], 'Регион инициатора розыска', 'Регион инициатора розыска');
                    if (array_key_exists('w_model',$rec))
                        $data['Model'] = new ResultDataField('string','Model', $rec['w_model'], 'Марка (модель)', 'Марка (модель)');
                    if (array_key_exists('w_god_vyp',$rec))
                        $data['Year'] = new ResultDataField('string','Year', $rec['w_god_vyp'], 'Год выпуска', 'Год выпуска');
                    if (array_key_exists('w_reg_zn',$rec))
                        $data['RegNum'] = new ResultDataField('string','RegNum', $rec['w_reg_zn'], 'Госномер', 'Госномер');
                    if (sizeof($data)) {
                        $data['recordtype'] = new ResultDataField('string', 'RecordType', 'wanted', 'Тип записи', 'Тип записи');
                        $resultData->addResult($data);
                    }
                }
            }

            if (array_key_exists('Accidents',$res) && is_array($res['Accidents'])) { 
                foreach($res['Accidents'] as $rec) {
                    $data = array();
                    if (array_key_exists('VehicleMark',$rec))
                        $data['Mark'] = new ResultDataField('string','Mark', $rec['VehicleMark'], 'Марка', 'Марка');
                    if (array_key_exists('VehicleModel',$rec))
                        $data['Model'] = new ResultDataField('string','Model', $rec['VehicleModel'], 'Модель', 'Модель');
                    if (array_key_exists('VehicleYear',$rec))
                        $data['Year'] = new ResultDataField('string','Year', $rec['VehicleYear'], 'Год выпуска', 'Год выпуска');
                    if (array_key_exists('OwnerOkopf',$rec))
                        $data['OwnerType'] = new ResultDataField('string','OwnerType', $rec['OwnerOkopf'], 'Тип собственника', 'Тип собственника');
                    if (array_key_exists('VehicleSort',$rec))
                        $data['VehicleNumber'] = new ResultDataField('string','VehicleNumber', $rec['VehicleSort'], 'Номер ТС в ДТП', 'Номер ТС в ДТП');
                    if (array_key_exists('VehicleAmount',$rec))
                        $data['VehicleAmount'] = new ResultDataField('string','VehicleAmount', $rec['VehicleAmount'], 'Количество ТС в ДТП', 'Количество ТС в ДТП');
                    if (array_key_exists('AccidentType',$rec))
                        $data['AccidentType'] = new ResultDataField('string','AccidentType', $rec['AccidentType'], 'Тип ДТП', 'Тип ДТП');
                    if (array_key_exists('AccidentDateTime',$rec))
                        $data['AccidentNumber'] = new ResultDataField('string','AccidentNumber', $rec['AccidentNumber'], 'Номер ДТП', 'Номер ДТП');
                    if (array_key_exists('AccidentNumber',$rec))
                        $data['AccidentDateTime'] = new ResultDataField('string','AccidentDateTime', $rec['AccidentDateTime'], 'Дата и время ДТП', 'Дата и время ДТП');
                    if (array_key_exists('AccidentType',$rec))
                        $data['AccidentType'] = new ResultDataField('string','AccidentType', $rec['AccidentType'], 'Тип ДТП', 'Тип ДТП');
                    if (array_key_exists('RegionName',$rec))
                        $data['AccidentRegion'] = new ResultDataField('string','AccidentRegion', $rec['RegionName'], 'Регион ДТП', 'Регион ДТП');
                    if (array_key_exists('AccidentPlace',$rec))
                        $data['AccidentPlace'] = new ResultDataField('string','AccidentPlace', $rec['AccidentPlace'], 'Место ДТП', 'Место ДТП');
                    if (array_key_exists('VehicleDamageState',$rec))
                        $data['AccidentDamage'] = new ResultDataField('string','AccidentDamage', $rec['VehicleDamageState'], 'Повреждения', 'Повреждения');
                    if (array_key_exists('DamagePoints',$rec) && is_array($rec['DamagePoints']) && sizeof($rec['DamagePoints'])) {
                        $pointname = array(
                            "01" => "передняя часть справа",
                            "02" => "правая часть спереди",
                            "03" => "правая часть сзади",
                            "04" => "задняя часть справа",
                            "05" => "задняя часть слева",
                            "06" => "левая часть сзади",
                            "07" => "левая часть спереди",
                            "08" => "передняя часть слева",
                            "09" => "крыша",
                            "10" => "днище",
                            "11" => "полная деформация кузова",
                            "12" => "смещение двигателя",
                            "13" => "смещение переднего моста",
                            "14" => "смещение заднего моста",
                            "15" => "возгорание",
                            "20" => "повреждение VIN",

                            "110" => "переднее правое крыло",
                            "111" => "передняя правая дверь/правое зеркало",
                            "112" => "задняя правая дверь",
                            "113" => "заднее правое крыло",
                            "114" => "задний бампер справа/правый фонарь",
                            "115" => "задний бампер слева/левый фонарь",
                            "116" => "заднее левое крыло",
                            "117" => "задняя левая дверь",
                            "118" => "передняя левая дверь/левое зеркало",
                            "119" => "переднее левое крыло",
                            "120" => "передний бампер слева/левая фара",
                            "121" => "передний бампер справа/правая фара",
                            "122" => "капот/лобовое стекло",
                            "123" => "крыша",
                            "124" => "багажник/заднее стекло",
                            "125" => "днище",

                            "130" => "передняя сторона справа",
                            "131" => "правая сторона спереди",
                            "132" => "правая сторона середина",
                            "133" => "правая сторона сзади",
                            "134" => "задняя сторона слева",
                            "135" => "задняя сторона справа",
                            "136" => "левая сторона сзади",
                            "137" => "левая сторона середина",
                            "138" => "левая сторона спереди",
                            "139" => "передняя сторона слева",
                            "140" => "крыша спереди",
                            "141" => "крыша середина",
                            "142" => "крыша сзади",
                            "143" => "днище",

                            "150" => "переднее колесо/руль/фара слева",
                            "151" => "сиденье/двигатель/бензобак слева",
                            "152" => "заднее колесо/глушитель/фонарь слева",
                            "153" => "заднее колесо/глушитель/фонарь справа",
                            "154" => "сиденье/двигатель/бензобак справа",
                            "155" => "переднее колесо/руль/фара справа",

                            "160" => "передняя сторона кабины справа",
                            "161" => "передняя сторона фургона справа ",
                            "162" => "правая сторона кабины",
                            "163" => "рама/низ справа",
                            "164" => "правый борт",
                            "165" => "правая сторона фургона",
                            "166" => "задняя сторона фургона справа",
                            "167" => "задний борт справа",
                            "168" => "рама/низ сзади справа",
                            "169" => "рама/низ сзади слева",
                            "170" => "задний борт слева",
                            "171" => "задняя сторона фургона слева",
                            "172" => "левая сторона фургона",
                            "173" => "левый борт",
                            "174" => "рама/низ слева",
                            "175" => "левая сторона кабины",
                            "176" => "передняя сторона фургона слева ",
                            "177" => "передняя сторона кабины слева",
                            "178" => "крыша кабины",
                            "179" => "крыша фургона",
                            "180" => "днище",

                            "190" => "передний правый угол",
                            "191" => "передний правый бок",
                            "192" => "задний правый бок",
                            "193" => "задний правый угол",
                            "194" => "задний левый угол",
                            "195" => "задний левый бок",
                            "196" => "передний левый бок",
                            "197" => "передний левый угол",
                            "198" => "крыша",
                            "199" => "днище",

                            "210" => "переднее правое крыло",
                            "211" => "передняя правая дверь/правое зеркало",
                            "212" => "задняя правая дверь",
                            "213" => "заднее правое крыло",
                            "214" => "задний бампер справа/правый фонарь",
                            "215" => "задний бампер слева/левый фонарь",
                            "216" => "заднее левое крыло",
                            "217" => "задняя левая дверь",
                            "218" => "передняя левая дверь/левое зеркало",
                            "219" => "переднее левое крыло",
                            "220" => "передний бампер слева/левая фара",
                            "221" => "передний бампер справа/правая фара",
                            "222" => "капот/лобовое стекло",
                            "223" => "крыша",
                            "224" => "багажник/заднее стекло",
                            "225" => "днище",

                            "230" => "передняя сторона справа",
                            "231" => "правая сторона спереди",
                            "232" => "правая сторона середина",
                            "233" => "правая сторона сзади",
                            "234" => "задняя сторона слева",
                            "235" => "задняя сторона справа",
                            "236" => "левая сторона сзади",
                            "237" => "левая сторона середина",
                            "238" => "левая сторона спереди",
                            "239" => "передняя сторона слева",
                            "240" => "крыша спереди",
                            "241" => "крыша середина",
                            "242" => "крыша сзади",
                            "243" => "днище",

                            "250" => "переднее колесо/руль/фара слева",
                            "251" => "сиденье/двигатель/бензобак слева",
                            "252" => "заднее колесо/глушитель/фонарь слева",
                            "253" => "заднее колесо/глушитель/фонарь справа",
                            "254" => "сиденье/двигатель/бензобак справа",
                            "255" => "переднее колесо/руль/фара справа",

                            "260" => "передняя сторона кабины справа",
                            "261" => "передняя сторона фургона справа ",
                            "262" => "правая сторона кабины",
                            "263" => "рама/низ справа",
                            "264" => "правый борт",
                            "265" => "правая сторона фургона",
                            "266" => "задняя сторона фургона справа",
                            "267" => "задний борт справа",
                            "268" => "рама/низ сзади справа",
                            "269" => "рама/низ сзади слева",
                            "270" => "задний борт слева",
                            "271" => "задняя сторона фургона слева",
                            "272" => "левая сторона фургона",
                            "273" => "левый борт",
                            "274" => "рама/низ слева",
                            "275" => "левая сторона кабины",
                            "276" => "передняя сторона фургона слева ",
                            "277" => "передняя сторона кабины слева",
                            "278" => "крыша кабины",
                            "279" => "крыша фургона",
                            "280" => "днище",

                            "290" => "передний правый угол",
                            "291" => "передний правый бок",
                            "292" => "задний правый бок",
                            "293" => "задний правый угол",
                            "294" => "задний левый угол",
                            "295" => "задний левый бок",
                            "296" => "передний левый бок",
                            "297" => "передний левый угол",
                            "298" => "крыша",
                            "299" => "днище",
                        );
                        $points = "";
//                        $imagepoints = "";
                        $parts = "";
                        foreach($rec['DamagePoints'] as $point) {
                            if (array_key_exists($point,$pointname))
                                $parts .= ($parts?",":"").$pointname[$point];
                            $points .= ($points?",":"").$point;
//                            if (strlen($point)<3) {
//                                $imagepoints .= $point;
//                            }
                        }
//                        if ($imagepoints)
//                            $data['AccidentDamageImage'] = new ResultDataField('image','AccidentDamageImage', "https://check.gibdd.ru/proxy/check/auto/dtp/damages.png?map=".$imagepoints, 'Места наибольших повреждений', 'Места наибольших повреждений');
                        if ($points)
                            $data['AccidentDamagePoints'] = new ResultDataField('string','AccidentDamagePoints', $points, 'Коды повреждений', 'Список кодов повреждений');
                        if ($parts)
                            $data['AccidentDamageParts'] = new ResultDataField('string','AccidentDamageParts', $parts, 'Повреждения', 'Список повреждений');
                    }
                    $data['recordtype'] = new ResultDataField('string', 'RecordType', 'accident', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                }
            }

            if (array_key_exists('diagnosticCards',$res) && is_array($res['diagnosticCards'])) { 
                foreach($res['diagnosticCards'] as $rec) {
                    $data = array();
                    if (array_key_exists('brand',$rec))
                        $data['Mark'] = new ResultDataField('string','Mark', $rec['brand'], 'Марка', 'Марка');
                    if (array_key_exists('model',$rec))
                        $data['Model'] = new ResultDataField('string','Model', $rec['model'], 'Модель', 'Модель');
                    if (array_key_exists('dcNumber',$rec))
                        $data['Number'] = new ResultDataField('string','Number', $rec['dcNumber'], 'Номер карты', 'Номер карты');
                    if (array_key_exists('dcDate',$rec))
                        $data['StartDate'] = new ResultDataField('string','StartDate', $rec['dcDate'], 'Дата диагностики', 'Дата диагностики');
                    if (array_key_exists('dcExpirationDate',$rec))
                        $data['EndDate'] = new ResultDataField('string','EndDate', $rec['dcExpirationDate'], 'Действителен до', 'Действителен до');
                    if (array_key_exists('pointAddress',$rec))
                        $data['Address'] = new ResultDataField('string','Address', $rec['pointAddress'], 'Адрес станции', 'Адрес станции');
                    if (array_key_exists('odometerValue',$rec))
                        $data['Mileage'] = new ResultDataField('string','Mileage', $rec['odometerValue'], 'Пробег', 'Пробег');
                    $data['recordtype'] = new ResultDataField('string', 'RecordType', 'diagnostic', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);

                    if (array_key_exists('previousDcs',$rec)) {
                        $data = array();
                        foreach(array_reverse($rec['previousDcs']) as $dc) {
                            if (array_key_exists('brand',$rec))
                                $data['Mark'] = new ResultDataField('string','Mark', $rec['brand'], 'Марка', 'Марка');
                            if (array_key_exists('model',$rec))
                               $data['Model'] = new ResultDataField('string','Model', $rec['model'], 'Модель', 'Модель');
                            if (array_key_exists('dcNumber',$dc))
                                $data['Number'] = new ResultDataField('string','Number', $dc['dcNumber'], 'Номер карты', 'Номер карты');
                            if (array_key_exists('dcDate',$dc))
                                $data['StartDate'] = new ResultDataField('string','StartDate', $dc['dcDate'], 'Дата диагностики', 'Дата диагностики');
                            if (array_key_exists('dcExpirationDate',$dc))
                                $data['EndDate'] = new ResultDataField('string','EndDate', $dc['dcExpirationDate'], 'Действителен до', 'Действителен до');
                            if (array_key_exists('odometerValue',$dc))
                                $data['Mileage'] = new ResultDataField('string','Mileage', $dc['odometerValue'], 'Пробег', 'Пробег');
                            $data['recordtype'] = new ResultDataField('string', 'RecordType', 'diagnostic', 'Тип записи', 'Тип записи');
                            $resultData->addResult($data);
                        }
                    }
                }
            }

            $rContext->setResultData($resultData);
            $rContext->setFinished();

            if (isset($swapData['captcha_id']) && isset($swapData['captcha_service']) /*&& $this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com'*/) {
/*
                $params = array(
                    'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                    'action' => 'reportgood',
                    'id' => $swapData['captcha_id'],
                );      
                $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/res.php?".http_build_query($params);
                $res = file_get_contents($url);
*/
                $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),21,3,'success','".$swapData['captcha_token']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
//                echo "Captcha ID ".$swapData['captcha_id']." reported as good with result $res\n";
            }

            $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
            unset($swapData['captcha_id']);
            $rContext->setSwapData($swapData);
            return true;
        } elseif (is_array($res) && array_key_exists('doc',$res) && is_array($res['doc'])) {
            $resultData = new ResultDataList();

            if (isset($res['doc']) && is_array($res['doc'])) {
                $data = array();
                $doc = $res['doc'];

                $data['Result'] = new ResultDataField('string','Result', sizeof($doc)?'В/у найдено, дата выдачи соответствует номеру':'В/у не найдено или неверно указана дата выдачи', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string','ResultCode', sizeof($doc)?'FOUND':'NOT_FOUND', 'Код результата', 'Код результата');
//                if (isset($doc['num']))
//                    $data['Number'] = new ResultDataField('string','Number', $doc['num'], 'Номер в/у', 'Номер в/у');
//                if (isset($doc['date']))
//                    $data['Date'] = new ResultDataField('string','Date', date('d.m.Y',strtotime($doc['date'])), 'Дата выдачи в/у', 'Дата выдачи в/у');
                if (isset($doc['srok']))
                    $data['EndDate'] = new ResultDataField('string','EndDate', date('d.m.Y',strtotime($doc['srok'])), 'Срок действия в/у', 'Срок действия в/у');
                if (isset($doc['bdate']))
                    $data['BirthDate'] = new ResultDataField('string','BirthDate', date('d.m.Y',strtotime($doc['bdate'])), 'Дата рождения', 'Дата рождения');
                if (isset($doc['cat']))
                    $data['Category'] = new ResultDataField('string','Category', $doc['cat'], 'Категория', 'Категория');
                if (isset($doc['nameop']))
                    $data['LastOperation'] = new ResultDataField('string', 'LastOperation', $doc['nameop'], 'Последнее действие', 'Последнее действие');
                if (isset($doc['st_kart']))
                    $data['DocStatus'] = new ResultDataField('string','DocStatus', ($doc['st_kart']!='T' && $doc['st_kart']!='Т')?'Недействителен':'Действует' , 'Статус документа', 'Статус документа');
                if (isset($doc['wanted']['dateWanted']))
                    $data['Wanted'] = new ResultDataField('string','Wanted', 'Документ недействителен и разыскивается с '.$doc['wanted']['dateWanted'], 'Розыск', 'Розыск');
                $data['RecordType'] = new ResultDataField('string','RecordType', 'license', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);
            }

            if (isset($res['decis']) && is_array($res['decis'])) {
                $state = array(
                    42 => 'Вынесено постановление о лишении права управления ТС',
                    60 => 'Постановление о лишении права управления ТС вступило в законную силу',
                    64 => 'Обжалование постановления суда',
                    68 => 'Исчисление срока лишения права управления ТС прервано',
                    71 => 'Прекращение исполнения постановления',
                    73 => 'Поступление информации об уплате штрафа',
                    76 => 'Поступление информации об уплате штрафа (от банка)',
                    78 => 'Начато исчисление срока лишения права управления',
                    79 => 'Окончание исчисления срока лишения права управления',
                    82 => 'Проведение проверки знаний ПДД',
                );
                foreach($res['decis'] as $dec) {
                    $data = array();
                    if (isset($dec['bplace']))
                        $data['BirthPlace'] = new ResultDataField('string','BirthPlace', $dec['bplace'], 'Место рождения', 'Место рождения');
                    if (isset($dec['date']))
                        $data['Date'] = new ResultDataField('string','Date', date('d.m.Y',strtotime($dec['date'])), 'Дата лишения', 'Дата вынесения постановления');
                    if (isset($dec['srok']))
                        $data['Term'] = new ResultDataField('string','Term', $dec['srok'], 'Срок лишения', 'Срок лишения прав');
                    if (isset($dec['comment']))
                        $data['Comment'] = new ResultDataField('string','Comment', $dec['comment'], 'Комментарий', 'Комментарий');
                    if (isset($dec['state']) && isset($state[$dec['state']]))
                        $data['State'] = new ResultDataField('string','State', $state[$dec['state']], 'Состояние', 'Состояние');
                    $data['RecordType'] = new ResultDataField('string','RecordType', 'decision', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                }
            }

            $rContext->setResultData($resultData);
            $rContext->setFinished();

            if (isset($swapData['captcha_id']) && isset($swapData['captcha_service']) /*&& $this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com'*/) {
/*
                $params = array(
                    'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                    'action' => 'reportgood',
                    'id' => $swapData['captcha_id'],
                );      
                $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/res.php?".http_build_query($params);
                $res = file_get_contents($url);
*/
                $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),21,3,'success','".$swapData['captcha_token']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
//                echo "Captcha ID ".$swapData['captcha_id']." reported as good with result $res\n";
            }

            $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
            unset($swapData['captcha_id']);
            $rContext->setSwapData($swapData);
            return true;
        } elseif ($checktype=='fines' && is_array($res) && !isset($swapData['pic'])) {
            if (isset($res['message']) && strpos($res['message'],'CAPTCHA')) {
//                $mysqli->query("UPDATE isphere.session SET sessionstatusid=4,statuscode='invalidcaptcha' WHERE id=".$swapData['session']->id);
//                unset($swapData['session']);
                $swapData['session']->token='';
                $swapData['session']->code='';
                $rContext->setSwapData($swapData);
            } elseif (isset($res['message']) && strpos($res['message'],'неверного введенного')) {
//                $mysqli->query("UPDATE isphere.session SET sessionstatusid=4,statuscode='invalidcaptcha' WHERE id=".$swapData['session']->id);
//                unset($swapData['session']);
                $swapData['session']->token='';
                $swapData['session']->code='';
                $rContext->setSwapData($swapData);
            } elseif (isset($res['message']) && strpos($res['message'],'Google')) {
                if (isset($swapData['captcha_id']) && isset($swapData['captcha_service']) /*&& $this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com'*/) {
/*
                    $params = array(
                        'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                        'action' => 'reportbad',
                        'id' => $swapData['captcha_id'],
                    );      
                    $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/res.php?".http_build_query($params);
                    $res = file_get_contents($url);
*/
                    $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),21,4,'invalidcaptcha','".$swapData['captcha_token']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
//                    echo "Captcha ID ".$swapData['captcha_id']." reported as bad with result $res\n";
                }
                unset($swapData['session']);
                unset($swapData['captcha_id']);
                unset($swapData['captcha_token']);
                $rContext->setSwapData($swapData);
//                $rContext->setFinished();
//                $rContext->setError($res['message']);
//                return false;
            } else {
                $resultData = new ResultDataList();
                $res_code = isset($res['code'])&&($res['code']==200)?'FOUND':(isset($res['status'])&&($res['status']==404)?'NOT_FOUND':'ERROR');
                $data['Result'] = new ResultDataField('string','Result', isset($res['code'])&&($res['code']==200)?'Проверка выполнена успешно, серия и номер свидетельства о регистрации ТС соответствует госномеру':(isset($res['message'])?$res['message']:'Ошибка при выполнении запроса'), 'Результат проверки', 'Результат проверки');
                $data['ResultCode'] = new ResultDataField('string','ResultCode', $res_code, 'Код результата', 'Код результата');
                if (isset($res['startDate']))
                    $data['startdate'] = new ResultDataField('string', 'StartDate', $res['startDate'], 'Начальная дата', 'Начальная дата');
                if (isset($res['endDate']))
                    $data['enddate'] = new ResultDataField('string', 'EndDate', $res['endDate'], 'Конечная дата', 'Конечная дата');
                if ($res_code=='FOUND' && array_key_exists('data',$res) && is_array($res['data'])) {
                    $data['fines'] = new ResultDataField('string', 'Fines', sizeof($res['data']), 'Неоплаченных штрафов', 'Неоплаченных штрафов');
                }
                $data['recordtype'] = new ResultDataField('string', 'RecordType', 'result', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);
                $swapData['result'] = $resultData;

                $swapData['data'] = array();
                $swapData['pics'] = array();
                if (isset($res['cafapPicsToken']))
                    $swapData['pics_token'] = $res['cafapPicsToken'];

                if ($res_code=='FOUND' && array_key_exists('data',$res) && is_array($res['data'])) {
                    foreach($res['data'] as $i => $fine) {
                        $data = array();
                        if (isset($fine['Division']) && isset($res['divisions'][$fine['Division']]) && isset($res['divisions'][$fine['Division']]['name'])) {
                            $data['disisionname'] = new ResultDataField('string','DivisionName', $res['divisions'][$fine['Division']]['name'], 'Административный орган', 'Административный орган');
                            $data['disisionaddr'] = new ResultDataField('string','DivisionAddress', $res['divisions'][$fine['Division']]['fulladdr'], 'Адрес административного органа', 'Адрес административного органа');
                        }
                        if (isset($fine['DateDecis']))
                            $data['date'] = new ResultDataField('string','Date', $fine['DateDecis'], 'Дата нарушения', 'Дата нарушения');
                        if (isset($fine['VehicleModel']))
                            $data['model'] = new ResultDataField('string','Model', $fine['VehicleModel'], 'Модель а/м', 'Модель а/м');
                        if (isset($fine['KoAPcode']))
                            $data['daparticlenum'] = new ResultDataField('string','DAPArticleNum', $fine['KoAPcode'], 'Номер статьи КоАПП', 'Номер статьи КоАПП');
                        if (isset($fine['KoAPtext']))
                            $data['daparticle'] = new ResultDataField('string','DAPArticle', $fine['KoAPtext'], 'Статья КоАПП', 'Статья КоАПП');
                        if (isset($fine['NumPost']))
                            $data['dapnumber'] = new ResultDataField('string', 'DAPNumber', $fine['NumPost'], 'Номер постановления', 'Номер постановления административного органа');
                        if (isset($fine['DatePost']))
                           $data['dapdate'] = new ResultDataField('string', 'DAPDate', $fine['DatePost'], 'Дата постановления', 'Дата постановления административного органа');
                        if (isset($fine['Summa']))
                            $data['sum'] = new ResultDataField('float', 'Sum', $fine['Summa'], 'Сумма', 'Сумма');
                        if (isset($fine['DateSSP']))
                            $data['fsspdate'] = new ResultDataField('string', 'FSSPDate', $fine['DateSSP'], 'Передано в ФССП', 'Передано в ФССП');
                        $data['recordtype'] = new ResultDataField('string', 'RecordType', 'fine', 'Тип записи', 'Тип записи');
                        $swapData['data'][$i] = $data;
                        if (isset($fine['enablePics']) && $fine['enablePics']) {
//                            $swapData['pics'][] = array('i'=>$i,'num'=>$fine['NumPost'],'div'=>$fine['Division']);
                            $swapData['pic'] = true;
                        }
                    }
                }
                $rContext->setSwapData($swapData);
            }
        } elseif ($checktype=='fines' && isset($swapData['pic'])) {
            $i = $swapData['pics'][0]['i'];
            $num = $swapData['pics'][0]['num'];
            if (is_array($res) && isset($res['photos']) && is_array($res['photos'])) {
                global $serviceurl;
                foreach ($res['photos'] as $j => $photo) {
                    if (isset($photo['base64Value'])) {
                        $jpgfile = 'logs/gibdd/'.$num.'_'.$j.'.jpg';
                        $jpg = base64_decode($photo['base64Value']);
                        if ($jpg) file_put_contents($jpgfile,$jpg);
                        $swapData['data'][$i]['photo'.$j] = new ResultDataField('image', 'Photo', $serviceurl.$jpgfile, 'Фото', 'Фото');
//                        echo $jpg."\n";
                    }
                }
            } elseif (!$content) {
//                echo "Thread ".$swapData['num']."  Empty photo  Captcha ID ".$swapData['captcha_id'.$swapData['num']]."  Session ID ".$swapData['session']->id."\n";
            }
            array_shift($swapData['pics']);
            $swapData['iteration']--;
            $rContext->setSwapData($swapData);
        } elseif(is_array($res) && array_key_exists('status',$res) && ($res['status']==200 || $res['status']==404)) {
            $resultData = new ResultDataList();
            $rContext->setResultData($resultData);
            $rContext->setFinished();

            if (isset($swapData['captcha_id']) && isset($swapData['captcha_service']) /*&& $this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com'*/) {
/*
                $params = array(
                    'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                    'action' => 'reportgood',
                    'id' => $swapData['captcha_id'],
                );      
                $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/res.php?".http_build_query($params);
                $res = file_get_contents($url);
*/
                $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),21,3,'success','".$swapData['captcha_token']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
//                echo "Captcha ID ".$swapData['captcha_id']." reported as good with result $res\n";
            }

            $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
            unset($swapData['captcha_id']);
            $rContext->setSwapData($swapData);
            return true;
        } elseif(is_array($res) && array_key_exists('code',$res) && ($res['code']==200 || $res['code']==404)) {
            $resultData = new ResultDataList();
            $rContext->setResultData($resultData);
            $rContext->setFinished();

            if (isset($swapData['captcha_id']) && isset($swapData['captcha_service']) /*&& $this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com'*/) {
/*
                $params = array(
                    'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                    'action' => 'reportgood',
                    'id' => $swapData['captcha_id'],
                );      
                $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/res.php?".http_build_query($params);
                $res = file_get_contents($url);
*/
                $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),21,3,'success','".$swapData['captcha_token']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
//                echo "Captcha ID ".$swapData['captcha_id']." reported as good with result $res\n";
            }

            $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
            unset($swapData['captcha_id']);
            $rContext->setSwapData($swapData);
            return true;
/*
        } elseif(($checktype=='fines') && is_array($res) && isset($res['data']) && (sizeof($res['data'])==0)) {
            $resultData = new ResultDataList();
            $rContext->setResultData($resultData);
            $rContext->setFinished();

            $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
            return true;
*/
        } elseif ($checktype!=='fines' || !isset($swapData['pic'])) {
            if (empty(trim($content))) {
//                echo date('h:i:s').' '.$initData['checktype'].' no data'." (".$swapData['num'].")\n";
//                echo "Thread ".$swapData['num']."  Empty answer  Captcha ID ".$swapData['captcha_id'.$swapData['num']]."  Session ID ".$swapData['session']->id."\n";
//                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='empty' WHERE id=".$swapData['session']->id);
                unset($swapData['session']);
                $rContext->setSwapData($swapData);
//                $rContext->setSleep(1);
//                return false;
            } elseif (is_array($res) && array_key_exists('message',$res)) {
                if (strpos($res['message'],'CAPTCHA')) {
//                    $mysqli->query("UPDATE isphere.session SET sessionstatusid=4,statuscode='invalidcaptcha' WHERE id=".$swapData['session']->id);
//                    unset($swapData['session']);
                    $swapData['session']->token='';
                    $swapData['session']->code='';
                    $rContext->setSwapData($swapData);
                } elseif (strpos($res['message'],'неверного введенного')) {
//                    $mysqli->query("UPDATE isphere.session SET sessionstatusid=4,statuscode='invalidcaptcha' WHERE id=".$swapData['session']->id);
//                    unset($swapData['session']);
                    $swapData['session']->token='';
                    $swapData['session']->code='';
                    $rContext->setSwapData($swapData);
                } elseif (strpos($res['message'],'Google')) {
                    if (isset($swapData['captcha_id']) && isset($swapData['captcha_service']) /*&& $this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com'*/) {
/*
                        $params = array(
                            'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                            'action' => 'reportbad',
                            'id' => $swapData['captcha_id'],
                        );      
                        $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/res.php?".http_build_query($params);
                        $res = file_get_contents($url);
*/
                        $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),21,4,'invalidcaptcha','".$swapData['captcha_token']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
//                        echo "Captcha ID ".$swapData['captcha_id']." reported as bad with result $res\n";
                    }
                    unset($swapData['session']);
                    unset($swapData['captcha_id']);
                    unset($swapData['captcha_token']);
                    $rContext->setSwapData($swapData);
//                    $rContext->setFinished();
//                    $rContext->setError($res['message']);
//                    return false;
                } elseif (strpos($res['message'],'No data found')) {
                    $resultData = new ResultDataList();
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();

                    if (isset($swapData['captcha_id']) && isset($swapData['captcha_service']) /*&& $this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com'*/) {
/*
                        $params = array(
                            'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                            'action' => 'reportgood',
                            'id' => $swapData['captcha_id'],
                        );      
                        $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/res.php?".http_build_query($params);
                        $res = file_get_contents($url);
*/
                        $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),21,3,'success','".$swapData['captcha_token']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
//                        echo "Captcha ID ".$swapData['captcha_id']." reported as good with result $res\n";
                    }

                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    unset($swapData['captcha_id']);
                    $rContext->setSwapData($swapData);
                    return true;
                } elseif (strpos($res['message'],' успешно получен')) {
//                    $error = 'Проверка завершилась ошибкой на стороне ГИБДД';
                    $resultData = new ResultDataList();

                    $data = array();
                    $data['Result'] = new ResultDataField('string','Result', 'В/у не найдено или неверно указана дата выдачи', 'Результат', 'Результат');
                    $data['ResultCode'] = new ResultDataField('string','ResultCode', 'NOT_FOUND', 'Код результата', 'Код результата');
                    $data['RecordType'] = new ResultDataField('string','RecordType', 'license', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();

                    if (isset($swapData['captcha_id']) && isset($swapData['captcha_service']) /*&& $this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com'*/) {
/*
                        $params = array(
                            'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                            'action' => 'reportgood',
                            'id' => $swapData['captcha_id'],
                        );      
                        $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/res.php?".http_build_query($params);
                        $res = file_get_contents($url);
*/
                        $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),21,3,'success','".$swapData['captcha_token']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
//                        echo "Captcha ID ".$swapData['captcha_id']." reported as good with result $res\n";
                    }

                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    unset($swapData['captcha_id']);
                    $rContext->setSwapData($swapData);
                    return true;
                } elseif ($res['message']=='Service has stop!') {
                    $error = 'Сервис выключен на стороне ГИБДД';
                } elseif ($res['message']!='ver.3.2' && $swapData['iteration']>5) {
                    $error = 'Внутренняя ошибка источника'; //trim($res['message']);
                }
//                $swapData['iteration'] = 100;
            } elseif ($content=="{}") {
                if ($swapData['iteration']>5)
                    $error = "Внутренняя ошибка источника";
            } elseif (strpos($content,'currently unavailable')) {
                if ($swapData['iteration']>5)
                    $error = "Внутренняя ошибка источника";
            } elseif (strpos($content,'internal error') || strpos($content,'Internal Server Error') || strpos($content,'Not Found')) {
                if ($swapData['iteration']>20)
                    $error = "Внутренняя ошибка источника";
                unset($swapData['session']);
                $rContext->setSwapData($swapData);
            } elseif (strpos($content,'Too Many Requests')) {
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='limit' WHERE id=".$swapData['session']->id);
                unset($swapData['session']);
                $rContext->setSwapData($swapData);
            } elseif (is_array($res) && array_key_exists('error',$res) && $res['error']) {
                file_put_contents('./logs/gibdd/'.$initData['checktype'].'_err_'.$swapData['iteration'].'_'.time().'.txt',/*curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".*/$fullcontent);
                $error = "Ошибка при получении ответа";
            } elseif (!$res || !isset($res['requestTime'])) {
                file_put_contents('./logs/gibdd/'.$initData['checktype'].'_err_'.$swapData['iteration'].'_'.time().'.txt',/*curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".*/$fullcontent);
                $error = "Некорректный ответ сервиса";
            }
        }

        if ($checktype=='fines' && isset($swapData['pics']) && sizeof($swapData['pics'])==0) {
            $resultData = $swapData['result'];
            foreach ($swapData['data'] as $data) {
                $resultData->addResult($data);
            }
            $rContext->setResultData($resultData);
            $rContext->setFinished();

            if (isset($swapData['captcha_id']) && isset($swapData['captcha_service']) /*&& $this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com'*/) {
/*
                $params = array(
                    'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                    'action' => 'reportgood',
                    'id' => $swapData['captcha_id'],
                );      
                $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/res.php?".http_build_query($params);
                $res = file_get_contents($url);
*/
                $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),now(),21,3,'success','".$swapData['captcha_token']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
//                echo "Captcha ID ".$swapData['captcha_id']." reported as good with result $res\n";
            }

            $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
            unset($swapData['captcha_id']);
            $rContext->setSwapData($swapData);
            return true;
        }

        if ($error || $swapData['iteration']>50) {
            $rContext->setFinished();
            $rContext->setError($error?$error:'Превышено количество попыток получения ответа');
        }

        $rContext->setSwapData($swapData);
        return true;
    }
}

?>
<?php

class ProverkaTOPlugin implements PluginInterface
{
    private $googlekey = '6LcPFewUAAAAANaw2rpJnQX48Lydz3YaaadcajZC';
    private $captcha_service = array(
//        array('host' => 'api.capmonster.cloud', 'key' => 'afb26cbb248d650ea8b8d88822984242'),
        array('host' => 'rucaptcha.com', 'key' => 'd167c71a9278312f184f17caa4e71050'),
        array('host' => 'api.anti-captcha.com', 'key' => '63def0a149a147e0d13e409cc8318fc3'),
    );
    private $minscore = 0.7;
    private $captcha_threads = 1;

    private $names = array (
                           'make' => array('Mark', 'Марка', 'Марка авто'),
                           'model' => array('Model', 'Модель', 'Модель авто'),
//                           'cat' => array('Category', 'Категория', 'Категория'),
                           'registered' => array('StartDate', 'Дата диагностики', 'Дата диагностики'),
                           'validity' => array('EndDate', 'Действителен до', 'Действителен до'),
                           'vin' => array('VIN', 'VIN', 'VIN'),
                           'kuzov' => array('BodyNum', 'Номер кузова', 'Номер кузова'),
                           'rama' => array('Chassis', 'Номер шасси (рамы)', 'Номер шасси (рамы)'),
                           'regZnak' => array('RegNum', 'Госномер', 'Государственный номер'),
                           'dkNomer' => array('Number', 'Номер карты', 'Номер карты'),
//                           'expert' => array('Expert', 'Эксперт', 'Эксперт'),
//                           'sto' => array('Operator', 'Оператор', 'Оператор'),
//                           'address' => array('Address', 'Адрес станции', 'Адрес станции'),
//                           'phone' => array('Phone', 'Телефон', 'Телефон'),
    );

    public function getName()
    {
        return 'ЕАИСТО';
    }

    public function getTitle()
    {
        return 'Поиск диагностической карты';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=53 ORDER BY lasttime limit 1");

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
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if( !isset($initData['vin']) && !isset($initData['bodynum']) && !isset($initData['regnum']))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (VIN или номер кузова или госномер)');

            return false;
        }
/*
        $rContext->setFinished();
        $rContext->setError('Сервис временно недоступен)');
        return false;
*/
        ////////////////////////////////////////////////////////////////////////////////////////////////////

        if (!isset($swapData['num'])) {
            $swapData['num']=1;
            $rContext->setSwapData($swapData);
        }

        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
        if (!isset($swapData['session'])) {
            unset($swapData['captcha']);
//            unset($swapData['captcha_id'.$swapData['num']]);
//            unset($swapData['captcha_token']);
            $swapData['session'] = $this->getSessionData($swapData['iteration']>20);
            $rContext->setSwapData($swapData);
            if(!$swapData['session']) {
                if (isset($swapData['iteration']) && ($swapData['iteration']>=120)) {
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
            $swapData['captcha_token'] = $swapData['session']->code;
        }
        $rContext->setSwapData($swapData);

        $ch = $rContext->getCurlHandler();

        $host = 'https://xn--80adjurebidw.xn--p1ai';
        $page = $host.'/';

        if (!isset($swapData['captcha_token'])) {
            if (!isset($swapData['captcha_id'.$swapData['num']])) {
                $swapData['captcha_service'] = intval(($swapData['iteration']-1)/2)%sizeof($this->captcha_service);
                $rContext->setSwapData($swapData);
                if ($this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com') {
                    $params = array(
                        'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                        'method' => 'userrecaptcha',
                        'googlekey' => $this->googlekey,
                        'pageurl' => $page,
                        'version' => 'v3',
                        'action' => 'proverkatorf',
                        'min_score' => $this->minscore,
                    );      
/*
                    if ($swapData['session']->proxy) {
                        $params['proxytype'] = 'http';
                        $params['proxy'] = ($swapData['session']->proxy_auth ? $swapData['session']->proxy_auth.'@' : '').$swapData['session']->proxy;
                    }
*/
                    $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/in.php?".http_build_query($params);
                } else {
                    $params = array(
                        "clientKey" => $this->captcha_service[$swapData['captcha_service']]['key'],
                        "task" => array(
                            "type" => "RecaptchaV3TaskProxyless",
//                            "type" => "NoCaptchaTask",
                            "websiteURL" => $page,
                            "websiteKey" => $this->googlekey,
                            "minScore" => $this->minscore,
                            "pageAction" => "proverkatorf",
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
                    $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/createTask";
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params,JSON_UNESCAPED_UNICODE));
                }
            } else {
                if ($this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com') {
                    $params = array(
                        'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                        'action' => 'get',
                        'id' => $swapData['captcha_id'.$swapData['num']],
                    );      
                    $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/res.php?".http_build_query($params);
                } else {
                    $params = array(
                        "clientKey" => $this->captcha_service[$swapData['captcha_service']]['key'],
                        "taskId" => $swapData['captcha_id'.$swapData['num']],
                    );
                    $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/getTaskResult";
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params,JSON_UNESCAPED_UNICODE));
                }
            }
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch,CURLOPT_TIMEOUT,2);
//            echo "$url\n";
//            var_dump($params);
//            echo "\n";
        } else {
            $url = $host.'/check.php';
            $post = array(
                'DK_NOMER' => '',
                'REG_ZNAK' => isset($initData['regnum'])?$initData['regnum']:'',
                'VIN' => isset($initData['vin'])?$initData['vin']:'',
                'NOMER_KUZOVA' => isset($initData['bodynum'])?$initData['bodynum']:'',
                'NOMER_RAMY' => '',
                'grecaptcha' => $swapData['captcha_token'],
            );
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                'Origin: '.$host,
                'X-Requested-With: XMLHttpRequest'));
            curl_setopt($ch, CURLOPT_REFERER, $page);
            curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
            if ($swapData['session']->proxy) {
                curl_setopt($ch,CURLOPT_PROXY,$swapData['session']->proxy);
                if ($swapData['session']->proxy_auth) {
                    curl_setopt($ch,CURLOPT_PROXYUSERPWD,$swapData['session']->proxy_auth); 
                    curl_setopt($ch,CURLOPT_PROXYAUTH,CURLAUTH_ANY); 
                }
            }
            curl_setopt($ch, CURLOPT_URL, $url);
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

        if (!isset($swapData['captcha_token'])) {
//            echo "$content\n";
            $res = json_decode($content, true);
            if (!isset($swapData['captcha_id'.$swapData['num']])) {
//                echo "Thread ".$swapData['num']."  Getting new captcha\n";
                if ($this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com') {
                    if (strpos($content,'OK|')!==false){
                        $swapData['captcha_id'.$swapData['num']] = substr($content,3);
                    } elseif ($swapData['iteration']>5) {
                        $rContext->setFinished();
                        $rContext->setError('Ошибка получения капчи');
                        file_put_contents('./logs/eaisto/proverkato_'.$initData['checktype'].'_captcha_err_'.time().'.txt',/*curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".*/$content."\r\n".$this->captcha_service[$swapData['captcha_service']]['host']);
                    }
                } else {
                    if (isset($res['taskId'])){
                        $swapData['captcha_id'.$swapData['num']] = $res['taskId'];
                    } elseif ($swapData['iteration']>5) {
                        $rContext->setFinished();
                        $rContext->setError('Ошибка получения капчи');
                        file_put_contents('./logs/eaisto/proverkato_'.$initData['checktype'].'_captcha_err_'.time().'.txt',/*curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".*/$content."\r\n".$this->captcha_service[$swapData['captcha_service']]['host']);
                    }
                }
            } else {
                if ($this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com') {
                    if ($content=='CAPCHA_NOT_READY') {
                    } else {
                        if (strpos($content,'OK|')!==false) {
                            $swapData['captcha_token'] = substr($content,3);
//                            echo "Thread ".$swapData['num']."  Received captcha ID ".$swapData['captcha_id'.$swapData['num']]."\n";
                        } elseif ($swapData['iteration']>5) {
//                            $rContext->setFinished();
//                            $rContext->setError('Ошибка распознавания капчи');
                        }
                        unset($swapData['captcha_id'.$swapData['num']]);
                    }
                } else {
                    if (!$content) {
                    } elseif (isset($res['status']) && $res['status']!=='ready') {
                    } else {
                        if (isset($res['solution']['gRecaptchaResponse'])) {
                            $swapData['captcha_token'] = $res['solution']['gRecaptchaResponse'];
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

        if ($content) {
            file_put_contents('./logs/eaisto/proverkato_'.time().'.txt',$content."\n\n".$swapData['iteration']);

            $res = json_decode($content, true);

            if($res && isset($res[0]['dkNomer'])) {
                $resultData = new ResultDataList();

                foreach ($res as $row) {
                    $data = array();
                    foreach ($row as $key => $val) {
                        if ($val && isset($this->names[$key])) {
                            $field = $this->names[$key];
                            if (strpos($field[0],'Date')) $val = strtr($val,array('-'=>'.'));
                            $data[$field[0]] = new ResultDataField(isset($field[3])?$field[3]:'string', $field[0], $val, $field[1], $field[2]);
                        }
                    }
                    if (sizeof($data)) $resultData->addResult($data);
                }

                $rContext->setResultData($resultData);
                $rContext->setFinished();
                return true;
            } elseif($res && isset($res['success']) && $res['success']) {
                $resultData = new ResultDataList();
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                return true;
            } elseif(preg_match("/captcha/",$content)) {
                unset($swapData['captcha_token']);
            } else if($swapData['iteration']>=3) {
                $error = "Некорректный ответ сервиса";
                file_put_contents('./logs/eaisto/proverkato_err_'.time().'.txt',$content);
            }
        }

        if ($error || $swapData['iteration']>20) {
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
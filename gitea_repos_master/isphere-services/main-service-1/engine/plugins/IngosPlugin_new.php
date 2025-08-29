<?php

class IngosPlugin implements PluginInterface
{
//    private $googlekey = '6LcEHCojAAAAAOhBzitchxZZd3j41Gp7roV5xvCb';
    private $googlekey = '6LdBCH0hAAAAABuOdF0gZAZMb1dVWzq16M1RLOO8';
    private $captcha_service = array(
        array('host' => 'capmonster.i-sphere.local', 'key' => 'afb26cbb248d650ea8b8d88822984242'),
//        array('host' => 'api.capmonster.cloud', 'key' => 'afb26cbb248d650ea8b8d88822984242'),
//        array('host' => 'api.capmonster.cloud', 'key' => 'afb26cbb248d650ea8b8d88822984242'),
//        array('host' => 'api.capmonster.cloud', 'key' => 'afb26cbb248d650ea8b8d88822984242'),
//        array('host' => 'rucaptcha.com', 'key' => 'd167c71a9278312f184f17caa4e71050'),
//        array('host' => 'api.anti-captcha.com', 'key' => '63def0a149a147e0d13e409cc8318fc3'),
    );
    private $minscore = 0.3;
    private $captcha_threads = 1;

    private $names = array (
                           'mark' => array('Mark', 'Марка', 'Марка'),
                           'model' => array('Model', 'Модель', 'Модель'),
                           'type' => array('Type', 'Тип', 'Тип'),
                           'category' => array('Category', 'Категория', 'Категория'),
                           'year' => array('Year', 'Год выпуска', 'Год выпуска'),
                           'wheel' => array('Wheel', 'Руль', 'Руль'),
                           'engine_type' => array('EngineType', 'Тип двигателя', 'Тип двигателя'),
                           'engine_powerHp' => array('EnginePower', 'Мощность двигателя, л.с.', 'Мощность двигателя, л.с.'),
                           'engine_volume' => array('EngineVolume', 'Объем двигателя', 'Объем двигателя'),
                           'drive' => array('Drive', 'Привод', 'Привод'),
                           'transmission' => array('Transmission', 'Тип КПП', 'Тип КПП'),
                           'body_type' => array('BodyType', 'Тип кузова', 'Тип кузова'),
                           'body_color' => array('Color', 'Цвет', 'Цвет'),
//                           'weight_netto' => array('Weight', 'Масса', 'Масса'),
                           'weight_max' => array('FullWeight', 'Полная масса', 'Полная масса'),
                           'VIN' => array('VIN', 'VIN', 'VIN'),
                           'КУЗОВ' => array('BobyNum', 'Номер кузова', 'Номер кузова'),
                           'СТС' => array('STS', 'Номер СТС', 'Номер СТС'),
                           'СТС_date' => array('STSDate', 'Дата выдачи СТС', 'Дата выдачи СТС'),
                           'ПТС' => array('PTS', 'Номер ПТС', 'Номер ПТС'),
                           'ПТС_date' => array('PTSDate', 'Дата выдачи ПТС', 'Дата выдачи ПТС'),
                           'ЭПТС' => array('EPTS', 'Номер ЭПТС', 'Номер ЭПТС'),
                           'ЭПТС_date' => array('EPTSDate', 'Дата выдачи ЭПТС', 'Дата выдачи ЭПТС'),
    );

    public function getName()
    {
        return 'CarInfo';
    }

    public function getTitle()
    {
        return 'Поиск информации по автомобилю';
    }

    public function getSessionData($sourceid,$nocaptcha=0)
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

        $result = $mysqli->query("UPDATE isphere.session s SET request_id=".$reqId." WHERE sessionstatusid=2 AND sourceid=$sourceid AND (captcha='' OR captchatime>DATE_SUB(now(), INTERVAL 110 SECOND)) ORDER BY lasttime limit 1");
        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=$sourceid AND request_id=".$reqId." ORDER BY lasttime limit 1");

//        if($result && $result->num_rows==0 && $nocaptcha) {
//            $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,'' captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid IN (1,2,3,4,5) AND sourceid=$sourceid ORDER BY lasttime limit 1");
//        } else
//            $nocaptcha = false;

        if($result)
        {
            $row = $result->fetch_object();

            if ($row)
            {
                $sessionData = new \StdClass;

                $sessionData->proxyid = $row->proxyid?$row->proxyid:'NULL';
                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;

                $sessionData->id = $row->id;
                $sessionData->code = $row->captcha;
                $sessionData->token = $row->token;
                $sessionData->starttime = $row->starttime;
                $sessionData->lasttime = $row->lasttime;
                $sessionData->cookies = $row->cookies;
                $sessionData->nocaptcha = $nocaptcha||!$row->captcha;

                $mysqli->query("UPDATE isphere.session SET ".($sessionData->nocaptcha?"request_id=NULL,":"sessionstatusid=3,endtime=now(),")."lasttime=now(),statuscode='used',used=ifnull(used,0)+1 WHERE id=".$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        global $mysqli;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if(!isset($initData['regnum']) && !isset($initData['vin']))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (VIN или госномер)');

            return false;
        }

        if(isset($initData['vin']) && !preg_match("/^[A-HJ-NPR-Z0-9]{17}$/i",$initData['vin']))
        {
            $rContext->setFinished();
            $rContext->setError('VIN должен состоять из 17 латинских букв или цифр кроме I,O,Q');

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
            $swapData['session'] = $this->getSessionData(62);
            $rContext->setSwapData($swapData);
            if(!$swapData['session']) {
                if (isset($swapData['iteration']) && ($swapData['iteration']>=30)) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                } else {
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);
                }
                return false;
            }
        }

        if (!isset($swapData['captcha_token']) && !isset($swapData['captcha_id'.$swapData['num']])) {
            $swapData['captcha_session'] = $this->getSessionData(65);
            if ($swapData['captcha_session'] && $swapData['captcha_session']->code) {
                $token = $swapData['captcha_session']->code;
                $swapData['captcha_token'] = $token;
                unset($swapData['captcha_id']);
                unset($swapData['captcha_service']);
                echo "Daemon token: ".substr($token,0,5)."...".substr($token,strlen($token)-5,5)."\n";
/*
            } elseif ($swapData['iteration']%5) {
                if ($swapData['iteration']%10==1)
                    $mysqli->query("INSERT INTO isphere.session (used,endtime,sourceid,sessionstatusid,statuscode,captcha_service) VALUES (1,now(),65,4,'needmore','')");
                $rContext->setSwapData($swapData);
                $rContext->setSleep(1);
                return false;
*/
            } else {
                echo "Daemon token not ready\n";
            }
        }

        $rContext->setSwapData($swapData);

        $ch = $rContext->getCurlHandler();

        $host = 'https://www.ingos.ru';
        $page = $host.'/auto/osago/calc';

        if (!isset($swapData['captcha_token'])) {
            if (!isset($swapData['captcha_id'.$swapData['num']])) {
                $swapData['captcha_service'.$swapData['num']] = intval(($swapData['iteration']-1)/2)%sizeof($this->captcha_service);
                $rContext->setSwapData($swapData);
                if ($this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']=='rucaptcha.com') {
                    $params = array(
                        'key' => $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['key'],
                        'method' => 'userrecaptcha',
                        'googlekey' => $this->googlekey,
                        'pageurl' => $page,
                        'version' => 'v2',
//                        'version' => 'v3',
//                        'action' => 'auto',
//                        'min_score' => $this->minscore,
                        'enterprise' => 1,
//                        'invisible' => 1,
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
                            "type" => "RecaptchaV2EnterpriseTaskProxyless",
//                            "type" => "RecaptchaV3TaskProxyless",
                            "websiteURL" => $page,
                            "websiteKey" => $this->googlekey,
//                            "minScore" => $this->minscore,
//                            "pageAction" => "auto",
//                            "isEnterprise" => true,
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
                    $url = "http://".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']."/createTask";
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
                    $url = "http://".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']."/getTaskResult";
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params,JSON_UNESCAPED_UNICODE));
                }
            }
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            echo date('H:i:s')." ".$swapData['iteration']." ".$swapData['session']->id.": $url\n";
//            var_dump($params);
//            echo "\n";
        } else {
            $params = array();
            if(isset($initData['regnum']))
                $params['number'] = $initData['regnum'];
            if(isset($initData['vin']))
                $params['vin'] = $initData['vin'];
            $params['masked'] = false;
            $url = $host.'/api/auto/v1/search?'.http_build_query($params);
            $header = array(
                'Accept: application/json, text/plain, */*',
                'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
                'Ingos-Timestamp-Action: AUTO_SEARCH',
                'Recaptcha-Action: auto',
                'Recaptcha-Token: '.$swapData['captcha_token'],
//                'Recaptcha-Version: v3',
                'Recaptcha-Version: enterprise-challenge',
                'Sessionid: '.$swapData['session']->token,
                'DNT: 1',
                'Connection: keep-alive',
//                'Origin: '.$host,
//                'X-Requested-With: XMLHttpRequest',
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
//            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_REFERER, $page);
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
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            echo date('H:i:s')." ".$swapData['iteration']." ".$swapData['session']->id.": $url\n";
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
        $error = false; //($swapData['iteration']>5) ? curl_error($rContext->getCurlHandler()) : '';
        if (strpos($error,'timed out') || strpos($error,'connection')) {
            $error = false;
//                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 10 minute),sessionstatusid=6 WHERE id=" . $swapData['session']->id);
        }

        $content = curl_multi_getcontent($rContext->getCurlHandler());

        if (!isset($swapData['captcha_token'])) {
            echo "$content\n";
            $res = json_decode($content, true);
            if (!isset($swapData['captcha_id'.$swapData['num']])) {
//                echo "Thread ".$swapData['num']."  Getting new captcha\n";
                if ($this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']=='rucaptcha.com') {
                    if (strpos($content,'OK|')!==false){
                        $swapData['captcha_id'.$swapData['num']] = substr($content,3);
                    } elseif ($swapData['iteration']>10) {
//                        $rContext->setFinished();
//                        $rContext->setError('Ошибка получения капчи');
                        file_put_contents('./logs/ingos/ingos_'.$initData['checktype'].'_captcha_err_'.time().'.txt',/*curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".*/$content."\r\n".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']);
                    }
                } else {
                    if (isset($res['taskId'])){
                        $swapData['captcha_id'.$swapData['num']] = $res['taskId'];
                    } elseif (isset($res['errorCode']) && $res['errorCode']=='ERROR_ZERO_BALANCE') {
                        $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['disabled'] = 1;
                    } elseif ($swapData['iteration']>10) {
//                        $rContext->setFinished();
//                        $rContext->setError('Ошибка получения капчи');
                        file_put_contents('./logs/ingos/ingos_'.$initData['checktype'].'_captcha_err_'.time().'.txt',/*curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".*/$content."\r\n".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']);
                    }
                }
            } else {
                if ($this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']=='rucaptcha.com') {
                    if ($content=='CAPCHA_NOT_READY') {
                    } else {
                        if (strpos($content,'OK|')!==false) {
                            $swapData['captcha_token'] = substr($content,3);
                            $swapData['captcha_id'] = $swapData['captcha_id'.$swapData['num']];
                            $swapData['captcha_service'] = $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'];
//                            echo "Thread ".$swapData['num']."  Received captcha ID ".$swapData['captcha_id'.$swapData['num']]."\n";
                        } elseif ($swapData['iteration']>10) {
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
                            $swapData['captcha_id'] = $swapData['captcha_id'.$swapData['num']];
                            $swapData['captcha_service'] = $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'];
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

        if ($content) {
            file_put_contents('./logs/ingos/ingos_'.$swapData['iteration'].'_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);

            $start = strpos($content,'{');
            $content = trim(substr($content,$start,strlen($content)-$start+1));
            $res = json_decode($content, true);

            if($res && isset($res['type'])) {
                $resultData = new ResultDataList();

                $keys = array();
                foreach ($res as $name => $row) {
                    if (!is_array($row)) {
                        $keys[$name] = $row;
                    } elseif (is_array($row) && isset($row['name'])) {
                        $keys[$name] = $row['name'];
                    } else {
                        foreach ($row as $name2 => $row2) {
                            if (!is_array($row2)) {
                                $keys[$name.'_'.$name2] = $row2;
                            } elseif (is_array($row2) && isset($row2['name'])) {
                                $keys[$name.'_'.$name2] = $row2['name'];
                            } elseif (isset($row2['type']['name']) && isset($row2['number'])) {
                                $keys[$row2['type']['name']] = $row2['number'];
                                if (isset($row2['date']))
                                    $keys[$row2['type']['name'].'_date'] = $row2['date'];
                            }
                        }
                    }
                }

                $data = array();
                foreach ($keys as $key => $val) {
                    if ($val && isset($this->names[$key])) {
                        $field = $this->names[$key];
                        if (strpos($key,'_date')) $val = date('d.m.Y',strtotime(substr($val,0,10)));
                        if (preg_match("/^[A-ZА-Я]\-[А-Я]$/i",substr($val,0,3))) $val = substr($val,2);
                        $data[$field[0]] = new ResultDataField(isset($field[3])?$field[3]:'string', $field[0], $val, $field[1], $field[2]);
                    }
                }
                if (sizeof($data)) $resultData->addResult($data);

                $rContext->setResultData($resultData);
                $rContext->setFinished();

                $mysqli->query("UPDATE isphere.session SET successtime=now(),success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);

                if (isset($swapData['captcha_id']) && isset($swapData['captcha_token']) && isset($swapData['captcha_service'])) {
                     $mysqli->query("INSERT INTO isphere.session (used,endtime,captchatime,sourceid,sessionstatusid,statuscode,proxyid,captcha,captcha_service,captcha_id) VALUES (1,now(),now(),65,3,'success',".$swapData['session']->proxyid.",'".$swapData['captcha_token']."','".$swapData['captcha_service']."','".$swapData['captcha_id']."')");
//                   echo "Captcha ID ".$swapData['captcha_id']." from ".$swapData['captcha_service']." reported as good with result $res\n";
                }

                if (isset($swapData['captcha_session'])) {
                    $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['captcha_session']->id);
                    unset($swapData['captcha_session']);
                }

                return true;
            } elseif($res && isset($res['code']) && $res['code']=='AUTO_VEHICLE_NOT_FOUND_EXCEPTION') {
                $resultData = new ResultDataList();
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                $mysqli->query("UPDATE isphere.session SET successtime=now(),success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);

                if (isset($swapData['captcha_id']) && isset($swapData['captcha_token']) && isset($swapData['captcha_service'])) {
                     $mysqli->query("INSERT INTO isphere.session (used,endtime,captchatime,sourceid,sessionstatusid,statuscode,proxyid,captcha,captcha_service,captcha_id) VALUES (1,now(),now(),65,3,'success',".$swapData['session']->proxyid.",'".$swapData['captcha_token']."','".$swapData['captcha_service']."','".$swapData['captcha_id']."')");
//                   echo "Captcha ID ".$swapData['captcha_id']." from ".$swapData['captcha_service']." reported as good with result $res\n";
                }

                if (isset($swapData['captcha_session'])) {
                    $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['captcha_session']->id);
                    unset($swapData['captcha_session']);
                }

                return true;
            } elseif($res && isset($res['code']) && $res['code']=='CAPTCHA_EXCEPTION') {
                if (isset($swapData['captcha_id']) && isset($swapData['captcha_token']) && isset($swapData['captcha_service'])) {
                    $mysqli->query("INSERT INTO isphere.session (used,endtime,captchatime,sourceid,sessionstatusid,statuscode,proxyid,captcha,captcha_service,captcha_id) VALUES (1,now(),now(),65,4,'invalidcaptcha',".$swapData['session']->proxyid.",'".$swapData['captcha_token']."','".$swapData['captcha_service']."','".$swapData['captcha_id']."')");
//                    echo "Captcha ID ".$swapData['captcha_id']." from ".$swapData['captcha_service']." reported as bad with result $res\n";
                }

                if (isset($swapData['captcha_session'])) {
                    $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=4,statuscode='invalidcaptcha' WHERE statuscode='used' AND id=" . $swapData['captcha_session']->id);
                    unset($swapData['captcha_session']);
                }
                unset($swapData['captcha_id']);
                unset($swapData['captcha_token']);
            } else if($swapData['iteration']>0) {
                $error = "Некорректный ответ сервиса";
                file_put_contents('./logs/ingos/ingos_err_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
            }
        }

        if ($error || $swapData['iteration']>=30) {
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
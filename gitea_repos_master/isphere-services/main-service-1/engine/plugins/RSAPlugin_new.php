<?php

class RSAPlugin implements PluginInterface
{
    private $googlekey = '6LcWXc8gAAAAAMpgB0-7TzTELlr8f7T2XiTrexO5'; //'6Lf2uycUAAAAALo3u8D10FqNuSpUvUXlfP7BzHOk';
    private $captcha_service = array(
//        array('host' => 'api.capmonster.cloud', 'key' => 'afb26cbb248d650ea8b8d88822984242'),
//        array('host' => 'rucaptcha.com', 'key' => 'd167c71a9278312f184f17caa4e71050'),
//        array('host' => 'api.anti-captcha.com', 'key' => '63def0a149a147e0d13e409cc8318fc3'),
//        array('host' => 'capmonster.i-sphere.local', 'key' => 'afb26cbb248d650ea8b8d88822984242'),
        array('host' => 'api.nextcaptcha.com', 'key' => 'next_ee8fed6e0853b731e900b14869215f7741'),
    );
    private $minscore = 0.9;
    private $captcha_threads = 1;

    public function str_rus($text) {
        $trans = array(
                'A' => 'А',
                'B' => 'В',
                'C' => 'С',
                'E' => 'Е',
                'H' => 'Н',
                'K' => 'К',
                'M' => 'М',
                'O' => 'О',
                'P' => 'Р',
                'T' => 'Т',
                'X' => 'Х',
                'Y' => 'У',
        );
        $text = str_uprus(strtoupper($text));
        if (preg_match("/[A-Z]/", $text))
            $text = strtr($text, $trans);
        return $text;
    }

    public function getName($checktype = '')
    {
        $name = array(
            '' => 'RSA',
            'rsa_kbm' => 'RSA_kbm',
            'rsa_org' => 'RSA_org',
            'rsa_policy' => 'RSA_policy',
            'rsa_osagovehicle' => 'RSA_osagovehicle',
            'rsa_bsostate' => 'RSA_bsostate',
        );
        return isset($name[$checktype])?$name[$checktype]:$name[''];
//        return 'RSA';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Поиск в РСА',
            'rsa_kbm' => 'РСА - проверка КБМ водителя',
            'rsa_org' => 'РСА - проверка КБМ организации',
            'rsa_policy' => 'РСА - поиск полиса ОСАГО по автомобилю',
            'rsa_osagovehicle' => 'РСА - информация о застрахованном автомобиле',
            'rsa_bsostate' => 'РСА - информация о полисе ОСАГО',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Поиск полиса ОСАГО в базе РСА';
    }

    public function getSessionData($sourceid=44,$nocaptcha=0)
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

        $result = $mysqli->query("UPDATE isphere.session s SET request_id=".$reqId." WHERE sessionstatusid=2 AND sourceid=$sourceid AND (captcha='' OR captchatime>DATE_SUB(now(), INTERVAL 110 SECOND))".($nocaptcha?"AND lasttime<DATE_SUB(now(), INTERVAL 20 SECOND) ":"")." ORDER BY lasttime limit 1");
        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sourceid=$sourceid AND request_id=".$reqId." ORDER BY lasttime limit 1");

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

                $sessionData->proxyid = $row->proxyid;
                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;

                $sessionData->id = $row->id;
                $sessionData->code = $row->captcha;
                $sessionData->token = $row->token;
                $sessionData->starttime = $row->starttime;
                $sessionData->lasttime = $row->lasttime;
                $sessionData->cookies = $row->cookies;
                $sessionData->nocaptcha = $nocaptcha||!$row->captcha;

                $mysqli->query("UPDATE isphere.session SET ".($sessionData->nocaptcha?"":"sessionstatusid=3,endtime=now(),")."lasttime=now(),statuscode='used',used=ifnull(used,0)+1,request_id=NULL WHERE id=".$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        global $mysqli;
        global $userId;
        global $clientId;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],4);

        if(($checktype=='kbm') && (!isset($initData['driver_number']) || !isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date'])))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (фамилия, имя, дата рождения и номер в/у)');

            return false;
        }

        if(($checktype=='org') && !isset($initData['inn']))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (фамилия, имя, дата рождения и номер в/у)');

            return false;
        }

        if(($checktype=='policy') && !isset($initData['vin']) && !isset($initData['bodynum']) && !isset($initData['regnum']))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (VIN или номер кузова или госномер)');

            return false;
        }

        if(($checktype=='osagovehicle' || $checktype=='bsostate') && !isset($initData['osago']))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (серия и номер полиса ОСАГО)');

            return false;
        }

        $testvin = array(
            '9BSR4X20003937785',
            '9BSR4X20003903396',
            'WKVDAF30300093173',
            'WDB93002200088386',
            'XW8AND0D000229077',
            'Z9M96340030427192',
        );
        if(/*$userId==3481 && */$checktype=='policy' && isset($initData['vin']) && in_array($initData['vin'],$testvin)) {
            $data['PolicyNumber'] = new ResultDataField('string','PolicyNumber', 'ХХХ 1234567890', 'Полис ОСАГО', 'Полис ОСАГО');
            $data['Company'] = new ResultDataField('string','Company', 'АО "АльфаСтрахование"', 'Страховая компания', 'Страховая компания');
            $data['PolicyStatus'] = new ResultDataField('string','PolicyStatus', 'Действует', 'Статус полиса', 'Статус полиса');
            $data['Purpose'] = new ResultDataField('string','Purpose', 'Личная', 'Цель использования', 'Цель использования');
            $data['Limited'] = new ResultDataField('string','Limited', 'Ограничен список лиц, допущенных к управлению', 'Ограничения', 'Ограничения');
            $data['Drivers'] = new ResultDataField('string','Drivers', '1', 'Допущено к управлению', 'Допущено к управлению');
            $data['Insurant'] = new ResultDataField('string','Insurant', 'И***** ИВАН ИВАНОВИЧ', 'Cтрахователь', 'Cтрахователь');
            $data['InsurantBirthDate'] = new ResultDataField('string','InsurantBirthDate', '01.05.1999', 'Дата рождения страхователя', 'Дата рождения страхователя');
            $data['Owner'] = new ResultDataField('string','Owner', 'И***** ИВАН ИВАНОВИЧ', 'Cобственник', 'Cобственник');
            $data['OwnerBirthDate'] = new ResultDataField('string','OwnerBirthDate', '01.05.1999', 'Дата рождения собственника', 'Дата рождения собственника');
            $data['Kbm'] = new ResultDataField('string','Kbm', '0.68', 'КБМ', 'КБМ');
            $data['Region'] = new ResultDataField('string','Region', 'г Москва', 'Регион', 'Регион');
            $data['Total'] = new ResultDataField('string','Total', '13723.59', 'Страховая премия', 'Страховая премия');
            $data['Model'] = new ResultDataField('string','Model', 'Audi Q7', 'Марка и модель', 'Марка и модель');
            $data['Category'] = new ResultDataField('string','Category', 'B', 'Категория', 'Категория');
            $data['Power'] = new ResultDataField('string','Power', '249.00', 'Мощность двигателя, л.с.', 'Мощность двигателя, л.с.');
            $data['RegNum'] = new ResultDataField('string','RegNum', 'А999КН799', 'Госномер', 'Госномер');
            $data['VIN'] = new ResultDataField('string','VIN', $initData['vin'], 'VIN', 'VIN');
            $data['Type'] = new ResultDataField('string','Type', 'policy', 'Тип записи', 'Тип записи');

            $resultData = new ResultDataList();
            $resultData->addResult($data);
            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return false;
        }

        $mode = array(
            'kbm' => 'kbm',
            'org' => 'kbm',
            'policy' => 'policyInfo',
            'bsostate' => 'policyInfo',
        );
        if (!isset($swapData['mode'])) {
            $swapData['date'] = date('d.m.Y',isset($initData['reqdate']) ? strtotime($initData['reqdate']) : time());
//            $swapData['mode'] = (isset($initData['vin']) || isset($initData['regnum'])) ? 'policy' : (isset($initData['osago']) ? $initData['checktype'] : 'kbm');
            $swapData['mode'] = $mode[$checktype];
        }
        $rContext->setSwapData($swapData);
/*
        global $clientId;
        if ($checktype=='policy' && $clientId!=264 && $clientId!=265) { // odd isphere
            $rContext->setError('Сервис временно недоступен');
            $rContext->setFinished();
            return false;
        }
        if ($checktype=='bsostate' && $clientId!=264 && $clientId!=265) { // odd isphere
//            $rContext->setError('Сервис временно недоступен');
            $rContext->setFinished();
            return false;
        }
*/
        ////////////////////////////////////////////////////////////////////////////////////////////////////

        if (!isset($swapData['num'])) $swapData['num']=1;

        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
        if (!isset($swapData['session'])) {
            unset($swapData['captcha']);
//            unset($swapData['captcha_id'.$swapData['num']]);
//            unset($swapData['captcha_token']);
            $swapData['session'] = $this->getSessionData(44,1/*,$swapData['iteration']>30*/);
            if(!$swapData['session']) {
                if (isset($swapData['iteration']) && ($swapData['iteration']>=60)) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                } else {
                    $rContext->setSleep(1);
                }
                $rContext->setSwapData($swapData);
                return false;
            }
        }
/*
//        if (!isset($swapData['captcha_token']) && !isset($swapData['captcha_id'.$swapData['num']])) {
        if (!isset($swapData['captcha_token'])) {
            $token = neuro_token('v3',$this->googlekey,'submit');
            if (strlen($token)>30) {
                $swapData['captcha_token'] = $token;
                $swapData['captcha_service'] = 'queue';
                $swapData['captcha_id'] = 0;
//                echo "Queue token (".(isset($swapData['row'])?'extract_actual_notification':'search_notary')."): ".substr($token,0,5)."...".substr($token,strlen($token)-5,5)."\n";
            } else {
//                echo "Queue token not ready (".(isset($swapData['row'])?'extract_actual_notification':'search_notary').")\n";
            }
        }
*/
/*
        if (!isset($swapData['captcha_token']) && !isset($swapData['captcha_id'.$swapData['num']])) {
            $swapData['captcha_session'] = $this->getSessionData(66);
            if ($swapData['captcha_session'] && $swapData['captcha_session']->code) {
                $token = $swapData['captcha_session']->code;
                $swapData['captcha_token'] = $token;
                unset($swapData['captcha_id']);
                unset($swapData['captcha_service']);
//                echo "Daemon token: ".substr($token,0,5)."...".substr($token,strlen($token)-5,5)."\n";
            } elseif ($swapData['iteration']%5) {
                if ($swapData['iteration']%10==1)
                    $mysqli->query("INSERT INTO isphere.session (used,endtime,sourceid,sessionstatusid,statuscode,captcha_service) VALUES (1,now(),66,4,'needmore','')");
                $rContext->setSwapData($swapData);
                $rContext->setSleep(1);
                return false;
            } else {
//                echo "Daemon token not ready\n";
            }
        }
*/
        $rContext->setSwapData($swapData);

        $ch = $rContext->getCurlHandler();

        $site = 'https://dkbm-web.autoins.ru';
        $page = $site.'/dkbm-web-1.0/'.$swapData['mode'].'.htm';

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
                        'version' => 'v3',
                        'action' => 'submit',
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
//                            "type" => "NoCaptchaTaskProxyless",
                            "websiteURL" => $page,
                            "websiteKey" => $this->googlekey,
                            "minScore" => $this->minscore,
                            "pageAction" => "submit",
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
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
//            echo "$url\n";
//            var_dump($params);
//            echo "\n";
        } elseif (isset($swapData['header_image']) && isset($swapData['body_image'])) {
            $url = 'http://172.16.199.11/recognize_rsa';
            $fields = array(
                'header_file' => new CurlFile($swapData['header_image'], 'image/png', 'header_image.png'),
                'body_file' => new CurlFile($swapData['body_image'], 'image/png', 'body_image.png'),
            );
            if (isset($swapData['policy_image'])) $fields['policy_file'] = new CurlFile($swapData['policy_image'], 'image/png', 'policy_image.png');
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        } else {
            $url = $page;
            if ($swapData['mode']=='policyInfo') {
                $params = array(
                    'bsoseries' => isset($initData['osago'])?mb_substr($initData['osago'],0,3):'CCC',
                    'bsonumber' => isset($initData['osago'])?trim(mb_substr($initData['osago'],3)):'',
                    'requestDate' => $swapData['date'],
                    'vin' => isset($initData['vin'])?$initData['vin']:'',
                    'licensePlate' => isset($initData['regnum'])?$initData['regnum']:'',
                    'bodyNumber' => isset($initData['bodynum'])?$initData['bodynum']:'',
                    'chassisNumber' => isset($initData['chassis'])?$initData['chassis']:'',
                    'isBsoRequest' => isset($initData['osago']),
                );
                if (isset($swapData['ready'])) {
                    $params['processId'] = $swapData['id'];
                    $params['g-recaptcha-response'] = '';
                    $url = $site.'/dkbm-web-1.0/policyInfoData.htm';
                } elseif (isset($swapData['id'])) {
                    $params = false;
                    $url = $site.'/dkbm-web-1.0/checkPolicyInfoStatus.htm?'.http_build_query(array('processId'=>$swapData['id'],'_'=>time()));
                }
            } elseif ($swapData['mode']=='kbm') {
                $params = array(
                    'subjectType' => $checktype=='org'?'juridical':'physical',
                    'isRestrict' => $checktype!='org',
                    'surname' => isset($initData['last_name'])?$initData['last_name']:'',
                    'name' => isset($initData['first_name'])?$initData['first_name']:'',
                    'patronymic' => isset($initData['patronymic'])?$initData['patronymic']:'',
                    'birthday' => isset($initData['date'])?date('d.m.Y',strtotime($initData['date'])):'',
                    'driverDocSeries' => isset($initData['driver_number'])?mb_substr($initData['driver_number'],0,4):'',
                    'driverDocNumber' => isset($initData['driver_number'])?trim(mb_substr($initData['driver_number'],4)):'',
                    'agrDate' => $swapData['date'],
                    'documentType' => 12,
                    'documentSeries' => '',
                    'documentNumber' => '',
                    'inn' => ($checktype=='org' && isset($initData['inn']))?$initData['inn']:'',
                    'addInn' => '',
                    'isInnChanged' => false,
                    'isDriverChanged' => false,
                    'addSurname' => '',
                    'addName' => '',
                    'addPatronymic' => '',
                    'addDocumentType' => 12,
                    'addDriverDocSeries' => '',
                    'addDriverDocNumber' => '',
                    'vin' => '',
                    'licensePlace' => '',
                    'bodyNumber' => '',
                    'chassisNumber' => '',
                    'date' => $swapData['date'],
                );
/*
            } elseif ($swapData['mode']=='bsostate') {
                $params = array(
                    'bsoseries' => mb_substr($initData['osago'],0,3),
                    'bsonumber' => trim(mb_substr($initData['osago'],3)),
                );
            } else {
                 $params = array(
                    'serialOsago' => mb_substr($initData['osago'],0,3),
                    'numberOsago' => trim(mb_substr($initData['osago'],3)),
                    'dateRequest' => $swapData['date'],
                );
*/
            }
            if (!isset($swapData['id']))
                $params['captcha'] = $swapData['captcha_token'];
            $header = array(
                    'Accept: application/json',
                    'Accept-Encoding: deflate',
                    'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
                    'Connection: keep-alive',
                    'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                    'Origin: '.$site,
                    'Referer: '.$page,
                    'X-Requested-With: XMLHttpRequest');
//            echo "{$swapData['iteration']}: $url\n";
//            var_dump($params);
//            echo "\n";
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
            if ($params) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            }
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
//            curl_setopt($ch, CURLOPT_HEADER, true);
//            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
//            echo "Cookies: ".$swapData['session']->cookies."\n";
//            curl_setopt($ch, CURLOPT_COOKIEFILE, '');
            if ($swapData['session']->proxy) {
                curl_setopt($ch,CURLOPT_PROXY,$swapData['session']->proxy);
                if ($swapData['session']->proxy_auth) {
                    curl_setopt($ch,CURLOPT_PROXYUSERPWD,$swapData['session']->proxy_auth); 
                    curl_setopt($ch,CURLOPT_PROXYAUTH,CURLAUTH_ANY); 
                }
            }
        }
//        print $swapData['mode'].': '.$url."\n";

        $rContext->setCurlHandler($ch);

        $rContext->setSwapData($swapData);
        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
//        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        $error = false; //($swapData['iteration']>0) ? curl_error($rContext->getCurlHandler()) : false;
        $content = curl_multi_getcontent($rContext->getCurlHandler());

        if (!isset($swapData['captcha_token'])) {
//            echo "$content\n\n";
            $res = json_decode($content, true);
            if (!isset($swapData['captcha_id'.$swapData['num']])) {
//                echo "Thread ".$swapData['num']."  Getting new captcha\n";
                if ($this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']=='rucaptcha.com') {
                    if (strpos($content,'OK|')!==false){
                        $swapData['captcha_id'.$swapData['num']] = substr($content,3);
                    } elseif ($swapData['iteration']>10) {
//                        $rContext->setFinished();
//                        $rContext->setError('Ошибка получения капчи');
                        file_put_contents('./logs/rsa/'.$initData['checktype'].'_captcha_err_'.time().'.txt',/*curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".*/$content."\r\n".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']);
                    }
                } else {
                    if (isset($res['taskId'])){
                        $swapData['captcha_id'.$swapData['num']] = $res['taskId'];
                    } elseif ($swapData['iteration']>10) {
//                        $rContext->setFinished();
//                        $rContext->setError('Ошибка получения капчи');
                        file_put_contents('./logs/rsa/'.$initData['checktype'].'_captcha_err_'.time().'.txt',/*curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".*/$content."\r\n".$this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']);
                    }
                }
                $swapData['iteration']--;
            } else {
                if ($this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host']=='rucaptcha.com') {
                    if ($content=='CAPCHA_NOT_READY') {
                    } else {
                        if (strpos($content,'OK|')!==false) {
                            $swapData['captcha_token'] = substr($content,3);
                            $swapData['captcha_id'] = $swapData['captcha_id'.$swapData['num']];
                            $swapData['captcha_service'] = $this->captcha_service[$swapData['captcha_service'.$swapData['num']]]['host'];
//                            echo "Thread ".$swapData['num']."  Received captcha ID ".$swapData['captcha_id'.$swapData['num']]." token ".substr($swapData['captcha_token'],0,5)."...".substr($swapData['captcha_token'],strlen($swapData['captcha_token'])-5,5)."\n";
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
//                            echo "Thread ".$swapData['num']."  Received captcha ID ".$swapData['captcha_id'.$swapData['num']]." token ".substr($swapData['captcha_token'],0,5)."...".substr($swapData['captcha_token'],strlen($swapData['captcha_token'])-5,5)."\n";
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
            if (!isset($swapData['captcha_token']) && isset($swapData['captcha_id'.$swapData['num']])) $rContext->setSleep(1);
            return true;
        }

        if (!empty(trim($content))) file_put_contents('./logs/rsa/'.$swapData['mode'].'_'.$swapData['iteration'].'_'.time().'.txt',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);

        $fullcontent = $content;
        if (strpos($content,'<html>')) {
            $res = false;
        } else {
            $start = strpos($content,'{');
            $content = trim(substr($content,$start,strlen($content)-$start+1));
            $res = json_decode($content, true);
        }

        if (isset($swapData['header_image']) && isset($swapData['body_image'])) {
            global $fields;
            if (isset($fields[$initData['checktype']])) $sfield = $fields[$initData['checktype']];
            elseif (isset($fields['RSA'])) $sfield = $fields['RSA'];
            if ($res && isset($res['data']['main_table'])) {
                $resultData = new ResultDataList();
                foreach ($res['data']['main_table'] as $row) {
                    if (isset($res['data']['policy_table'])) $row = array_merge($row,$res['data']['policy_table']);
                    $data = array();
                    foreach ($row as $key => $value) {
                        $field = isset($sfield[$key])?$sfield[$key]:array('type'=>'string','title'=>$key,'description'=>$key);
                        $data[$key] = new ResultDataField($field['type']=='bool'?'integer':$field['type']/*.($field['type']=='url'?':recursive':'')*/,$key,$value,$field['title'],$field['description']);
                    }
                    if (sizeof($data)) {
                        $data['Type'] = new ResultDataField('string','Type', 'policy', 'Тип записи', 'Тип записи');
                        $resultData->addResult($data);
                    }
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                return true;
            } elseif (!isset($swapData['retry']) && $res && isset($res['detail']['code']) && ($res['detail']['code']=422 || $res['detail']['code']=500)) {
                $swapData['retry'] = 1;
                unset($swapData['captcha_id']);
                unset($swapData['captcha_service']);
                unset($swapData['captcha_token']);
                unset($swapData['captcha_session']);
                unset($swapData['id']);
                unset($swapData['ready']);
                unset($swapData['header_image']);
                unset($swapData['body_image']);
                unset($swapData['policy_image']);
            } else {
                $error = 'Ошибка обработки ответа';
            }
        } elseif (is_array($res) && isset($res['validCaptcha']) && !$res['validCaptcha']) {
//            echo "invalid captcha\n";
            if (isset($swapData['captcha_id']) && isset($swapData['captcha_token']) && isset($swapData['captcha_service'])) {
                $mysqli->query("INSERT INTO isphere.session (used,endtime,captchatime,sourceid,sessionstatusid,statuscode,proxyid,captcha,captcha_service,captcha_id) VALUES (1,now(),now(),66,4,'invalidcaptcha',".$swapData['session']->proxyid.",'".$swapData['captcha_token']."','".$swapData['captcha_service']."','".$swapData['captcha_id']."')");
//                echo "Captcha ID ".$swapData['captcha_id']." reported as bad with result $res\n";
            }

            if (isset($swapData['captcha_session'])) {
                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=4,statuscode='invalidcaptcha' WHERE statuscode='used' AND id=" . $swapData['captcha_session']->id);
                unset($swapData['captcha_session']);
            }
            unset($swapData['captcha_id']);
            unset($swapData['captcha_service']);
            unset($swapData['captcha_token']);

            $mysqli->query("UPDATE isphere.session SET statuscode='invalidcaptcha' WHERE statuscode='used' AND id=".$swapData['session']->id);
            unset($swapData['session']);
        } elseif ($swapData['mode']=='policyInfo' && is_array($res) && !isset($swapData['id']) && isset($res['processId'])) {
            $swapData['id'] = $res['processId'];
            $swapData['iteration']--;
        } elseif ($swapData['mode']=='policyInfo' && is_array($res) && isset($swapData['id']) && isset($res['RequestStatusInfo']['RequestStatusCode'])) { 
            if ($res['RequestStatusInfo']['RequestStatusCode']==3) {
                $swapData['ready'] = true;
            } elseif ($res['RequestStatusInfo']['RequestStatusCode']==6) {
                $error = isset($res['ErrorList']['ErrorInfo'][0]['Message'])?$res['ErrorList']['ErrorInfo'][0]['Message']:'Ошибка при обработке запроса';
            } elseif ($res['RequestStatusInfo']['RequestStatusCode']==14) {
                $resultData = new ResultDataList();
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                if (isset($swapData['captcha_id']) && isset($swapData['captcha_token']) && isset($swapData['captcha_service'])) {
                    $mysqli->query("INSERT INTO isphere.session (used,endtime,captchatime,sourceid,sessionstatusid,statuscode,proxyid,captcha,captcha_service,captcha_id) VALUES (1,now(),now(),66,3,'success',".$swapData['session']->proxyid.",'".$swapData['captcha_token']."','".$swapData['captcha_service']."','".$swapData['captcha_id']."')");
//                    echo "Captcha ID ".$swapData['captcha_id']." reported as good with result $res\n";
                }

                if (isset($swapData['captcha_session'])) {
                    $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['captcha_session']->id);
                    unset($swapData['captcha_session']);
                }
                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
                return true;
            }
            $swapData['iteration']--;
        } elseif ($swapData['mode']=='policyInfo' && isset($swapData['ready'])) { 
            $resultData = new ResultDataList();
            if (preg_match("/<table class=\"bso-tbl[^>]+>(.*?)<\/table>/sim",$content,$matches)) {
                $table = trim($matches[1]);
                $parts = preg_split("/class=\"data-row\"/",$table);
                if (sizeof($parts)>1) {
                    preg_match_all("/<td[^>]*>(.*?)<\/td>/sim",$parts[0],$matches);
                    $titles = $matches[1];
//                    var_dump($titles); echo "\n\n";
                    array_shift($parts);
                    foreach ($parts as $part) {
                        $data = array();
                        if (preg_match_all("/<td[^>]*>(.*?)<\/td>/sim",trim(substr($part,0,strpos($part,'<div'))).trim(substr($part,strpos($part,'</div>')+6)),$matches)) {
                            $values = $matches[1];
//                            var_dump($values); echo "\n\n";
                            foreach($values as $i=>$value) if (isset($titles[$i])) {
                                $title = trim(preg_replace("/[\s]+/"," ",strip_tags($titles[$i])));
                                $value = trim(preg_replace("/[\s]+/"," ",strip_tags($value)));
                                if (!$value || $value=='Сведения отсутствуют') {
                                } elseif ($title=='Наименование страховой организации')
                                    $data['Company'] = new ResultDataField('string','Company', $value, 'Страховая компания', 'Страховая компания');
                                elseif ($title=='Статус полиса')
                                    $data['PolicyStatus'] = new ResultDataField('string','PolicyStatus', $value, 'Статус полиса', 'Статус полиса');
                                elseif (preg_match("/Дата изменения статуса полиса/sim",$title))
                                    $data['PolicyDate'] = new ResultDataField('string','PolicyDate', $value, 'Дата изменения статуса полиса', 'Дата изменения статуса полиса');
//                                else echo "$title: $value\r\n";
                            }
                        }
                        $data['Type'] = new ResultDataField('string','Type', 'bsostate', 'Тип записи', 'Тип записи');
                        $resultData->addResult($data);
                    }
                }
            }
            if (preg_match("/<table class=\"policies-tbl[^>]+>(.*?)<\/section>/sim",$content,$matches)) {
                $table = trim($matches[1]);
                $parts = preg_split("/class=\"data-row\"/",$table);
                if (sizeof($parts)>1) {
                    preg_match_all("/<td[^>]*>(.*?)<\/td>/sim",$parts[0],$matches);
                    $titles = $matches[1];
//                    var_dump($titles); echo "\n\n";
                    array_shift($parts);
                    foreach ($parts as $part) {
                        $data = array();
                        if (preg_match_all("/<td[^>]*>(.*?)<\/td>/sim",trim(substr($part,0,strpos($part,'<div'))).trim(substr($part,strpos($part,'</div>')+6)),$matches)) {
                            $values = $matches[1];
//                            var_dump($values); echo "\n\n";
                            foreach($values as $i=>$value) if (isset($titles[$i])) {
                                $title = trim(preg_replace("/[\s]+/"," ",strip_tags($titles[$i])));
                                $value = trim(preg_replace("/[\s]+/"," ",strip_tags($value)));
                                if (!$value || $value=='Сведения отсутствуют') {
                                } elseif ($title=='Сведения о страхователе транспортного средства') {
                                    $data['Insurant'] = new ResultDataField('string','Insurant', trim(preg_replace("/[0-9]{2}\.[0-9]{2}\.[0-9]{4}/","",$value)), 'Cтрахователь', 'Cтрахователь');
                                    if (preg_match("/([0-9]{2}\.[0-9]{2}\.[0-9]{4})/",$value,$matches))
                                        $data['InsurantBirthDate'] = new ResultDataField('string','InsurantBirthDate', $matches[1], 'Дата рождения страхователя', 'Дата рождения страхователя');
                                } elseif ($title=='Сведения о собственнике транспортного средства') {
                                    $data['Owner'] = new ResultDataField('string','Owner', trim(preg_replace("/[0-9]{2}\.[0-9]{2}\.[0-9]{4}/","",$value)), 'Cобственник', 'Cобственник');
                                    if (preg_match("/([0-9]{2}\.[0-9]{2}\.[0-9]{4})/",$value,$matches))
                                        $data['OwnerBirthDate'] = new ResultDataField('string','OwnerBirthDate', $matches[1], 'Дата рождения собственника', 'Дата рождения собственника');
                                } elseif ($title=='Серия и номер договора ОСАГО')
                                    $data['PolicyNumber'] = new ResultDataField('string','PolicyNumber', $value, 'Полис ОСАГО', 'Полис ОСАГО');
                                elseif ($title=='Наименование страховой организации')
                                    $data['Company'] = new ResultDataField('string','Company', $value, 'Страховая компания', 'Страховая компания');
                                elseif ($title=='Статус договора ОСАГО')
                                    $data['PolicyStatus'] = new ResultDataField('string','PolicyStatus', $value, 'Статус полиса', 'Статус полиса');
                                elseif (preg_match("/Цель использования/sim",$title))
                                    $data['Purpose'] = new ResultDataField('string','Purpose', $value, 'Цель использования', 'Цель использования');
                                elseif (preg_match("/допущенных к управлению/sim",$title)) {
                                    $data['Limited'] = new ResultDataField('string','Limited', trim(preg_replace("/\([^\)]*\)/","",$value)), 'Ограничения', 'Ограничения');
                                    if (preg_match("/допущено: ([0-9]+)/sim",$value,$matches))
                                        $data['Drivers'] = new ResultDataField('string','Drivers', $matches[1], 'Допущено к управлению', 'Допущено к управлению');
                                } elseif ($title=='Транспортное средство используется в регионе')
                                    $data['Region'] = new ResultDataField('string','Region', $value, 'Регион', 'Регион');
                                elseif ($title=='КБМ по договору ОСАГО')
                                    $data['Kbm'] = new ResultDataField('string','Kbm', $value, 'КБМ', 'КБМ');
                                elseif ($title=='Страховая премия')
                                    $data['Total'] = new ResultDataField('string','Total', substr($value,0,strpos($value,' ')), 'Страховая премия', 'Страховая премия');
//                                else echo "$title: $value\r\n";
                            }
                        }
                        if (preg_match_all("/<td class=\"table-td-header\">([^<]+)<\/td>[^<]*<td>([^<]+)<\/td>/sim",$part,$matches)) {
                            $titles2 = $matches[1];
                            $values2 = $matches[2];
                            foreach($values2 as $i=>$value) {
                                $title = trim(preg_replace("/[\s]+/"," ",strip_tags($titles2[$i])));
                                $value = trim(preg_replace("/[\s]+/"," ",strip_tags($value)));
                                if (!$value || $value=='Сведения отсутствуют') {
                                } elseif (preg_match("/Марка и модель/sim",$title)) {
                                    $data['Model'] = new ResultDataField('string','Model', trim(preg_replace("/\([^\)]*\)/","",$value)), 'Марка и модель', 'Марка и модель');
                                    if (preg_match("/категория «([A-Z])»/sim",$value,$matches))
                                        $data['Category'] = new ResultDataField('string','Category', $matches[1], 'Категория', 'Категория');
                                } elseif (preg_match("/Мощность двигателя/sim",$title))
                                    $data['Power'] = new ResultDataField('string','Power', $value, 'Мощность двигателя, л.с.', 'Мощность двигателя, л.с.');
                                elseif ($title=='Государственный регистрационный знак') {
                                    if (!strpos($value,'не зарегистрировано'))
                                        $data['RegNum'] = new ResultDataField('string','RegNum', $this->str_rus(preg_replace("/RUS$/","",$value)), 'Госномер', 'Госномер');
                                } elseif ($title=='VIN') {
                                    if (!strpos($value,'тсутств'))
                                        $data['VIN'] = new ResultDataField('string','VIN', strtr($value,array(' '=>'','I'=>'1','O'=>'0','Q'=>'0','А'=>'A','В'=>'B','С'=>'C','Е'=>'E','Н'=>'H','К'=>'K','М'=>'M','О'=>'0','Р'=>'P','Т'=>'T','У'=>'Y','Х'=>'X','а'=>'a','с'=>'c','е'=>'e','к'=>'k','м'=>'m','о'=>'0','р'=>'p','т'=>'t','у'=>'y','х'=>'x')), 'VIN', 'VIN');
                                } elseif ($title=='Номер кузова')
                                    $data['BodyNum'] = new ResultDataField('string','BodyNum', strtr($value,array(' '=>'','А'=>'A','В'=>'B','С'=>'C','Е'=>'E','Н'=>'H','К'=>'K','М'=>'M','О'=>'o','Р'=>'P','Т'=>'T','У'=>'Y','Х'=>'X','а'=>'a','с'=>'c','е'=>'e','к'=>'k','м'=>'m','о'=>'o','р'=>'p','т'=>'t','у'=>'y','х'=>'x')), 'Номер кузова', 'Номер кузова');
//                                else echo "$title: $value\r\n";
                            }
                        }
                        $data['Type'] = new ResultDataField('string','Type', 'policy', 'Тип записи', 'Тип записи');
                        $resultData->addResult($data);
                    }
                }
            }
            if (preg_match_all("/<img src=\"([^\"]+)\">/sim",$content,$matches)) {
                global $serviceurl;
                global $reqId;

                $get_options = array(
                    'http' => array(
                        'method' => 'GET',
                        'header' =>
                            "Cache-Control: no-cache, no-store, must-revalidate\r\n",
                    ),
                );
                if ($swapData['session']->proxy) {
                    $get_options['http']['proxy'] = $swapData['session']->proxy;
                    if ($swapData['session']->proxy_auth) {
                        $get_options['http']['header'] .= "Proxy-Authorization: Basic ".base64_encode($swapData['session']->proxy_auth)./*"\r\nAuthorization: Basic ".base64_encode($swapData['session']->proxy_auth).*/"\r\n";
                    }
                }
                $get_context = stream_context_create($get_options);

                $data = array();
                foreach($matches[1] as $src) {
                    $img = @file_get_contents('https://dkbm-web.autoins.ru/dkbm-web-1.0/'.$src,false,$get_context);
                    if (!$img) {
                        return true;
                    }
                    $imgtype = substr($src,0,strpos($src,'.'));
                    $name = 'rsa_'.(isset($initData['vin'])?$initData['vin'].'_':'').$imgtype.'_'.time().'.jpg';
//                    $file = './logs/files/'.$reqId.'_'.$name;
                    $file = './logs/rsa/'.$reqId.'_'.$name;
                    file_put_contents($file,$img);
//                    if (preg_match("/Table/",$imgtype)) {
//                        $url = $serviceurl.'getfile.php?id='.$reqId.'&name='.$name;
//                        $data[$imgtype] = new ResultDataField('image', $imgtype, $url, $imgtype, $imgtype);
//                    }
                    if ($imgtype=='policyInfoDataHeadTable')
                        $swapData['header_image'] = $file;
                    if ($imgtype=='policyInfoDataBodyTable')
                        $swapData['body_image'] = $file;
                    if ($imgtype=='policyInfoDataBsoPolicyInfo' && strlen($img)>10000)
                        $swapData['policy_image'] = $file;

                }
//                $data['Type'] = new ResultDataField('string','Type', 'images', 'Тип записи', 'Тип записи');
//                $resultData->addResult($data);
            }

            if (isset($swapData['captcha_id']) && isset($swapData['captcha_token']) && isset($swapData['captcha_service'])) {
                $mysqli->query("INSERT INTO isphere.session (used,endtime,captchatime,sourceid,sessionstatusid,statuscode,proxyid,captcha,captcha_service,captcha_id) VALUES (1,now(),now(),66,3,'success',".$swapData['session']->proxyid.",'".$swapData['captcha_token']."','".$swapData['captcha_service']."','".$swapData['captcha_id']."')");
//                echo "Captcha ID ".$swapData['captcha_id']." reported as good with result $res\n";
            }

            if (isset($swapData['captcha_session'])) {
                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['captcha_session']->id);
                unset($swapData['captcha_session']);
            }
            $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);

            if (!isset($swapData['header_image']) && !isset($swapData['body_image'])) {
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                return true;
            }
        } elseif (($swapData['mode']=='kbm') && is_array($res)) { 
            if (isset($swapData['list']))
                $resultData = $swapData['list'];
            else
                $resultData = new ResultDataList();
            $data = array();
            if (array_key_exists('policySerialKey',$res) && array_key_exists('policyNumberKey',$res) && $res['policySerialKey'] && $res['policyNumberKey']) {
                $data['PolicyNumber'] = new ResultDataField('string','PolicyNumber', $res['policySerialKey'].' '.$res['policyNumberKey'], 'Полис ОСАГО', 'Полис ОСАГО');
            }
            if (array_key_exists('policyDateBeg',$res) && $res['policyDateBeg'])
                $data['PolicyStartDate'] = new ResultDataField('string','PolicyStartDate', $res['policyDateBeg'], 'Дата начала', 'Дата начала полиса ОСАГО');
            if (array_key_exists('policyDateEnd',$res) && $res['policyDateEnd'])
                $data['PolicyEndDate'] = new ResultDataField('string','PolicyEndDate', $res['policyDateEnd'], 'Дата окончания', 'Дата окончания полиса ОСАГО');
            if (array_key_exists('insurerName',$res) && $res['insurerName'])
                $data['Company'] = new ResultDataField('string','Company', $res['insurerName'], 'Страховая компания', 'Страховая компания');
            if (array_key_exists('policyKbm',$res) && $res['policyKbm'])
                $data['Class'] = new ResultDataField('string','Class', $res['policyKbm'], 'Класс', 'Класс в период действия полиса');
            if (array_key_exists('policyKbmValue',$res) && $res['policyKbmValue'])
                $data['Kbm'] = new ResultDataField('string','Kbm', $res['policyKbmValue'], 'КБМ', 'КБМ в период действия полиса');
            if (array_key_exists('kbmValue',$res) && $res['kbmValue'])
                $data['Kbm'] = new ResultDataField('string','Kbm', $res['kbmValue'], 'КБМ', 'КБМ');
            if (array_key_exists('lossCRTTypeList',$res) && $res['lossCRTTypeList']) {
                $data['LossCount'] = new ResultDataField('string','LossCount', sizeof($res['lossCRTTypeList']), 'Страховых случаев', 'Страховых случаев');
                if (is_array($res['lossCRTTypeList'])) {
                    foreach($res['lossCRTTypeList'] as $rec) {
                        if (array_key_exists('lossDateTime',$rec) && $rec['lossDateTime'])
                            $lossdata['LossDate'] = new ResultDataField('string','LossDate', date('d.m.Y',$rec['lossDateTime']/1000), 'Дата страхового случая', 'Дата страхового случая');
                        if (array_key_exists('policySerialKey',$rec) && array_key_exists('policyNumberKey',$rec) && $rec['policySerialKey'] && $rec['policyNumberKey']) {
                            $lossdata['LostPolicyNumber'] = new ResultDataField('string','PolicyNumber', $rec['policySerialKey'].' '.$rec['policyNumberKey'], 'Полис ОСАГО', 'Полис ОСАГО');
                        }
                        if (array_key_exists('insurerName',$rec) && $rec['insurerName'])
                            $lossdata['Company'] = new ResultDataField('string','Company', $rec['insurerName'], 'Страховая компания', 'Страховая компания');
                        $lossdata['Type'] = new ResultDataField('string','Type', 'loss', 'Тип записи', 'Тип записи');
                        $resultData->addResult($lossdata);
                    }
                }
            }
            if (sizeof($data)) {
                $data['Type'] = new ResultDataField('string','Type', 'kbm', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);
            }
/*
            if (isset($res['policyDateEnd'])) {
                $swapData['list'] = $resultData;
                $swapData['date'] = $res['policyDateEnd'];
                $swapData['num'] = 1;
                $swapData['iteration'] = 0;
                $rContext->setSwapData($swapData);
            } else {
*/
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                if (isset($swapData['captcha_id']) && isset($swapData['captcha_token']) && isset($swapData['captcha_service'])) {
                    $mysqli->query("INSERT INTO isphere.session (used,endtime,captchatime,sourceid,sessionstatusid,statuscode,proxyid,captcha,captcha_service,captcha_id) VALUES (1,now(),now(),66,3,'success',".$swapData['session']->proxyid.",'".$swapData['captcha_token']."','".$swapData['captcha_service']."','".$swapData['captcha_id']."')");
//                    echo "Captcha ID ".$swapData['captcha_id']." reported as good with result $res\n";
                }

                if (isset($swapData['captcha_session'])) {
                    $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['captcha_session']->id);
                    unset($swapData['captcha_session']);
                }
                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
                return true;
//            }
/*
        } elseif (($swapData['mode']=='policy') && is_array($res) && array_key_exists('policyResponseUIItems',$res) && is_array($res['policyResponseUIItems'])) { 
            $resultData = new ResultDataList();
            foreach($res['policyResponseUIItems'] as $rec) {
                $data = array();
                if (array_key_exists('policyBsoSerial',$rec) && array_key_exists('policyBsoNumber',$rec)) {
                    $data['PolicyNumber'] = new ResultDataField('string','PolicyNumber', $rec['policyBsoSerial'].' '.$rec['policyBsoNumber'], 'Полис ОСАГО', 'Полис ОСАГО');
                }
                if (array_key_exists('insCompanyName',$rec))
                    $data['Company'] = new ResultDataField('string','Company', $rec['insCompanyName'], 'Страховая компания', 'Страховая компания');
                if (array_key_exists('policyIsRestrict',$rec))
                    $data['Restrict'] = new ResultDataField('string','Restrict', $rec['policyIsRestrict'], 'Ограничения', 'Ограничения по водителям');
                $resultData->addResult($data);
            }
            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return true;
        } elseif (($swapData['mode']=='bsostate') && is_array($res) && array_key_exists('bsoStatusName',$res)) { 
            $data = array();
            if (array_key_exists('policyStatus',$res))
                $data['PolicyPlace'] = new ResultDataField('string','PolicyPlace', $res['policyPlace'], 'Местонахождение полиса', 'Местонахождение полиса');
            if (array_key_exists('policyCreateDate',$res))
                $data['CreateDate'] = new ResultDataField('string','CreateDate', $res['policyCreateDate'], 'Дата договора', 'Дата заключения договора');
            if (array_key_exists('policyBeginDate',$res))
                $data['BeginDate'] = new ResultDataField('string','BeginDate', $res['policyBeginDate'], 'Дата начала', 'Дата начала');
            if (array_key_exists('policyEndDate',$res))
                $data['EndDate'] = new ResultDataField('string','EndDate', $res['policyEndDate'], 'Дата окончания', 'Дата окончания');
            $resultData = new ResultDataList();
            $resultData->addResult($data);
            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return true;
        } elseif (($swapData['mode']=='osagovehicle') && is_array($res) && array_key_exists('policyStatus',$res)) { 
            $data = array();
            if (array_key_exists('licensePlate',$res) && $res['licensePlate'])
                $data['RegNum'] = new ResultDataField('string','RegNum', $this->str_rus($res['licensePlate']), 'Госномер', 'Госномер');
            if (array_key_exists('vin',$res))
                $data['VIN'] = new ResultDataField('string','VIN', strtr($res['vin'],array(' '=>'','I'=>'1','O'=>'0','Q'=>'0','А'=>'A','В'=>'B','С'=>'C','Е'=>'E','Н'=>'H','К'=>'K','М'=>'M','О'=>'0','Р'=>'P','Т'=>'T','У'=>'Y','Х'=>'X','а'=>'a','с'=>'c','е'=>'e','к'=>'k','м'=>'m','о'=>'0','р'=>'p','т'=>'t','у'=>'y','х'=>'x')), 'VIN', 'VIN');
            if (array_key_exists('bodyNumber',$res))
                $data['BodyNum'] = new ResultDataField('string','BodyNum', strtr($res['bodyNumber'],array(' '=>'','А'=>'A','В'=>'B','С'=>'C','Е'=>'E','Н'=>'H','К'=>'K','М'=>'M','О'=>'o','Р'=>'P','Т'=>'T','У'=>'Y','Х'=>'X','а'=>'a','с'=>'c','е'=>'e','к'=>'k','м'=>'m','о'=>'o','р'=>'p','т'=>'t','у'=>'y','х'=>'x')), 'Номер кузова', 'Номер кузова');
            if (array_key_exists('policyStatus',$res))
                $data['PolicyStatus'] = new ResultDataField('string','PolicyStatus', $res['policyStatus'], 'Статус полиса', 'Статус полиса');
            $resultData = new ResultDataList();
            $resultData->addResult($data);
            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return true;
*/
        } elseif (($swapData['mode']=='policy') && is_array($res) && isset($res['errorMessage'])) {
            if (strpos($res['errorMessage'],'не найдены')>0) {
                $resultData = new ResultDataList();
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                if (isset($swapData['captcha_id']) && isset($swapData['captcha_token']) && isset($swapData['captcha_service'])) {
                    $mysqli->query("INSERT INTO isphere.session (used,endtime,captchatime,sourceid,sessionstatusid,statuscode,proxyid,captcha,captcha_service,captcha_id) VALUES (1,now(),now(),66,3,'success',".$swapData['session']->proxyid.",'".$swapData['captcha_token']."','".$swapData['captcha_service']."','".$swapData['captcha_id']."')");
//                    echo "Captcha ID ".$swapData['captcha_id']." reported as good with result $res\n";
                }

                if (isset($swapData['captcha_session'])) {
                    $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['captcha_session']->id);
                    unset($swapData['captcha_session']);
                }
                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE statuscode='used' AND id=".$swapData['session']->id);
                return true;
            } else {
                $error = $res['errorMessage'];
            }
        } elseif (strpos($content,'Temporarily Unavailable')) {
//            echo "unavailable\n";
            if ($content) file_put_contents('./logs/rsa/'.$swapData['mode'].'_'.$swapData['iteration'].'_err_'.time().'.txt',$content);
            if ($swapData['iteration']>=100) $error = 'Внутренняя ошибка источника';
/*
            if (isset($swapData['captcha_id']) && isset($swapData['captcha_token']) && isset($swapData['captcha_service'])) {
                $mysqli->query("INSERT INTO isphere.session (used,endtime,captchatime,sourceid,sessionstatusid,statuscode,proxyid,captcha,captcha_service,captcha_id) VALUES (1,now(),now(),66,4,'invalidcaptcha',".$swapData['session']->proxyid.",'".$swapData['captcha_token']."','".$swapData['captcha_service']."','".$swapData['captcha_id']."')");
//                echo "Captcha ID ".$swapData['captcha_id']." reported as bad with result $res\n";
            }

            if (isset($swapData['captcha_session'])) {
                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=4,statuscode='invalidcaptcha' WHERE statuscode='used' AND id=" . $swapData['captcha_session']->id);
                unset($swapData['captcha_session']);
            }
            unset($swapData['captcha_id']);
            unset($swapData['captcha_service']);
            unset($swapData['captcha_token']);
            $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='unavailable' WHERE statuscode='used' AND id=".$swapData['session']->id);
*/
            $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 30 second),sessionstatusid=6,statuscode='unavailable' WHERE statuscode='used' AND id=".$swapData['session']->id);
            unset($swapData['session']);
            $swapData['iteration']--;
        } elseif (strpos($content,'Bad Gateway')) {
            if ($content) file_put_contents('./logs/rsa/'.$swapData['mode'].'_'.$swapData['iteration'].'_err_'.time().'.txt',$content);
            if ($swapData['iteration']>=5) $error = 'Внутренняя ошибка источника';
            $mysqli->query("UPDATE isphere.session SET endtime=null,sessionstatusid=2,statuscode='error' WHERE statuscode='used' AND id=".$swapData['session']->id);
            unset($swapData['session']);
            if (isset($swapData['captcha_session'])) {
                $mysqli->query("UPDATE isphere.session SET endtime=null,sessionstatusid=2,statuscode='' WHERE statuscode='used' AND id=".$swapData['captcha_session']->id);
                unset($swapData['captcha_session']);
            }
        } else {
            if ($content) file_put_contents('./logs/rsa/'.$swapData['mode'].'_'.$swapData['iteration'].'_err_'.time().'.txt',$content);
            if ($content) {
                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='invalidanswer' WHERE statuscode='used' AND id=".$swapData['session']->id);
                $error = "Некорректный ответ сервиса";
            } else {
                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='empty' WHERE statuscode='used' AND id=".$swapData['session']->id);
            }
            if (isset($swapData['captcha_session'])) {
                $mysqli->query("UPDATE isphere.session SET endtime=null,sessionstatusid=2,statuscode='' WHERE statuscode='used' AND id=".$swapData['captcha_session']->id);
                unset($swapData['captcha_session']);
            }
            unset($swapData['session']);
        }
        $rContext->setSwapData($swapData);

        if ($error || $swapData['iteration']>=30) {
            $rContext->setFinished();
            $rContext->setError($error?$error:'Превышено количество попыток получения ответа');
        }

        $rContext->setSleep(1);
        return false;
    }
}

?>
<?php

class FSSPPlugin implements PluginInterface
{
    private $timeout = 30;
    private $tlsproxy = false;
//    private $tlsproxy = '172.16.1.254:8009';
//    private $tlsproxy = '172.16.1.1:8089';
//    private $tlsproxy = 'tlsproxy.default';

    private $captcha_service = array(
//        array('host' => 'api.capmonster.cloud', 'key' => 'afb26cbb248d650ea8b8d88822984242'),
        array('host' => 'rucaptcha.com', 'key' => 'd167c71a9278312f184f17caa4e71050'),
        array('host' => 'api.anti-captcha.com', 'key' => '63def0a149a147e0d13e409cc8318fc3'),
    );
    private $captcha_threads = 1;
    private $captcha_lifetime = 14;

    public function getName($checktype = '')
    {
        $name = array(
            '' => 'fssp',
            'fssp_person' => 'fssp',
            'fssp_suspect' => 'fssp_suspect',
            'fssp_org' => 'fssp',
            'fssp_inn' => 'fssp',
            'fssp_ip' => 'fssp',
        );
        return isset($name[$checktype])?$name[$checktype]:$name[''];
//        return 'fssp';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'ФССП - поиск исполнительных производств',
            'fssp_person' => 'ФССП - исполнительные производства (сайт)',
            'fssp_suspect' => 'ФССП - розыск подозреваемых в совершении преступлений',
            'fssp_org' => 'ФССП - исполнительные производства по организации',
            'fssp_inn' => 'ФССП - исполнительные производства по ИНН организации',
            'fssp_ip' => 'ФССП - информация об исполнительном производстве',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'ФССП РФ - поиск исполнительных производств';
    }

    public function getSessionData()
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

//        if (rand(0,9)==0)
//            $mysqli->query("DELETE FROM isphere.session WHERE sourceid=3 AND cookies='' AND sessionstatusid IN (2,6) ORDER BY lasttime limit 1");

//        $mysqli->query("DELETE FROM isphere.session WHERE sessionstatusid=2 AND sourceid=3 AND cookies='' ORDER BY lasttime limit 1");
        $mysqli->query("UPDATE isphere.session s SET request_id=".$reqId." WHERE sourceid=3 AND request_id IS NULL AND sessionstatusid=2 AND lasttime<DATE_SUB(now(), INTERVAL 10 SECOND) ORDER BY lasttime limit 1");
//        $mysqli->query("UPDATE isphere.session s SET request_id=".$reqId." WHERE sourceid=3 AND request_id IS NULL AND sessionstatusid=2 AND (statuscode<>'used' OR lasttime<from_unixtime(unix_timestamp(now())-300)) AND lasttime<DATE_SUB(now(), INTERVAL 10 SECOND) ORDER BY lasttime limit 1");
//        $mysqli->query("UPDATE isphere.session s SET endtime=now(),sessionstatusid=5,statuscode='expired' WHERE sourceid=3 AND request_id IS NULL AND sessionstatusid IN (1,2) AND unix_timestamp(now())-unix_timestamp(starttime)>=15");
//        $mysqli->query("UPDATE isphere.session s SET request_id=".$reqId." WHERE sourceid=3 AND request_id IS NULL AND sessionstatusid=2 AND unix_timestamp(now())-unix_timestamp(starttime)<15 ORDER BY starttime limit 1");
        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sourceid=3 AND request_id=".$reqId." ORDER BY lasttime limit 1");

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

                $mysqli->query("UPDATE isphere.session SET statuscode='used',lasttime=now(),used=ifnull(used,0)+1,captcha='',request_id=NULL WHERE id=".$sessionData->id);
//                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='used',lasttime=now(),used=ifnull(used,0)+1,request_id=NULL WHERE id=".$sessionData->id);
//                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='exhausted' WHERE used>=4 AND id=".$sessionData->id);

                $i = 0;
                $r = rand(0,2)==0;
                while (!$sessionData->proxyid && (++$i<=2)) {
                    if (/*$r xor */$i%2) { 
//                    if (0) { 
                        $proxygroup = 1;
                        $multiple = false;
                        $lastlocked = 300;
                    } else {
                        $proxygroup = 5;
                        $multiple = true;
                        $lastlocked = 5;
                    }
                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth, (SELECT count(*) FROM session s WHERE sourceid=3 AND proxyid=proxy.id) scnt FROM isphere.proxy WHERE enabled=1 AND status=1 AND proxygroup IN ($proxygroup) AND id NOT IN (SELECT proxyid FROM session s WHERE sourceid=3 AND proxyid IS NOT NULL AND ((sessionstatusid=6 AND lasttime>from_unixtime(unix_timestamp(now())-$lastlocked))))".($multiple?" HAVING scnt<30":" HAVING scnt=0")." ORDER BY scnt, lasttime limit 1");
//                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE enabled=1 AND status=1 AND country='ru' AND (rotation>0 OR id NOT IN (SELECT proxyid FROM session WHERE sourceid=3 AND proxyid IS NOT NULL AND sessionstatusid IN (1,2,6))) ORDER BY lasttime limit 1");
//                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE enabled=1 AND status=1 AND country='ru' ORDER BY lasttime limit 1");
                    if ($result) {
                        $row = $result->fetch_object();
                        if ($row) {
                            $sessionData->proxyid = $row->proxyid;
                            $sessionData->proxy = $row->proxy;
                            $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;

//                            $mysqli->query("UPDATE isphere.proxy SET lasttime=now() WHERE id=".$row->proxyid);
                            $mysqli->query("UPDATE isphere.session SET used=1,success=0,proxyid=".$row->proxyid." WHERE id=".$sessionData->id);
                        }
                    }
                }
                if (!$sessionData->proxyid) {
                    $mysqli->query("UPDATE isphere.session SET statuscode='needmoreproxy' WHERE id=".$sessionData->id);
                    $sessionData = null;
                }
            }
        }

        return $sessionData;
    }


    public function prepareRequest(&$rContext)
    {
        global $clientId;
        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],5);

        if(($checktype=='person' || $checktype=='suspect') && (!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date'])))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (ФИО, дата рождения)');

            return false;
        }
/*
        if(($checktype=='person') && !isset($initData['region_id']))
        {
            $rContext->setFinished();
            $rContext->setError('ФССП теперь требует указывать в запросе регион');

            return false;
        }
*/
        if((isset($initData['last_name']) && isset($initData['patronymic']) && $initData['last_name']==$initData['patronymic']) ||
          (isset($initData['first_name']) && isset($initData['patronymic']) && $initData['first_name']==$initData['patronymic'])) {
            $initData['patronymic'] = '';
        }

        if(isset($initData['last_name']) && isset($initData['first_name']) && $initData['last_name']==$initData['first_name']) {
            $rContext->setFinished();
            $rContext->setError('ФССП не может обработать запрос с совпадением полей в ФИО');

            return false;
        }

        if(($checktype=='org') && (!isset($initData['name']) || !isset($initData['address']) || $initData['address']==''))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (название и адрес организации)');

            return false;
        }

        if(($checktype=='inn') && !isset($initData['inn']))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (ИНН организации)');

            return false;
        }

        if($checktype=='inn' && isset($initData['inn']) && !preg_match("/^\d{10}$/",$initData['inn']))
        {
            $rContext->setFinished();
            $rContext->setError('ИНН юрлица должен содержать 10 цифр');

            return false;
        }

        if(($checktype=='ip') && !isset($initData['fssp_ip']))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (номер ИП)');

            return false;
        }

        if(isset($initData['last_name']) && isset($initData['first_name']) && preg_match("/[^А-Яа-яЁё\s\-\.]/ui", $initData['last_name'].' '.$initData['first_name'].(isset($initData['patronymic'])?' '.$initData['patronymic']:''))){
            $rContext->setFinished();
            $rContext->setError('Имя может содержать только русские буквы');
            return false;
        }

//        if ($clientId==6 || $clientId==32 || $clientId==8 || $clientId==82 || $clientId==290 || $clientId==15 || $clientId==1 || $clientId==157 || $clientId==11) {
//            $rContext->setError('Сервис временно недоступен');
//            $rContext->setFinished();
//            return false;
//        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        if(!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData();
            $swapData['callback'] = 'jQuery34005992004184089165_'.(round(microtime(true)*1000)-10);
            unset($swapData['captcha_image']);
            unset($swapData['captcha_id']);
            unset($swapData['captcha_time']);
            unset($swapData['captcha_value']);
            if ($swapData['session'] && ($swapData['iteration']>10) && rand(0,1)==0) {
                $astro = array('213.108.196.179:10687');
//                $swapData['session']->proxyid = 2;
//                $swapData['session']->proxy = $astro[rand(0,sizeof($astro)-1)];
//                $swapData['session']->proxy_auth = 'isphere:e6eac1'; 
            }
        }
        $swapData['_'] = round(microtime(true)*1000);

        if (isset($swapData['captcha_time']) && (microtime(true)-$swapData['captcha_time'])>$this->captcha_lifetime) {
            unset($swapData['captcha_image']);
            unset($swapData['captcha_id']);
//            unset($swapData['captcha_time']);
        }
        $rContext->setSwapData($swapData);

        if(!$swapData['session'])
        {
            if($swapData['iteration']>60) {
                $rContext->setError('Сервис временно недоступен');
                $rContext->setFinished();
                return false;
            }
            $rContext->setSleep(1);
            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();
        $hosts = array(
            'suspect' => 'is.fssp.gov.ru',
            'person' => 'is.fssp.gov.ru',
//            'person' => 'is-node6.fssp.gov.ru',
//            'person' => 'is-node7.fssp.gov.ru',
            'org' => 'is-node2.fssp.gov.ru',
            'inn' => 'is-node5.fssp.gov.ru',
            'ip' => 'is-node3.fssp.gov.ru',
        );
//        if (intval($swapData['iteration']/4)%2) $hosts[$checktype] = 'is.fssp.gov.ru';
        $url = 'https://'.$hosts[$checktype];

        if (/*isset($swapData['captcha_time']) && */!isset($swapData['captcha_image'])) {
            $params = array(
                'callback' => $swapData['callback'],
                '_' => $swapData['_']++,
            );
            $url .= '/refresh_visual_captcha/?'.http_build_query($params);
            $header = array(
                'Accept: */*',
                'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
                'Accept-Encoding: gzip, deflate, br',
                'DNT: 1',
                'Connection: keep-alive',
                'Referer: https://fssp.gov.ru/',
                'Cookie: '.$swapData['session']->cookies,
                'Sec-Fetch-Dest: script',
                'Sec-Fetch-Mode: no-cors',
                'Sec-Fetch-Site: same-site',
            );

            if ($this->tlsproxy) {
                $params = array(
                    'Url' => $url,
                    'Method' => 'GET',
                    'Timeout' => 5,
                    'Cookies' => array(),
                    'Headers' => $header,
//                    'UserAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36',
//                    'Ja3' => '771,4865-4866-4867-49195-49199-49196-49200-52393-52392-49171-49172-156-157-47-53,0-23-65281-10-11-35-16-5-13-18-51-45-43-27-21,29-23-24,0',
                    'UserAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/114.0',
                    'Ja3' => '772,4865-4867-4866-49195-49199-52393-52392-49196-49200-49162-49161-49171-49172-156-157-47-53,0-23-65281-10-11-35-16-5-34-51-43-13-45-28,29-23-24-25-256-257,0',
                    'Proxy' => 'http://'.$swapData['session']->proxy_auth.'@'.$swapData['session']->proxy,
                );
                $cookies = str_cookies($swapData['session']->cookies);
                foreach ($cookies as $name => $value) $params['Cookies'][] = array('Name'=>$name, 'Value'=>$value);

//                echo date('H:i:s')." ".$swapData['iteration']." ".$swapData['session']->id.": $url\n";
//                var_dump($params); echo "\n\n";

                $url = 'http://'.$this->tlsproxy.'/handle';
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_TIMEOUT, $params['Timeout']+1);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params,JSON_UNESCAPED_UNICODE));
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_ENCODING, '');
//                curl_setopt($ch, CURLOPT_REFERER, 'https://fssp.gov.ru/');
                curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
                curl_setopt($ch, CURLOPT_COOKIEFILE, '');
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
//                curl_setopt($ch, CURLOPT_HEADER, true);
//                curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                if ($swapData['session']->proxy) {
                    curl_setopt($ch,CURLOPT_PROXY,$swapData['session']->proxy);
                    if ($swapData['session']->proxy_auth) {
                        curl_setopt($ch,CURLOPT_PROXYUSERPWD,$swapData['session']->proxy_auth); 
                        curl_setopt($ch,CURLOPT_PROXYAUTH,CURLAUTH_ANY); 
                    }
                }

                echo date('H:i:s')." ".$swapData['iteration']." ".$swapData['session']->id.": $url\n";
//                var_dump($params); echo "\n\n";
            }
        } elseif (isset($swapData['captcha_image']) && !isset($swapData['captcha_value'])) {
            if (!isset($swapData['captcha_id'])) {
                $swapData['captcha_service'] = intval(($swapData['iteration']-1)/2)%sizeof($this->captcha_service);
                $rContext->setSwapData($swapData);
//                echo date('H:i:s')." ".$swapData['iteration'].": Sending captcha to ".$this->captcha_service[$swapData['captcha_service']]['host']."\n";
                if ($this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com') {
                    $params = array(
                        'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                        'method' => 'base64',
                        'body' => $swapData['captcha_image'],
//                        'regsense' => 1,
                        'min_len' => 4,
                        'max_len' => 6,
                        'language' => 1,
                        'lang' => 'ru',
                    );      
                    $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/in.php";
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                } else {
                    $params = array(
                        "clientKey" => $this->captcha_service[$swapData['captcha_service']]['key'],
                        "task" => array(
                            "type" => "ImageToTextTask",
                            "body" => $swapData['captcha_image'],
//                            "case" => true,
                            "minLength" => 4,
                            "maxLength" => 6,
                        ),
                        "languagePool" => "ru",
                    );
                    $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/createTask";
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params,JSON_UNESCAPED_UNICODE));
                }
            } else {
//                echo date('H:i:s')." ".$swapData['iteration'].": Getting captcha value from ".$this->captcha_service[$swapData['captcha_service']]['host']."\n";
                if ($this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com') {
                    $params = array(
                        'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                        'action' => 'get',
                        'id' => $swapData['captcha_id'],
                    );      
                    $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/res.php?".http_build_query($params);
                } else {
                    $params = array(
                        "clientKey" => $this->captcha_service[$swapData['captcha_service']]['key'],
                        "taskId" => $swapData['captcha_id'],
                    );
                    $url = "https://".$this->captcha_service[$swapData['captcha_service']]['host']."/getTaskResult";
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params,JSON_UNESCAPED_UNICODE));
                }
            }
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_PROXY, false);
//            echo date('H:i:s')." ".$swapData['iteration'].": $url\n";
//            var_dump($params);
//            echo "\n";
        } else {
            $url .= '/ajax_search';

            if ($checktype=='suspect') {
                $params = array(
                    'is[extended]' => 1,
                    'system' => 'suspect_info',
                    'is[all]' => '',
                    'is[suspect_fio]' => $initData['last_name'].' '.$initData['first_name'].(isset($initData['patronymic'])?' '.$initData['patronymic']:''),
                    'is[suspect_birth_date]' => date('d.m.Y',strtotime($initData['date'])),
                    'is[suspect_birth_place]' => '',
                    'is[init_agency_name]' => '',
                    'is[init_agency_contact]' => '',
                    'is[search_agency_name]' => '',
                    'is[code_clause]' => '',
                    'is[or]' => 0,
                );
//                if (isset($initData['region_id']))
//                    $params['is[region_id][]'] = $initData['region_id'];
            } else {
                $params = array(
                    'callback' => $swapData['callback'],
                    'system' => 'ip',
                    'is[extended]' => 1,
                    'nocache' => 1,
                    'is[variant]' => 0,
                    'is[region_id][0]' => /*isset($initData['region_id'])?$initData['region_id']:*/'-1',
                    'is[last_name]' => '',
                    'is[first_name]' => '',
                    'is[drtr_name]' => '',
                    'is[ip_number]' => '',
                    'is[patronymic]' => '',
                    'is[date]' => '',
                    'is[address]' => '',
                    'is[id_number]' => '',
                    'is[id_type][0]' => '',
                    'is[id_issuer]' => '',
                    'is[inn]' => '',
                );
                if ($checktype=='ip') {
                    $params['is[variant]']='3';
                    $params['is[ip_number]']=$initData['fssp_ip'];
                } elseif ($checktype=='org') {
                    $params['is[variant]']='2';
                    $params['is[drtr_name]']=$initData['name'];
                    $params['is[address]']=isset($initData['address'])?$initData['address']:'';
                } elseif ($checktype=='inn') {
                    $params['is[variant]']='5';
                    $params['is[inn]']=$initData['inn'];
                } else {
                    $params['is[variant]']='1';
                    $params['is[last_name]']=$initData['last_name']?$initData['last_name']:'-';
                    $params['is[first_name]']=$initData['first_name'];
                    $params['is[patronymic]']=isset($initData['patronymic'])?$initData['patronymic']:'';
                    $params['is[date]']=isset($initData['date'])?date('d.m.Y',strtotime($initData['date'])):'';
                }
            }
            if (isset($swapData['captcha_value'])) {
                unset($params['callback']);
                $params['callback'] = $swapData['callback'];
                $params['code'] = strtr($swapData['captcha_value'],array('-'=>''));
            }
            $params['_'] = $swapData['_']++;
            if (isset($swapData['page']))
                $params['page'] = $swapData['page'];

            $rContext->setSwapData($swapData);
            $url .= '?'.http_build_query($params);
/*
	for( $j=1; $j <= 4; $j++){
	          if(file_exists('./logs/cookies/fssp_'.$j.'.txt') && ( ( time() - filemtime('./logs/cookies/fssp_'.$j.'.txt')) > 30 )){
		            break;
		  }
	}
        if( $j > 3 ){
                  file_put_contents('./logs/cookies/fssp_error.txt', '4', FILE_APPEND);
                  return true;
        }
        else{
                 $swapData['cuka'] = 'fssp_'.$j.'.txt';
        }
        $rContext->setSwapData($swapData);
*/
            $header = array(
                'Accept: */*',
                'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
                'DNT: 1',
                'Connection: keep-alive',
                'Referer: https://fssp.gov.ru/',
                'Sec-Fetch-Dest: script',
                'Sec-Fetch-Mode: no-cors',
                'Sec-Fetch-Site: same-site',
            );

            if ($this->tlsproxy) {
                $params = array(
                    'Url' => $url,
                    'Method' => 'GET',
                    'Timeout' => $this->timeout,
                    'Cookies' => array(),
                    'Headers' => $header,
//                    'UserAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36',
//                    'Ja3' => '771,4865-4866-4867-49195-49199-49196-49200-52393-52392-49171-49172-156-157-47-53,0-23-65281-10-11-35-16-5-13-18-51-45-43-27-21,29-23-24,0',
                    'UserAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/114.0',
                    'Ja3' => '772,4865-4867-4866-49195-49199-52393-52392-49196-49200-49162-49161-49171-49172-156-157-47-53,0-23-65281-10-11-35-16-5-34-51-43-13-45-28,29-23-24-25-256-257,0',
                    'Proxy' => 'http://'.$swapData['session']->proxy_auth.'@'.$swapData['session']->proxy,
                );
                $cookies = str_cookies($swapData['session']->cookies);
                foreach ($cookies as $name => $value) $params['Cookies'][] = array('Name'=>$name, 'Value'=>$value);

//                echo date('H:i:s')." ".$swapData['iteration']." ".$swapData['session']->id.": $url\n";
//                var_dump($params); echo "\n\n";

                $url = 'http://'.$this->tlsproxy.'/handle';
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_TIMEOUT, $params['Timeout']+1);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params,JSON_UNESCAPED_UNICODE));
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
                curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
                curl_setopt($ch, CURLOPT_ENCODING, '');
//                curl_setopt($ch, CURLOPT_REFERER, 'https://fssp.gov.ru/');
                curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
                curl_setopt($ch, CURLOPT_COOKIEFILE, '');
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
//                curl_setopt($ch, CURLOPT_HEADER, true);
//                curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                if ($swapData['session']->proxy) {
                    curl_setopt($ch,CURLOPT_PROXY,$swapData['session']->proxy);
                    if ($swapData['session']->proxy_auth) {
                        curl_setopt($ch,CURLOPT_PROXYUSERPWD,$swapData['session']->proxy_auth); 
                        curl_setopt($ch,CURLOPT_PROXYAUTH,CURLAUTH_ANY); 
                    }
                }

                echo date('H:i:s')." ".$swapData['iteration']." ".$swapData['session']->id.": $url\n";
//                var_dump($params); echo "\n\n";
            }
        }

        $rContext->setCurlHandler($ch);
        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;
        global $reqId;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

//        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
//        $rContext->setSwapData($swapData);

        $raw_content = curl_multi_getcontent($rContext->getCurlHandler());
        $full_content = $raw_content;
        if ($this->tlsproxy)
            $res = json_decode($raw_content, true);

        if (isset($swapData['captcha_image']) && !isset($swapData['captcha_value'])) {
            $content = $raw_content;
//            echo "$content\n";
            $res = json_decode($content, true);
            if (!isset($swapData['captcha_id'])) {
//                echo "Thread "."  Getting new captcha\n";
                if ($this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com') {
                    if (strpos($content,'OK|')!==false){
                        $swapData['captcha_id'] = substr($content,3);
                    } elseif ($swapData['iteration']>20) {
//                        $rContext->setFinished();
//                        $rContext->setError('Ошибка получения капчи');
                        file_put_contents('./logs/fssp/'.$initData['checktype'].'_captcha_err_'.$swapData['iteration'].'_'.time().'.txt',/**/$content."\r\n".$this->captcha_service[$swapData['captcha_service']]['host']);
                    }
                } else {
                    if (isset($res['taskId'])){
                        $swapData['captcha_id'] = $res['taskId'];
                    } elseif ($swapData['iteration']>20) {
//                        $rContext->setFinished();
//                        $rContext->setError('Ошибка получения капчи');
                        file_put_contents('./logs/fssp/'.$initData['checktype'].'_captcha_err_'.$swapData['iteration'].'_'.time().'.txt',/**/$content."\r\n".$this->captcha_service[$swapData['captcha_service']]['host']);
                    }
                }
                if (isset($swapData['captcha_id'])) $rContext->setSleep(5);
            } else {
                if ($this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com') {
                    if ($content=='CAPCHA_NOT_READY') {
                        $rContext->setSleep(3);
                    } else {
                        if (strpos($content,'OK|')!==false) {
                            $swapData['captcha_value'] = substr($content,3);
//                            echo "Thread "."  Received captcha ID ".$swapData['captcha_id']."\n";
                        } else {
                            unset($swapData['captcha_id']);
                            unset($swapData['captcha_time']);
                            unset($swapData['captcha_image']);
//                        } elseif ($swapData['iteration']>30) {
//                            $rContext->setFinished();
//                            $rContext->setError('Ошибка распознавания капчи');
                        }
//                        unset($swapData['captcha_id']);
                    }
                } else {
                    if (!$content) {
                    } elseif (isset($res['status']) && $res['status']!=='ready') {
                        $rContext->setSleep(1);
                    } else {
                        if (isset($res['solution']['text'])) {
                            $swapData['captcha_value'] = $res['solution']['text'];
//                            echo "Thread "."  Received captcha ID ".$swapData['captcha_id']."\n";
                        } else {
                            unset($swapData['captcha_id']);
                            unset($swapData['captcha_time']);
                            unset($swapData['captcha_image']);
//                        } elseif ($swapData['iteration']>30) {
//                            $rContext->setFinished();
//                            $rContext->setError('Ошибка распознавания капчи');
                        }
//                        unset($swapData['captcha_id']);
                    }
                }
                $swapData['iteration']--;
            }
            $rContext->setSwapData($swapData);
            return true;
        }

        $error = false; //curl_error($rContext->getCurlHandler());
        if($error){
              $rContext->setError($error);
        }

        if ($this->tlsproxy) {
//            file_put_contents('./logs/fssp/raw_'.time().'.txt', $raw_content."\n\n".$swapData['iteration']);
            if (!isset($res['success']) || !$res['success'] || !isset($res['payload']['status']) || $res['payload']['status']!=200) {
                file_put_contents('./logs/fssp/raw_err_'.$swapData['iteration'].'_'.time().'.txt', $raw_content."\n\n".$swapData['iteration']);
            }

            if (isset($res['payload']['headers']['Set-Cookie'])) {
                $cookies = str_cookies($swapData['session']->cookies);
                $new_cookies = explode('/,/',$res['payload']['headers']['Set-Cookie']);
                foreach ($new_cookies as $cookie) {
//                    print 'Response cookie '.$cookie."\n";
                    $arr = explode(';',$cookie);
                    $arr = explode('=',$arr[0]);
                    $cookies[$arr[0]] = $arr[1];
//                    print 'New cookie '.$arr[0].'='.$arr[1]."\n";
                }
                $new_cookies = cookies_str($cookies);
                $swapData['session']->cookies = $new_cookies;
                $rContext->setSwapData($swapData);
                $mysqli->query("UPDATE isphere.session SET cookies='$new_cookies' WHERE id=" . $swapData['session']->id);
//                file_put_contents('./logs/fssp/fssp_'.$swapData['iteration'].'_'.time().'.cookies',$new_cookies);
            }
           
            $full_content = isset($res['payload']['text'])?$res['payload']['text']:''; //curl_multi_getcontent($rContext->getCurlHandler());
        } else {
            $cookies = str_cookies($swapData['session']->cookies);
            foreach (curl_getinfo($rContext->getCurlHandler(),CURLINFO_COOKIELIST) as $cookie) {
//                print 'Response cookie '.$cookie."\n";
                $arr = explode("	",$cookie);
                if (!isset($cookies[$arr[5]]) || $cookies[$arr[5]]!=$arr[6]) {
                    $cookies[$arr[5]] = $arr[6];
//                    print 'New cookie '.$arr[5].' = '.$arr[6]."\n";
                }
            }
            $new_cookies = cookies_str($cookies);
            $swapData['session']->cookies = $new_cookies;
            $rContext->setSwapData($swapData);
            $mysqli->query("UPDATE isphere.session SET cookies='$new_cookies' WHERE id=" . $swapData['session']->id);
//            file_put_contents('./logs/fssp/fssp_'.$swapData['iteration'].'_'.time().'.cookies',$new_cookies);
        }

        $start = strpos($full_content,'{');
        $finish = strrpos($full_content,'}');
        if ($start!==false && $finish!==false && strpos($full_content,'<!DOCTYPE')!==0) {
            $content = substr($full_content,$start,$finish-$start+1);
            $data = json_decode($content, true);
            if (isset($data['data']))
                $content = $data['data'];
            file_put_contents('./logs/fssp/'.$initData['checktype'].'_'.$swapData['iteration'].'_'.time().'.htm', $content."\n\n".$swapData['iteration']);
        } elseif ($full_content) {
            $content = $full_content;
            file_put_contents('./logs/fssp/'.$initData['checktype'].'_err_'.$swapData['iteration'].'_'.time().'.htm', $content."\n\n".$swapData['iteration']);
        } else {
            $content = $full_content;
            if (isset($swapData['session'])) {
                if ($swapData['session']->proxyid>100)
                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 30 minute),sessionstatusid=6,statuscode='empty' WHERE id=" . $swapData['session']->id);
//                $mysqli->query("UPDATE isphere.session SET proxyid=NULL WHERE sourceid=3 AND sessionstatusid=2 AND proxyid=" . $swapData['session']->proxyid . " AND id<>" . $swapData['session']->id . " ORDER BY lasttime LIMIT 3");
            }
            unset($swapData['session']);
            $rContext->setSwapData($swapData);
        }

        if (/*isset($swapData['captcha_time']) && */!isset($swapData['captcha_image']) && strlen($content)>1000) {
            unset($swapData['captcha_image']);
            unset($swapData['captcha_id']);
            unset($swapData['captcha_time']);
            unset($swapData['captcha_value']);
//            file_put_contents('./logs/fssp/'.$initData['checktype'].'_captcha_image_'.$swapData['iteration'].'_'.time().'.txt',$content);
            $start = strpos($content,'{');
            $finish = strpos($content,'}');
            $content = trim(substr($content,$start,$finish-$start+1));
            $res = json_decode($content, true);
            if ($res && isset($res['image'])) {
                $captcha = substr($res['image'],23,strlen($res['image'])-23);
                $swapData['captcha_image'] = $captcha;
                $swapData['captcha_time'] = microtime(true);
                $captcha = base64_decode($captcha);
//                file_put_contents('./logs/fssp/captcha_'.$swapData['iteration'].'_'.time().'.jpg', $captcha);
//                    if (rand(0,1)) {
//                        $value = neuro_post($captcha,'fsspsitedecode');
                        $value = nn_post($captcha,'fssp');
                        if ($value && substr($value,0,5)<>'ERROR') {
                            $swapData['captcha_value'] = $value;
                            $rContext->setSleep(1);
                            $mysqli->query("UPDATE isphere.session SET lasttime=now(),statuscode='captchaneuro' WHERE id=" . $swapData['session']->id);
                        } else {
                            $mysqli->query("UPDATE isphere.session SET lasttime=now(),statuscode='captcha' WHERE id=" . $swapData['session']->id);
                        }
//                    }
            } elseif ($swapData['iteration']>=5+(isset($swapData['page'])?5*$swapData['page']:5)) {
                $rContext->setFinished();
                $rContext->setError($error?$error:'Превышено количество попыток получения ответа');
                return false;
            } else {
                file_put_contents('./logs/fssp/'.$initData['checktype'].'_captcha_image_err_'.$swapData['iteration'].'_'.time().'.txt',$content);
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='invalidimage' WHERE id=" . $swapData['session']->id);
                unset($swapData['session']);
            }
            $rContext->setSwapData($swapData);
            return true;
        }

        if(preg_match('/connection refused/', $content))
        {
            if (isset($swapData['session'])) {
//                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='refused' WHERE id=" . $swapData['session']->id);
//                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 6 hour),sessionstatusid=6,statuscode='refused' WHERE id=" . $swapData['session']->id);
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='refused' WHERE id=" . $swapData['session']->id);
////!!                $mysqli->query("UPDATE isphere.session SET proxyid=NULL WHERE sourceid=3 AND sessionstatusid=2 AND proxyid=" . $swapData['session']->proxyid . " AND id<>" . $swapData['session']->id . " ORDER BY lasttime LIMIT 3");
//                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval ".($swapData['session']->proxyid<100?"1 minute":"3 hour")."),sessionstatusid=6,statuscode='refused' WHERE sourceid=3 AND proxyid=" . $swapData['session']->proxyid . " ORDER BY lasttime LIMIT 10");
            }
            unset($swapData['session']);
            $rContext->setSwapData($swapData);
        }
        if(preg_match('/Timeout exceeded/', $content))
        {
            if (isset($swapData['session'])) {
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='timeout' WHERE id=" . $swapData['session']->id);
////!!                $mysqli->query("UPDATE isphere.session SET proxyid=NULL WHERE sourceid=3 AND sessionstatusid=2 AND proxyid=" . $swapData['session']->proxyid . " AND id<>" . $swapData['session']->id . " ORDER BY lasttime LIMIT 3");
            }
            unset($swapData['session']);
            $rContext->setSwapData($swapData);
        }
        if(preg_match('/превысили лимит/', $content))
        {
            if (isset($swapData['session'])) {
//                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='limit' WHERE id=" . $swapData['session']->id);
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval ".($swapData['session']->proxyid<100?"10 second":"5 minute")."),sessionstatusid=6,statuscode='limit' WHERE id=" . $swapData['session']->id);
                $mysqli->query("UPDATE isphere.session SET proxyid=NULL WHERE sourceid=3 AND sessionstatusid=2 AND proxyid=" . $swapData['session']->proxyid . " AND id<>" . $swapData['session']->id . " ORDER BY lasttime LIMIT 5");
            }
            unset($swapData['session']);
            $rContext->setSwapData($swapData);
        }
        if(preg_match('/пошло не так/', $content))
        {
            if (isset($swapData['session'])) {
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 10 minute),sessionstatusid=6,statuscode='wrong' WHERE id=" . $swapData['session']->id);
            }
            unset($swapData['session']);
            $rContext->setSwapData($swapData);
        }
        if(preg_match('/502 Bad Gateway/', $content))
        {
            if (isset($swapData['session'])) {
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='bad' WHERE id=" . $swapData['session']->id);
            }
            unset($swapData['session']);
            $rContext->setSwapData($swapData);

            if($swapData['iteration']>=20)
                $error = 'Внутренняя ошибка источника';
        }
        if(preg_match('/400 Bad Request/', $content))
        {
            if (isset($swapData['session'])) {
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='bad' WHERE id=" . $swapData['session']->id);
            }
            unset($swapData['session']);
            $rContext->setSwapData($swapData);

            if($swapData['iteration']>=5)
                $error = 'Внутренняя ошибка источника';
        }
        if(preg_match('/403 Forbidden/', $content))
        {
            if (isset($swapData['session'])) {
//                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='forbidden' WHERE id=" . $swapData['session']->id);
//                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 6 hour),sessionstatusid=6,statuscode='forbidden' WHERE id=" . $swapData['session']->id);
                if ($swapData['session']->proxyid>100) {
                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='forbidden' WHERE id=" . $swapData['session']->id);
                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 30 minute) WHERE used>=3 AND success/used*3<1 AND id=" . $swapData['session']->id);
                } else {
                    $mysqli->query("UPDATE isphere.session SET proxyid=NULL WHERE sourceid=3 AND sessionstatusid=2 AND proxyid=" . $swapData['session']->proxyid . " AND id<>" . $swapData['session']->id . " ORDER BY lasttime LIMIT 3");
                }
//                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval ".($swapData['session']->proxyid<100?"1 minute":"3 hour")."),sessionstatusid=6,statuscode='forbidden' WHERE sourceid=3 AND proxyid=" . $swapData['session']->proxyid . " ORDER BY lasttime LIMIT 10");
            }
            unset($swapData['session']);
            $rContext->setSwapData($swapData);
        }
        if(preg_match('/503 Service Temporarily Unavailable/', $content))
        {
            if (isset($swapData['session'])) {
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='unavailable' WHERE id=" . $swapData['session']->id);
            }
            unset($swapData['session']);
            $rContext->setSwapData($swapData);

            if($swapData['iteration']>=20)
                $error = 'Внутренняя ошибка источника';
        }
        if(preg_match('/уже обрабатывается/', $content))
        {
            file_put_contents('./logs/fssp/'.$initData['checktype'].'_already_'.$swapData['iteration'].'_'.time().'.htm', $content."\n\niteration=".$swapData['iteration']."\nsessionid=".$swapData['session']->id."\nrequestid=".$reqId);
/*
            if (isset($swapData['session']))
//                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='forbidden' WHERE id=" . $swapData['session']->id);
                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='processing' WHERE id=" . $swapData['session']->id);
*/
            unset($swapData['session']);
            $rContext->setSwapData($swapData);
            $rContext->setSleep(5);
            return true;
        }
        if(!$content)
        {
            if (isset($swapData['session'])) {
                if ($swapData['session']->proxyid>100)
                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 30 minute),sessionstatusid=6,statuscode='empty' WHERE id=" . $swapData['session']->id);
//                $mysqli->query("UPDATE isphere.session SET proxyid=NULL WHERE sourceid=3 AND sessionstatusid=2 AND proxyid=" . $swapData['session']->proxyid . " AND id<>" . $swapData['session']->id . " ORDER BY lasttime LIMIT 3");
            }
            unset($swapData['session']);
            $rContext->setSwapData($swapData);
        }

        if(preg_match('/<div id="captcha-popup"/', $content)) {
/*
            if (isset($swapData['session'])) {
//                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,statuscode='exhausted' WHERE used>1 AND id=" . $swapData['session']->id);
                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=4,statuscode='invalidcaptcha' WHERE id=" . $swapData['session']->id);
            }
            unset($swapData['session']);
*/
            if (isset($swapData['captcha_id']) && isset($swapData['captcha_service']) /*&& $this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com'*/) {
//                $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),'".date('Y-m-d H:i:s',$swapData['captcha_time'])."',3,4,'invalidcaptcha','".$swapData['captcha_value']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
            }
            if (isset($swapData['captcha_image']) && isset($swapData['captcha_value'])) {
                $swapData['captcha_value'] = strtr($swapData['captcha_value'],array(':'=>'_','/'=>'_','\\'=>'_','?'=>'_','*'=>'_','"'=>'_','<'=>'_','>'=>'_','|'=>'_'));
                file_put_contents('./logs/fssp/captcha/bad.'.(isset($swapData['captcha_service'])?$this->captcha_service[$swapData['captcha_service']]['host']:'neuro').'/'.md5(time()).'-'.$swapData['captcha_value'].'.jpg',base64_decode($swapData['captcha_image']));
            }
            unset($swapData['captcha_image']);
            unset($swapData['captcha_id']);
            unset($swapData['captcha_time']);
            unset($swapData['captcha_value']);

            $prefix = 'data:image/jpeg;base64,';
            $start = strpos($content,$prefix);
            $captcha = false;
            if($start!==false) {
                $captcha = substr($content,$start+strlen($prefix));
                $finish = strpos($captcha,'=');
                if ($finish!==false) {
                    $captcha = substr($captcha,0,$finish+1);
                    $swapData['captcha_image'] = $captcha;
                    $swapData['captcha_time'] = microtime(true);
                    $captcha = base64_decode($captcha);
                    file_put_contents('./logs/fssp/captcha_'.$swapData['iteration'].'_'.time().'.jpg', $captcha);
//                    if (rand(0,1)) {
//                        $value = neuro_post($captcha,'fsspsitedecode');
                        $value = nn_post($captcha,'fssp');
                        if ($value && substr($value,0,5)<>'ERROR') {
                            $swapData['captcha_value'] = $value;
                            $rContext->setSleep(2);
                            $mysqli->query("UPDATE isphere.session SET lasttime=now(),statuscode='captchaneuro' WHERE id=" . $swapData['session']->id);
                        } else {
                            $mysqli->query("UPDATE isphere.session SET lasttime=now(),statuscode='captcha' WHERE id=" . $swapData['session']->id);
                        }
//                    }
                }
            }
            $rContext->setSwapData($swapData);
            return true;
        } elseif(preg_match_all('/<tr class="[^"]*">(.+?)<\/tr>/msu',$content, $records)) {
            if (isset($swapData['captcha_id']) && isset($swapData['captcha_service']) /*&& $this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com'*/) {
                $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),'".date('Y-m-d H:i:s',$swapData['captcha_time'])."',3,3,'success','".$swapData['captcha_value']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
            }
            if (isset($swapData['captcha_service']) && isset($swapData['captcha_image']) && isset($swapData['captcha_value'])) file_put_contents('./logs/fssp/captcha/good.'.(isset($swapData['captcha_service'])?$this->captcha_service[$swapData['captcha_service']]['host']:'neuro').'/'.md5(time()).'-'.$swapData['captcha_value'].'.jpg',base64_decode($swapData['captcha_image']));

            $mysqli->query("UPDATE isphere.session SET successtime=now(),statuscode='success',success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->id);
            $resultData = isset($swapData['result'])?$swapData['result']:new ResultDataList();

            foreach($records[1] as $record)
            {
                if($initData['checktype']=='fssp_suspect' && preg_match_all('/<td[^>]*>(.*?)<\/td>/msu',$record, $cols)) {
                    if(count($cols[1])<7)
                        continue;

                    $record = array();

                    $record['nameofsuspect'] = new ResultDataField('string','NameOfSuspect', strtr(trim($cols[1][0]),array(' '=>'')), 'ФИО', 'ФИО подозреваемого');
                    $record['birthdayofsuspect'] = new ResultDataField('string','BirthdayOfSuspect', trim($cols[1][1]), 'Дата рождения', 'Дата рождения подозреваемого');
                    $record['birthplaceofsuspect'] = new ResultDataField('string','BirthplaceOfSuspect', trim($cols[1][2]), 'Место рождения', 'Место рождения подозреваемого');
                    $record['initagencyname'] = new ResultDataField('string','InitAgencyName', trim($cols[1][3]), 'Инициатор розыска', 'Наименование органа, инициировавшего розыск');
                    $record['initagencycontact'] = new ResultDataField('string','InitAgencyContact', trim($cols[1][4]), 'Контакты инициатора розыска', 'Контакты органа, инициировавшего розыск');
                    $record['searchagencyname'] = new ResultDataField('string','SearchAgencyName', trim($cols[1][5]), 'Организатор розыска', 'Наименование органа, осуществляющего розыск');
                    $record['clause'] = new ResultDataField('string','Clause', trim($cols[1][6]), 'Статья УК РФ', 'Статья УК РФ');

                    $resultData->addResult($record);
                } elseif(preg_match_all('/<td[^>]*>(.*?)<\/td>/msu',$record, $cols)) {
                    if(count($cols[1])<8)
                        continue;

                    $record = array();

                    $debtor = preg_split('/<br[^>]*>/', $cols[1][0]);
                    $cases = preg_split('/<br[^>]*>/', $cols[1][1]);
                    $case = explode(' от ', $cases[0]);
                    $doc = preg_split('/<br[^>]*>/', $cols[1][2]);
                    $close = preg_split('/<br[^>]*>/', $cols[1][3]);
                    $subjects = preg_split('/<br[^>]*>/', $cols[1][5]);
                    if (preg_match("/^(.*?)Исполнительский сбор:/",$subjects[0],$matches) || preg_match("/^(.*?)Задолженность по расходам:/",$subjects[0],$matches) || preg_match("/^(.*?)Штраф СПИ:/",$subjects[0],$matches)) {
                        $subject = array($matches[1]);
                        $subjects[0] = substr($subjects[0],strlen($matches[1]));
                    } else {
                        $subject = explode(': ', $subjects[0]);
                        array_shift($subjects);
                    }
                    $department = preg_split('/<br[^>]*>/', $cols[1][6]);
                    $bailiff = preg_split('/<br[^>]*>/', $cols[1][7]);

                    foreach($cols[1] as &$row)
                        $row = strip_tags($row);

                    $record['name'] = new ResultDataField('string','Debtor', strtr(trim($debtor[0]),array(' '=>'')), 'Должник', 'Должник');
                    if (preg_match("/^[\d\.\-]+$/",trim($debtor[1]))) {
                        $record['debtor_birthday'] = new ResultDataField('string','DebtorBirthday', trim($debtor[1]), 'Дата рождения', 'Дата рождения должника');
                        if (sizeof($debtor)>2)
                            $record['debtor_birthplace'] = new ResultDataField('string','DebtorBirthplace', trim($debtor[2]), 'Место рождения', 'Место рождения должника');
                    } else {
                        $record['debtor_address'] = new ResultDataField('string','DebtorAddress', trim($debtor[1]), 'Адрес', 'Адрес должника');
                    }

                    $record['case_num'] = new ResultDataField('string','CaseNumber', $case[0], 'Номер ИП', 'Номер исполнительного производства');
                    $record['case_date'] = new ResultDataField('string','CaseDate', trim($case[1]), 'Дата ИП', 'Дата исполнительного производства');
                    if (sizeof($cases)>1)
                        $record['summary_case_num'] = new ResultDataField('string','SummaryCaseNumber', trim($cases[1]), 'Номер сводного ИП', 'Номер сводного исполнительного производства');

                    $record['doc_text'] = new ResultDataField('string','DocText', implode(' ',$doc), 'Реквизиты документа', 'Реквизиты исполнительного документа');
                    $record['doc_type'] = new ResultDataField('string','DocType', strip_tags(substr($doc[0],0,strpos($doc[0],' от '))), 'Вид документа', 'Вид исполнительного документа');
                    $doc[0] = substr($doc[0],strpos($doc[0],' от ')+6);
                    if (preg_match("/^([0-9\.]+) /",$doc[0],$matches))
                        $record['doc_date'] = new ResultDataField('string','DocDate', $matches[1], 'Дата документа', 'Дата исполнительного документа');
                    if (strpos($doc[0],'№') && trim(substr($doc[0],strpos($doc[0],'№')+4)))
                        $record['doc_num'] = new ResultDataField('string','DocNumber', trim(substr($doc[0],strpos($doc[0],'№')+4)), 'Номер документа', 'Номер исполнительного документа');
                    $record['doc_issuer'] = new ResultDataField('string','DocIssuer', $doc[sizeof($doc)-1], 'Орган', 'Орган, выдавший исполнительный документ');
                    if ($h=strpos($doc[0],"href='http")) {
                        $h+=6;
                        $record['doc_url'] = new ResultDataField('url','DocURL', substr($doc[0],$h,strpos($doc[0],"'",$h)-$h), 'URL документа', 'URL исполнительного документа');
                    }
//                    if (strlen($doc)>2)
//                        $record['doc_title'] = new ResultDataField('string','DocTitle', $doc[1], 'Название документа', 'Название исполнительного документа');

                    if (isset($close[0]) && $close[0]) {
                        $record['close_date'] = new ResultDataField('string','CloseDate', $close[0], 'Дата завершения', 'Дата завершения исполнительного производства');
                    }
                    if (isset($close[1])) {
                        $record['close_reason'] = new ResultDataField('string','CloseReason', $close[1] . (isset($close[2])?' '.$close[2]:'') . (isset($close[3])?' '.$close[3]:''), 'Причина завершения', 'Причина завершения исполнительного производства');
//                        file_put_contents('logs/fssp/reasons.csv',"\"".trim(html_entity_decode($close[1]))."\"\n",FILE_APPEND);
                        $record['close_reason1'] = new ResultDataField('string','CloseReason1', substr($close[1],strpos($close[1],' ')+1), 'Причина завершения - статья', 'Причина завершения исполнительного производства - статья');
                    }
                    if (isset($close[2])) {
                        $record['close_reason2'] = new ResultDataField('string','CloseReason2', substr($close[2],strpos($close[2],' ')+1), 'Причина завершения - часть', 'Причина завершения исполнительного производства - часть');
                    }
                    if (isset($close[3])) {
                        $record['close_reason3'] = new ResultDataField('string','CloseReason3', substr($close[3],strpos($close[3],' ')+1), 'Причина завершения - пункт', 'Причина завершения исполнительного производства - пункт');
                    }

                    if ($subject[0]) {
                        $record['subject'] = new ResultDataField('string','Subject', $subject[0], 'Предмет исполнения', 'Предмет исполнения');
//                        file_put_contents('logs/fssp/subjects.csv',"\"".trim(html_entity_decode($subject[0]))."\"\n",FILE_APPEND);
                    }
                    if (sizeof($subject)>1)
                        $record['total'] = new ResultDataField('float','Total', substr($subject[1],0,strpos($subject[1],' ')), 'Сумма задолженности', 'Сумма задолженности');
                    foreach($subjects as $subject) {
                        $subject_b = explode(':', trim($subject));
                        $name = array(
                            'Общая сумма задолженности' => array('',false,'Сумма задолженности'),
                            'Исполнительский сбор' => array('Bailiff','Сбор исполнителя','Сумма сбора исполнителя'),
                            'Задолженность по расходам' => array('Costs','Расходы исполнителя','Сумма расходов исполнителя'),
                            'Штраф СПИ' => array('Fine','Штраф','Сумма штрафа'),
                        );
                        if (isset($name[$subject_b[0]])) {
                            $n = $name[$subject_b[0]][0];
                            $s = $name[$subject_b[0]][1];
                            $t = $name[$subject_b[0]][2];
                            if ($s)
                                $record[$n.'Subject'] = new ResultDataField('string',$n.'Subject', $subject_b[0], $s, $s);
                            if($t && sizeof($subject_b)>1)
                                $record[$n.'Total'] = new ResultDataField('float',$n.'Total', trim(strtr($subject_b[1],array('руб.'=>''))), $t, $t);
                        }
                    }

                    $record['department'] = new ResultDataField('string','Department', $department[0], 'Отдел', 'Отдел судебных приставов');
                    if (isset($department[1]))
                        $record['department_address'] = new ResultDataField('string','DepartmentAddress', $department[1], 'Адрес отдела', 'Адрес отдела судебных приставов');

                    $record['bailiff'] = new ResultDataField('string','Bailiff', $bailiff[0], 'Пристав', 'Судебный пристав-исполнитель');
                    foreach($bailiff as $i => $bailiff_phone)
                        if ($bailiff_phone && $i && $bailiff_phone!=$bailiff[$i-1])
                            $record['bailiff_phone'.$i] = new ResultDataField('string','BailiffPhone', strip_tags($bailiff_phone), 'Телефон', 'Телефон судебного пристава-исполнителя');

                    $resultData->addResult($record);
                }

            }

//            if (preg_match_all('/page=([\d]+)\">[\d]+</',$content,$matches) && (!isset($swapData['page']) || ($swapData['page']<intval($matches[1][sizeof($matches[1])-1]) && $swapData['page']<50))) {
            if (preg_match('/page=([\d]+)\">Следующая</',$content,$matches) && intval($matches[1])<=50) {
                unset($swapData['captcha_image']);
                unset($swapData['captcha_id']);
//                unset($swapData['captcha_time']);
                unset($swapData['captcha_value']);
//                if (!isset($swapData['page'])) $swapData['page']=1;
//                $swapData['page']++;
                $swapData['page'] = intval($matches[1]);
                $swapData['iteration']--;
                $swapData['result'] = $resultData;
                $rContext->setSwapData($swapData);
//                $rContext->setSleep(1);
//                if($swapData['page']==10) {
//                    ini_set('memory_limit', '300M');
//                }
                $rContext->setResultData($resultData);
            } else {
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            }
            return true;
        } elseif(preg_match('/ничего не найдено/',$content)) {
            if (isset($swapData['captcha_id']) && isset($swapData['captcha_service']) /*&& $this->captcha_service[$swapData['captcha_service']]['host']=='rucaptcha.com'*/) {
                $mysqli->query("INSERT INTO isphere.session (endtime,captchatime,sourceid,sessionstatusid,statuscode,captcha,captcha_service,captcha_id) VALUES (now(),'".date('Y-m-d H:i:s',$swapData['captcha_time'])."',3,3,'success','".$swapData['captcha_value']."','".$this->captcha_service[$swapData['captcha_service']]['host']."','".$swapData['captcha_id']."')");
            }
            if (isset($swapData['captcha_service']) && isset($swapData['captcha_image']) && isset($swapData['captcha_value'])) file_put_contents('./logs/fssp/captcha/good.'.(isset($swapData['captcha_service'])?$this->captcha_service[$swapData['captcha_service']]['host']:'neuro').'/'.md5(time()).'-'.$swapData['captcha_value'].'.jpg',base64_decode($swapData['captcha_image']));

            $resultData = new ResultDataList();
            $rContext->setResultData($resultData);
            $rContext->setFinished();

            $mysqli->query("UPDATE isphere.session SET successtime=now(),statuscode='success',success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->id);
            return true;
        } elseif(preg_match('/<div class=\"empty\">([^<]+)/',$content,$matches)) {
            file_put_contents('./logs/fssp/'.$initData['checktype'].'_msg_'.$swapData['iteration'].'_'.time().'.htm', $content."\n\n".$swapData['iteration']);
            if (/*$swapData['iteration']>=3 && */!preg_match('/пошло не так/', $content)) $error = html_entity_decode($matches[1]); //"Некорректный ответ источника";
            unset($swapData['session']);
        } else {
        }

        if(isset($initData['last_name']) && isset($initData['first_name']) && $initData['last_name']==$initData['first_name'] && $swapData['iteration']>=3) {
            $rContext->setFinished();
            $rContext->setError($error?$error:'ФССП не может обработать запрос с совпадением полей в ФИО');
            return false;
        }

        if($error || $swapData['iteration']>=10+(isset($swapData['page'])?5*$swapData['page']:5)) {
            $rContext->setFinished();
            $rContext->setError($error?$error:'Превышено количество попыток получения ответа');
            return false;
        }

        $rContext->setSwapData($swapData);
//        if(isset($swapData['session'])) $rContext->setSleep(1);
        return true;
    }
}

?>
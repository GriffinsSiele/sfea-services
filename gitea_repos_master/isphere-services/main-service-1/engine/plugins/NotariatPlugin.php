<?php

class NotariatPlugin implements PluginInterface
{

    private $names = array (
        'Fio' => array('ФИО'),
        'BirthDate' => array('Дата рождения'),
        'DeathDate' => array('Дата смерти'),
        'DeathActDate' => array('Дата свидетельства о смерти'),
        'DeathActNumber' => array('Номер свидетельства о смерти'),
        'Address' => array('Адрес'),
        'CaseNumber' => array('Номер наследственного дела'),
        'CaseDate' => array('Дата наследственного дела'),
        'CaseCloseDate' => array('Дата завершения наследственного дела'),
        'NotaryName' => array('Нотариус'),
        'ChamberName' => array('Нотариальная палата'),
        'DistrictName' => array('Местонахождение'),
        'ContactAddress' => array('Адрес нотариуса'),
        'ContactPhone' => array('Телефон нотариуса'),
    );

    public function getName()
    {
        return 'Notariat';
    }

    public function getTitle($checktype = '')
    {
        return 'Нотариат - реестр наследственных дел';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=11 AND token>'' ORDER BY lasttime limit 1");

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
                $sessionData->proxyid = $row->proxyid;
                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;

                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1,statuscode='used' WHERE id=".$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if(!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date']))
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (ФИО, дата рождения)');

            return false;
        }

        if(isset($initData['last_name']) && isset($initData['first_name']) && preg_match("/[^А-Яа-яЁё\s\-\.]/ui", $initData['last_name'].' '.$initData['first_name'].(isset($initData['patronymic'])?' '.$initData['patronymic']:''))){
            $rContext->setFinished();
            $rContext->setError('Имя может содержать только русские буквы');
            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
//        if (!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData();
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
            $swapData['iteration']=1;
            $rContext->setSwapData($swapData);
//        }

        $ch = $rContext->getCurlHandler();

        $host = 'https://notariat.ru';
        $page = $host.'/api/probate-cases/';
        $url = $page.'eis-proxy';
        $params = array(
            'name' => $initData['last_name'].' '.$initData['first_name'].(isset($initData['patronymic'])?' '.$initData['patronymic']:''),
            'birth_date' => date('Ymd',strtotime($initData['date'])),
            'death_date' => 'NULL',
        );                
        $header = array(
          'Accept: application/json, text/javascript, */*; q=0.01',
          'Content-Type: application/json',
          'Origin: '.$host,
          'Referer: '.$page,
          'X-CSRFToken: '.$swapData['session']->token,
          'X-Requested-With: XMLHttpRequest',
        );

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
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
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $error = false;

        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
        $content = curl_multi_getcontent($rContext->getCurlHandler());

        if ($content) {
//            file_put_contents('./logs/notariat/search_'.time().'.txt',$content);
            $res = json_decode($content, true);
            if (is_array($res) && isset($res['records']) && is_array($res['records'])) {
                $resultData = new ResultDataList();
                foreach($res['records'] as $rec) if (isset($rec['BirthDate']) && intval(substr($rec['BirthDate'],6,2))) {
                    $data = array();
                    foreach ($rec as $title => $val) {
                        if ($val && $val!='NULL' && array_key_exists($title,$this->names)) {
                            $field = $this->names[$title];
                            if($title=='BirthDate' || $title=='DeathDate')
                                $val = substr($val,0,4).'-'.substr($val,4,2).'-'.substr($val,6,2);
                            if(preg_match("/Date$/",$title))
                                $val = date('d.m.Y',strtotime($val));
                            if(preg_match("/Address$/",$title) && preg_match("/{.*?}/",$val)) {
                                $v = json_decode($val, true);
                                if (isset($v['address'])) $val = $v['address'];
                            }
                            if ($title=='Fio') $title='Name';
                            $data[$title] = new ResultDataField(isset($field[2])?$field[2]:'string',$title,trim($val),$field[0],isset($field[1])?$field[1]:$field[0]);
                        }
                    }
                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                return true;
            } else { 
                if (strpos($content,'временно') || strpos($content,'ведутся работы')) {
                    $error = "Сервис временно недоступен";
                } elseif (strpos($content,'403 Forbidden')) {
                    if ($content) file_put_contents('./logs/notariat/forbidden_'.time().'.txt',$content);
                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 hour),sessionstatusid=6,statuscode='forbidden' WHERE id=".$swapData['session']->id);
                    unset($swapData['session']);
                } else {
                    if ($content) file_put_contents('./logs/notariat/err_'.time().'.txt',$content);
                    if ($swapData['iteration']>3) $error = "Некорректный ответ сервиса";
                }
            }
        } else {
            $error = ($swapData['iteration']>10) && false; //curl_error($rContext->getCurlHandler());
            if ($swapData['iteration']>=5) unset($swapData['session']);
        }

        if($error || $swapData['iteration']>20) {
            $rContext->setFinished();
            $rContext->setError($error==''?'Превышено количество попыток получения ответа':$error);
            return false;
        }

        $rContext->setSwapData($swapData);
        $rContext->setSleep(1);
        return true;
    }
}

?>
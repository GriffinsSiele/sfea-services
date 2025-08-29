<?php

class GooglePlugin implements PluginInterface
{
    public function getName()
    {
        return 'Google';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Поиск аккаунта в Google',
            'google_phone' => 'Google - проверка телефона на наличие пользователя',
            'google_email' => 'Google - проверка email на наличие пользователя',
            'google_name' => 'Google - проверка на соответствие фамилии и имени',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Проверка на наличие аккаунта в Google';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=43 ORDER BY lasttime limit 1");
//        $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password)proxy_auth FROM isphere.proxy WHERE status=1 ORDER BY lasttime limit 1");

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

        $checktype = substr($initData['checktype'],7);

        if($checktype=='phone' && !isset($initData['phone'])) {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (телефон)');

            return false;
        }

        if($checktype=='email' && !isset($initData['email'])) {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (email)');

            return false;
        }

        if($checktype=='name' && ((!isset($initData['phone']) && !isset($initData['email'])) || !isset($initData['first_name']) || !isset($initData['last_name']))) {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (телефон или email, фамилия и имя)');

            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $swapData['session'] = $this->getSessionData();
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

        if (isset($initData['phone'])) {
//            if (strlen($initData['phone'])==10)
//                $initData['phone']='7'.$initData['phone'];
//            if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
//                $initData['phone']='7'.substr($initData['phone'],1);
        }

        if(isset($initData['phone']) && substr($initData['phone'],0,2)!='79')
        {
            $rContext->setFinished();
//            $rContext->setError('Поиск временно производится только по российским мобильным телефонам');

            return false;
        }

        $url = 'https://src2.i-sphere.ru/';
        $proxy_auth = explode(':',$swapData['session']->proxy_auth);
        $params = array(
            "proxy" => $swapData['session']->proxy,
            "pUser" => $proxy_auth[0],
            "pPasswd" => $proxy_auth[1],
        );
        if ($checktype=='phone') {
            $params['phone'] = $initData['phone']; 
            $url .= 'gphone/';
        } elseif ($checktype=='email') {
            $params['email'] = $initData['email']; 
            $url .= 'gemail/';
        } else {
            $params['item'] = isset($initData['phone'])?$initData['phone']:$initData['email']; 
            $params['lastname'] = $initData['last_name']; 
            $params['firstname'] = $initData['first_name']; 
            $url .= 'gname/';
        }
//        var_dump($params); echo "\n";
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params,JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_URL, $url);
        $header[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
//        curl_setopt($ch, CURLOPT_HEADER, true);
//        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        global $total_timeout;
        curl_setopt($ch, CURLOPT_TIMEOUT,$total_timeout+15);

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli, $reqId;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],7);

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $rContext->setSwapData($swapData);

        $error = false;
        $content = curl_multi_getcontent($rContext->getCurlHandler());

        if (!$content) {
            $error = ($swapData['iteration']>5) && curl_error($rContext->getCurlHandler());
        } else {
//            file_put_contents('./logs/google/google_'.$checktype.'_'.time().'.txt',$content."\r\n".(isset($initData['phone'])?$initData['phone']:$initData['email'])."\r\n".$reqId);
            $start = strpos($content,'{');
            $content = trim(substr($content,$start,strlen($content)-$start+1));
            $res = json_decode($content, true);               
            if ($checktype=='name' && $res && isset($res['status']) && $res['status']=='success'){
                $resultData = new ResultDataList();
                if ($res['data']=='exists') {
                    $data = array();
                    $data['result'] = new ResultDataField('string','Result','Найден, '.(isset($initData['phone'])?'телефон':'e-mail').' соответствует фамилии и имени','Результат','Результат');
                    $data['result_code'] = new ResultDataField('string','ResultCode','MATCHED','Код результата','Код результата');
                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->id);
                return true;
            } elseif($res && isset($res['status']) && $res['status']=='success'){
                $resultData = new ResultDataList();
                $data = array();
                $android = false;
                $online = false;
                $other = false;
                if (isset($res['data']) && is_array($res['data']) && sizeof($res['data'])>1) {
                    $data['result'] = new ResultDataField('string','Result','Найден','Результат','Результат');
                    $data['result_code'] = new ResultDataField('string','ResultCode','FOUND','Код результата','Код результата');
                    foreach($res['data'] as $i => $text) {
                        if (is_array($text)) {
                            if (isset($text['models'])) {
                                $models = preg_split("/::/",$text['models']);
                                foreach($models as $i => $model)
                                    if ($model)
                                        $data['device'.$i] = new ResultDataField('string','Device',$model,'Устройство','Устройство');
                            }
                        } else {
                            if (preg_match("/номер телефона, указанный в настройках безопасности аккаунта \($/",$text,$matches))
                                $data['phone'] = new ResultDataField('string','Phone',strtr($res['data'][$i+1],array(' '=>'','('=>'',')'=>'','-'=>'','•'=>'*')),'Телефон','Телефон');
                            if (preg_match("/адрес электронной почты, указанный для аккаунта $/",$text,$matches) || preg_match("/код подтверждения на адрес $/",$text,$matches))
                                $data['email'] = new ResultDataField('string','Email',strtr($res['data'][$i+1],array('•'=>'*')),'E-mail','E-mail');
                            if (preg_match("/устройстве $/",$text,$matches) || preg_match("/устройству $/",$text,$matches) || preg_match("/устройство $/",$text,$matches)) {
//                                $data['device'] = new ResultDataField('string','Device',$res['data'][$i+1],'Устройство','Устройство');
                                $android = true;
                            }
                            if (preg_match("/прокрутите вправо/",$text)) {
                                $android = true;
                            }
                            if (preg_match("/панель уведомлений/",$text)) {
                                $android = true;
                                $online = true;
                            } elseif (preg_match("/запустите приложение/",$text)) {
                                $other = true;
                                $online = true;
                            }
                            if (preg_match("/секретный вопрос$/",$text,$matches))
                                $data['secret'] = new ResultDataField('string','Secret',strtr($res['data'][$i+1],array('>'=>'')),'Секретный вопрос','Секретный вопрос');
                            $data['android'] = new ResultDataField('string','Android',$android?'Да':'Нет','Зарегистрирован на устройстве c Android','Зарегистрирован на устройстве c Android');
                            if ($other)
                                $data['other'] = new ResultDataField('string','Other','Да','Зарегистрирован на ином устройстве','Зарегистрирован на ином устройстве');
                            if ($online)
                                $data['online'] = new ResultDataField('string','Online','Да','Пользователь онлайн и получил уведомление','Пользователь онлайн и получил уведомление');
                        }
                    }
                    $resultData->addResult($data);
                } elseif (isset($res['data']) && strpos($res['data'],'exists')) {
                    if (!strpos($res['data'],'doesnt')) {
                        $data = array();
                        $data['result'] = new ResultDataField('string','Result','Найден','Результат','Результат');
                        $data['result_code'] = new ResultDataField('string','ResultCode','FOUND','Код результата','Код результата');
                        $resultData->addResult($data);
                    }
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1 WHERE id=" . $swapData['session']->id);
                return true;
            } elseif ($res && isset($res['status']) && $res['status']=='error') {
                file_put_contents('./logs/google/google_'.$checktype.'_err_'.time().'.txt',$content."\r\n".(isset($initData['phone'])?$initData['phone']:$initData['email'])."\r\n".$reqId);
                unset($swapData['session']);
                $rContext->setSwapData($swapData);
            } else {
                file_put_contents('./logs/google/google_'.$checktype.'_err_'.time().'.txt',$content."\r\n".(isset($initData['phone'])?$initData['phone']:$initData['email'])."\r\n".$reqId);
                $error = "Некорректный ответ";
            }
        }

        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>1 /*0*/) {
            $error='Превышено количество попыток получения ответа';
        }
        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();
            return false;
        }

        $rContext->setSleep(1);
        return true;
    }
}

?>
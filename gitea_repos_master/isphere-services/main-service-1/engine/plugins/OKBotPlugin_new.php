<?php

class OKBotPlugin implements PluginInterface
{
    public function getName()
    {
        return 'OK';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Поиск учетной записи в OK',
            'ok_phonecheck' => 'OK - проверка телефона на наличие пользователя',
            'ok_emailcheck' => 'OK - проверка email на наличие пользователя',
            'ok_urlcheck' => 'OK - проверка наличия профиля',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Поиск учетной записи в OK';
    }

    public function prepareRequest(&$rContext)
    {
        global $reqId;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
/*
        $rContext->setError('Сервис временно недоступен');
        $rContext->setFinished();
        return false;
*/
        $checktype = substr($initData['checktype'],3);

        if($checktype=='phonecheck' && !isset($initData['phone'])) {
            $rContext->setFinished();
//              $rContext->setError('Указаны не все обязательные параметры (телефон)');
            return false;
        }

        if($checktype=='emailcheck' && !isset($initData['email'])) {
            $rContext->setFinished();
//              $rContext->setError('Указаны не все обязательные параметры (email)');
            return false;
        }

        if($checktype=='urlcheck' && !isset($initData['url'])) {
            $rContext->setFinished();
//              $rContext->setError('Указаны не все обязательные параметры (ссылка на профиль)');
            return false;
        }

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        if (!isset($swapData['db'])){

//            $params['start'] = time();
//            $params['id'] = $reqId;
            $swapData['param'] = isset($initData['phone'])?$initData['phone']:(isset($initData['email'])?$initData['email']:$initData['url']);

            $db = new Redis();
            try {
                global $keydb;
//                $db->connect($keydb['server'],6379,$keydb['connect_timeout'],NULL,100,$keydb['read_timeout']);
                $db->connect($swapData['iteration']%2?$keydb['server1']:$keydb['server2'],6379,$keydb['connect_timeout'],NULL,100,$keydb['read_timeout']);
                $db->auth($keydb['auth']);
                if (!$db->hexists('okbot', $swapData['param'])) {
                    if ($db->llen('okbot_queue')>20) {
                        $db->close();
                        unset($swapData['db']);
                        $rContext->setSwapData($swapData);
                        if ($swapData['iteration']>=10) {
                            $rContext->setFinished();
                            $rContext->setError('Слишком много запросов в очереди');
                        }
                        $rContext->setSleep(1);
                        return false;
                    }

                    $swapData['retry'] = (!isset($swapData['retry']))?1:$swapData['retry'] + 1;
                    if ($swapData['retry']>5) {
                        $db->close();
                        unset($swapData['db']);
                        $rContext->setSwapData($swapData);
                        $rContext->setFinished();
                        $rContext->setError('Превышено количество попыток получения ответа');
                        return false;
                    }

                    $db->rpush('okbot_queue', $swapData['param']);
                }
                $swapData['db'] = $db;
//                $rContext->setSleep(1);
//                $rContext->setSwapData($swapData);
//                return false;
            } catch (Exception $e) {
                if ($swapData['iteration']>10 && !isset($swapData['retry'])) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                }
                $rContext->setSwapData($swapData);
                return false;
            }
        }
        try {
            $content = '';

            $db = $swapData['db'];
//            $params = $swapData['params'];

            if($db->hexists('okbot', $swapData['param'])){
                $content = $db->hget('okbot', $swapData['param']);
                $res = json_decode($content, true);
                if($res && isset($res['status']) && strtoupper($res['status'])=='ERROR'){
                    $db->hdel('okbot', $swapData['param']);
                }
                $db->close();
                unset($swapData['db']);
            }else{
                if($swapData['iteration']>90){
                    $db->hdel('okbot_queue', $swapData['param']);
                    $db->close();
                    $rContext->setFinished();
                    $rContext->setError('Ошибка при обработке запроса');
                    return false;
                }else{
                    if ($swapData['iteration']>30 && $swapData['iteration']%21==0) {
//                        $db->rpush('okbot_queue', $swapData['param']);
                        $db->close();
                        unset($swapData['db']);
                    }
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);
                    return false;
                }
             }
        } catch (Exception $e) {
            unset($swapData['db']);
            $rContext->setSwapData($swapData);
            return false;
        }

        $error = false;

        file_put_contents('./logs/ok/okbot_'.time().'.txt',$content);
        if (!$content) {
                $rContext->setFinished();
                $rContext->setError('Ответ не получен');
        } else {
            $res = json_decode($content, true);

            if(is_array($res) && isset($res['status']) && $res['status']=='ok' && isset($res['records'])){
                $resultData = new ResultDataList();
//                if (sizeof($res['records'])) {
//                    $row = $res;
                foreach ($res['records'] as $row) if (is_array($row)) {
                    $data = array();
                    if (isset($initData['phone']))
                        $data['phone'] = new ResultDataField('string','phone',$initData['phone'],'Телефон','Телефон');
                    if (isset($initData['email']))
                        $data['email'] = new ResultDataField('string','email',$initData['email'],'E-mail','E-mail');
                    foreach($row as $field) if (is_array($field)) {
//                        if ($field['field']=='Name')
                            $data[$field['field']] = new ResultDataField($field['type']=='bool'?'integer':$field['type'],$field['field'],$field['value'],$field['title'],$field['description']);
                    }
                    if (sizeof($data))
                        $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                return false;
            } elseif($res && isset($res['status']) && strtoupper($res['status'])=='ERROR' && isset($res['message'])){
                file_put_contents('./logs/ok/okbot_err_'.time().'.txt',$content);
                if (!strpos($res['message'],'ожидания'))
                    $error = $res['message'];
            } else {
                file_put_contents('./logs/ok/okbot_err_'.time().'.txt',$content);
                $error = "Некорректный ответ";
            }
        }
        $rContext->setSwapData($swapData);

//        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>=10) {
//            $error='Превышено количество попыток получения ответа';
//        }
        if ($error) {
//            $rContext->setResultData(new ResultDataList());
            $rContext->setError($error);
            $rContext->setFinished();
            return false;
        }

        $rContext->setSleep(1);
        return false;
    }

    public function computeRequest(&$rContext)
    {
    }

}

?>
<?php

class OKAppPlugin implements PluginInterface
{
    public function getName()
    {
        return 'OK';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Поиск учетной записи в OK',
            'ok_phoneapp' => 'OK - проверка телефона на наличие пользователя',
            'ok_emailapp' => 'OK - проверка email на наличие пользователя',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Поиск учетной записи в OK';
    }

    public function prepareRequest(&$rContext)
    {
        global $reqId;
/*
        global $clientId;
        if ($clientId!=0 && $clientId!=265) {
            $rContext->setFinished();
            return false;
        }
*/
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
/*
//        $rContext->setError('Сервис временно недоступен');
        $rContext->setFinished();
        return false;
*/
        $checktype = substr($initData['checktype'],3);

        if($checktype=='phoneapp' && !isset($initData['phone'])) {
            $rContext->setFinished();
//              $rContext->setError('Указаны не все обязательные параметры (телефон)');
            return false;
        }

        if($checktype=='emailapp' && !isset($initData['email'])) {
            $rContext->setFinished();
//              $rContext->setError('Указаны не все обязательные параметры (email)');
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
                $db->connect($keydb['server'],6379,$keydb['connect_timeout'],NULL,100,$keydb['read_timeout']);
                $db->auth($keydb['auth']);
                if (!$db->hexists('ok_mobile', $swapData['param']) && ($db->llen('ok_mobile_queue')>20)) {
                    $db->close();
                    unset($swapData['db']);
                    $rContext->setSwapData($swapData);
                    if ($swapData['iteration']>=10) {
                        $rContext->setFinished();
//                        $rContext->setError('Слишком много запросов в очереди');
                    }
                    $rContext->setSleep(1);
                    return false;
                }
                $db->rpush('ok_mobile_queue', $swapData['param']);
                $swapData['db'] = $db;
//                $rContext->setSleep(1);
//                $rContext->setSwapData($swapData);
//                return false;
            } catch (Exception $e) {
                if ($swapData['iteration']>=10) {
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

            if($db->hexists('ok_mobile', $swapData['param'])){
                $content = $db->hget('ok_mobile', $swapData['param']);
                $res = json_decode($content, true);
                if($res && isset($res['status']) && strtoupper($res['status'])=='ERROR'){
                    $db->hdel('ok_mobile', $swapData['param']);
                }
                $db->close();
                unset($swapData['db']);
            }else{
                if($swapData['iteration']>30){
                    $db->hdel('ok_mobile_queue', $swapData['param']);
                    $db->close();
                    $rContext->setFinished();
                    $rContext->setError('Ошибка при обработке запроса');
                    return false;
                }else{
                    if ($swapData['iteration']>10 && $swapData['iteration']%3==0) {
//                        $db->rpush('ok_mobile_queue', $swapData['param']);
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

//        file_put_contents('./logs/ok/okapp_'.time().'.txt',$content);
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
                            $data[$field['field']] = new ResultDataField($field['type']=='bool'?'integer':$field['type']/*.($field['type']=='url'?':recursive':'')*/,$field['field'],$field['value'],$field['title'],$field['description']);
                    }
                    if (sizeof($data))
                        $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                return false;
            } elseif($res && isset($res['status']) && strtoupper($res['status'])=='ERROR' && isset($res['message'])){
                file_put_contents('./logs/ok/okapp_err_'.time().'.txt',$content);
                $error = $res['message'];
            } else {
                file_put_contents('./logs/ok/okapp_err_'.time().'.txt',$content);
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
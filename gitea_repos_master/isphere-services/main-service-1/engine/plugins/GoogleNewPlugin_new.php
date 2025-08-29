<?php

class GoogleNewPlugin implements PluginInterface
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

    public function prepareRequest(&$rContext)
    {
        global $reqId;
        global $userId;

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
/*
            if($checktype=='name'){
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
                return false;
            }
*/
        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        if (!isset($swapData['db'])){
            $queue = 'google';

            $params['start'] = time();
            $params['id'] = $reqId;

            if ($checktype=='phone') {
                $params['item'] = $initData['phone'];
                $swapData['param'] = $reqId.'_'.$params['item'];
            } elseif ($checktype=='email') {
                $params['item'] = $initData['email']; 
                $swapData['param'] = $reqId.'_'.$params['item'];
            } elseif ($checktype=='name') {
                $queue = 'googlename';
                $params['item'] = isset($initData['phone'])?$initData['phone']:$initData['email']; 
                $params['lastname'] = $initData['last_name']; 
                $params['firstname'] = $initData['first_name']; 
                $swapData['param'] = $reqId.'_'.$params['item'];
            } else {
                $rContext->setFinished();
                $rContext->setError('Неизвестный метод проверки');
                return false;
            }

            $swapData['params'] = $params;
            $swapData['queue'] = $queue;

            $db = new Redis();
            try {
                global $keydb;
                $db->connect($keydb['server'],6379,$keydb['connect_timeout'],NULL,100,$keydb['read_timeout']);
                $db->auth($keydb['auth']);
//                if($db->hexists($queue.'_result', $swapData['param']))
//                    $db->hdel($queue.'_result', $swapData['param']);
                $db->rpush($queue.'_queue', json_encode($params));
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
            $queue = $swapData['queue'];
            $params = $swapData['params'];

            if($db->hexists($queue.'_result', $swapData['param'])){
                $content = $db->hget($queue.'_result', $swapData['param']);
                $res = json_decode($content, true);
                if($res && isset($res['status']) && strtoupper($res['status'])=='ERROR'){
                    $db->hdel($queue.'_result', $swapData['param']);
                }
                $db->close();
                unset($swapData['db']);
            }else{
                if($swapData['iteration']>60){
                    $db->hdel($queue.'_queue', $params['item']);
                    $db->close();
                    $rContext->setFinished();
                    $rContext->setError('Ошибка при обработке запроса');
                    return false;
                }else{
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

        file_put_contents('./logs/googlenew/'.$initData['checktype'].'_'.time().'.txt',$content);
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
//                    if (isset($initData['phone']))
//                        $data['Phone'] = new ResultDataField('string','Phone',$initData['phone'],'Телефон','Телефон');
//                    if (isset($initData['email']))
//                        $data['Email'] = new ResultDataField('string','Email',$initData['email'],'E-mail','E-mail');
                    $counter = array();
                    foreach($row as $field) if (is_array($field)) {
                        $r = new ResultDataField($field['type']=='bool'?'integer':$field['type'],$field['field'],$field['value'],$field['title'],$field['description']);
                        if (!isset($counter[$field['field']])) {
                            $data[$field['field']] = $r;
                            $counter[$field['field']] = 0;
                        } else {
                            $data[$field['field'].++$counter[$field['field']]] = $r;
                        }
                    }
                    if (sizeof($data))
                        $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                return false;
            } elseif($res && isset($res['status']) && strtoupper($res['status'])=='ERROR' && isset($res['message'])){
                file_put_contents('./logs/googlenew/'.$initData['checktype'].'_err_'.time().'.txt',$content);
                $error = $res['message'];
            } else {
                file_put_contents('./logs/googlenew/'.$initData['checktype'].'_err_'.time().'.txt',$content);
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
<?php

class TestPlugin implements PluginInterface
{
    public function getName()
    {
        return 'Test';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Тестовый поиск',
            'test_phone' => 'Тестовый поиск по номеру телефона',
            'test_email' => 'Тестовый поиск по email',
            'test_auto' => 'Тестовый поиск по автомобилю',
            'test_org' => 'Тестовый поиск по организации',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Тестовый поиск';
    }

    public function prepareRequest(&$rContext)
    {
        global $reqId;
        global $userId;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
/*
        $rContext->setError('Сервис временно недоступен');
        $rContext->setFinished();
        return false;
*/
        $checktype = substr($initData['checktype'],5);

        if($checktype=='person' && !isset($initData['inn'])) {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (ИНН)');
            return false;
        }

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

        if(($checktype=='auto') && !isset($initData['regnum']) && !isset($initData['vin'])/* && !isset($initData['bodynum'])*/)
        {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (VIN или номер кузова или госномер)');
            return false;
        }

        if($checktype=='org' && !isset($initData['inn'])) {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (ИНН)');
            return false;
        }

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        if (!isset($swapData['db'])){

//            $params['start'] = time();
//            $params['id'] = $reqId;
            if ($checktype=='person') {
                $swapData['param'] = $initData['inn'];
            } elseif ($checktype=='phone') {
                $swapData['param'] = $initData['phone'];
            } elseif ($checktype=='email') {
                $swapData['param'] = $initData['email'];
            } elseif ($checktype=='auto') {
                $swapData['param'] = isset($initData['regnum'])?$initData['regnum']:$initData['vin'];
            } elseif ($checktype=='org') {
                $swapData['param'] = $initData['inn'];
            } else {
                $rContext->setFinished();
                $rContext->setError('Неизвестный метод проверки');
                return false;
            }

            $db = new Redis();
            try {
                global $keydb;
                $db->connect($keydb['server1'],6379,$keydb['connect_timeout'],NULL,100,$keydb['read_timeout']);
                $db->auth($keydb['auth']);
                if($db->hexists('test', $swapData['param']))
                    $db->hdel('test', $swapData['param']);
                $db->rpush('test_queue', $swapData['param']);
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

            if($db->hexists('test', $swapData['param'])){
                $content = $db->hget('test', $swapData['param']);
/*
                $res = json_decode($content, true);
                if($res && isset($res['status']) && strtoupper($res['status'])=='ERROR'){
                    $db->hdel('test', $swapData['param']);
                }
*/
                $db->close();
                unset($swapData['db']);
            }else{
                if($swapData['iteration']>60){
                    $db->hdel('test_queue', $swapData['param']);
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

        file_put_contents('./logs/test/test_'.time().'.txt',$content);
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
                        $data['Phone'] = new ResultDataField('string','Phone',$initData['phone'],'Телефон','Телефон');
                    if (isset($initData['email']))
                        $data['Email'] = new ResultDataField('string','Email',$initData['email'],'E-mail','E-mail');
                    if (isset($initData['inn']))
                        $data['INN'] = new ResultDataField('string','INN',$initData['inn'],'ИНН','ИНН');
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
                file_put_contents('./logs/test/test_err_'.time().'.txt',$content);
                $error = $res['message'];
            } else {
                file_put_contents('./logs/test/test_err_'.time().'.txt',$content);
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
<?php

class KADPlugin implements PluginInterface
{
    public function getName()
    {
        return 'kad';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'КАД - поиск арбитражных дел',
            'kad_org' => 'КАД - поиск арбитражных дел организации',
            'kad_person' => 'КАД - поиск арбитражных дел физлица',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
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
        $checktype = substr($initData['checktype'],4);

        if($checktype=='person' && !isset($initData['inn'])) {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (ИНН)');
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
                $db->connect($keydb['server'],6379,$keydb['connect_timeout'],NULL,100,$keydb['read_timeout']);
                $db->auth($keydb['auth']);
                if($db->hexists('kad', $swapData['param']))
                    $db->hdel('kad', $swapData['param']);
                if ($db->llen('kad_queue')>20) {
                    $db->close();
                    $rContext->setFinished();
                    $rContext->setError('Слишком много запросов в очереди');
                    return false;
                }
                $db->rpush('kad_queue', $swapData['param']);
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

            if($db->hexists('kad', $swapData['param'])){
                $content = $db->hget('kad', $swapData['param']);
                $res = json_decode($content, true);
                if($res && isset($res['status']) && strtoupper($res['status'])=='ERROR'){
                    $db->hdel('kad', $swapData['param']);
                }
                $db->close();
                unset($swapData['db']);
            }else{
                if($swapData['iteration']>20){
                    $db->hdel('kad_queue', $swapData['param']);
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

//        file_put_contents('./logs/kad/kad_'.time().'.txt',$content);
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
                file_put_contents('./logs/kad/kad_err_'.time().'.txt',$content);
                $error = $res['message'];
            } else {
                file_put_contents('./logs/kad/kad_err_'.time().'.txt',$content);
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
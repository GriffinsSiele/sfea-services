<?php

class GetContactPlugin implements PluginInterface
{
    public function __construct()
    {
    }

    public function getName()
    {
        return 'GetContact';
    }

    public function getTitle()
    {
        return 'Поиск в GetContact';
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
        if(!isset($initData['phone'])) {
            $rContext->setFinished();
            $rContext->setError('Не задан номер телефона');
            return false;
        }

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        if (!isset($swapData['db'])){

//            $params['start'] = time();
//            $params['id'] = $reqId;
//            $params['phone'] = $initData['phone'];

            $db = new Redis();
            try {
                global $keydb;
                $db->connect($keydb['server'],6379,$keydb['connect_timeout'],NULL,100,$keydb['read_timeout']);
                $db->auth($keydb['auth']);
                if (!$db->hexists('getcontact', $initData['phone']) && ($db->llen('getcontact_queue')>20)) {
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
                $db->rpush('getcontact_queue', $initData['phone']);
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

            if($db->hexists('getcontact', $initData['phone'])){
                $content = $db->hget('getcontact', $initData['phone']);
                $res = json_decode($content, true);
                if($res && isset($res['status']) && strtoupper($res['status'])=='ERROR'){
                    $db->hdel('getcontact', $initData['phone']);
                }
                $db->close();
                unset($swapData['db']);
            }else{
                if($swapData['iteration']>30){
                    $db->hdel('getcontact_queue', $initData['phone']);
                    $db->close();
                    $rContext->setFinished();
                    $rContext->setError('Ошибка при обработке запроса');
                    return false;
                }else{
                    if ($swapData['iteration']>5 && $swapData['iteration']%3==0) {
//                        $db->rpush('getcontact_queue', $initData['phone']);
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

        file_put_contents('./logs/getcontact/getcontact_'.time().'.txt',$content);
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
                    $data['phone'] = new ResultDataField('string','phone',$initData['phone'],'Телефон','Телефон');
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
                file_put_contents('./logs/getcontact/getcontact_err_'.time().'.txt',$content);
                $error = $res['message'];
            } else {
                file_put_contents('./logs/getcontact/getcontact_err_'.time().'.txt',$content);
                $error = "Некорректный ответ";
            }
        }
        $rContext->setSwapData($swapData);

        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>=10) {
            $error='Превышено количество попыток получения ответа';
        }
        if ($error) {
//            $rContext->setResultData(new ResultDataList());
            $rContext->setError($error);
            $rContext->setFinished();
            return false;
        }

//        $rContext->setSleep(1);
        return true;
    }

    public function computeRequest(&$rContext)
    {
    }

}

?>
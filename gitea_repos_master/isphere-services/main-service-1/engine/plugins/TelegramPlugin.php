<?php

class TelegramPlugin implements PluginInterface
{
    public function __construct()
    {
    }

    public function getName()
    {
        return 'Telegram';
    }

    public function getTitle($checktype = '')
    {
        return 'Поиск телефона в Telegram';
    }

    public function prepareRequest(&$rContext)
    {
        global $reqId;
        global $clientId;
//        if ($clientId==1 || $clientId==9 || $clientId==11 || $clientId==15 || $clientId==19 || $clientId==25 || $clientId==172 || $clientId==221) { // ecofinance gpbl plus-bank kviku mcplat idfinance carcade zaymer
//        if ($clientId!=3 && $clientId!=18 && $clientId!=21 && $clientId!=22 && $clientId!=23 && $clientId!=24 && $clientId!=51 && $clientId!=58 && $clientId!=63 && $clientId!=67 && $clientId!=68 && $clientId!=80 && $clientId!=83 && $clientId!=128 && $clientId!=132 && $clientId!=144 && $clientId!=158 && $clientId!=166 && $clientId!=173 && $clientId!=204 && $clientId!=207 && $clientId!=209 && $clientId!=216 && $clientId!=232 && $clientId!=241 && $clientId!=250 && $clientId!=251 && $clientId!=261 && $clientId!=263 && $clientId!=265 && $clientId!=311 && $clientId!=313) { // cabis prima rusagrogarant egida cr911 aton deltaincom lorry tele2 lknarcapital taxi515 stoloto arenda-a gkm infopro zdeslegko ixi banksoyuz papazaim azurdrive psb carloson fix_ asiacredit ipoteka24 dengi003 trucker srochnodengi ud isphere veal migcredit
        if ($clientId!=265) {
//            $rContext->setError('Сервис временно недоступен');
            $rContext->setFinished();
            return false;
        }

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

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
//                $db->connect($keydb['server'],6379,$keydb['connect_timeout'],NULL,100,$keydb['read_timeout']);
                $db->connect($swapData['iteration']%2?$keydb['server1']:$keydb['server1'],6379,$keydb['connect_timeout'],NULL,100,$keydb['read_timeout']);
                $db->auth($keydb['auth']);
                if (!$db->hexists('telegram', $initData['phone']) && ($db->llen('telegram_queue')>20)) {
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
                $db->rpush('telegram_queue', $initData['phone']);
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

            if($db->hexists('telegram', $initData['phone'])){
                $content = $db->hget('telegram', $initData['phone']);
                $res = json_decode($content, true);
                if($res && isset($res['status']) && strtoupper($res['status'])=='ERROR'){
                    $db->hdel('telegram', $initData['phone']);
                }
                $db->close();
                unset($swapData['db']);
            }else{
                if($swapData['iteration']>30){
                    $db->hdel('telegram_queue', $initData['phone']);
                    $db->close();
                    $rContext->setFinished();
                    $rContext->setError('Ошибка при обработке запроса');
                    return false;
                }else{
/*
                    if ($swapData['iteration']>10 && $swapData['iteration']%3==0) {
//                        $db->rpush('telegram_queue', $initData['phone']);
                        $db->close();
                        unset($swapData['db']);
                    }
*/
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

//        file_put_contents('./logs/telegram/telegram_'.time().'.txt',$content);
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
                    $counter = array();
                    foreach($row as $field) if (is_array($field)) {
                        $r = new ResultDataField($field['type']=='bool'?'integer':$field['type'],$field['field'],$field['value'],$field['title'],$field['description']);
                        if (!isset($counter[$field['field']])) {
                            $data[$field['field']] = $r;
                            $counter[$field['field']] = 0;
                            if ($field['field']=='image') {
//                                file_put_contents('./logs/telegram/'.$initData['phone'].'.jpg',base64_decode(substr($field['value'],22)));
                            }
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
                file_put_contents('./logs/telegram/telegram_err_'.time().'.txt',$content);
                $error = $res['message'];
            } else {
                file_put_contents('./logs/telegram/telegram_err_'.time().'.txt',$content);
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
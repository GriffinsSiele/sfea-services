<?php

class UniPlugin implements PluginInterface
{
    private $connect_iterations = 10;
    private $read_iterations = 600;

    public function getName($checktype = '')
    {
        global $checktypes;
        return isset($checktypes[$checktype])?$checktypes[$checktype]['source_name']:'';
    }

    public function getTitle($checktype = '')
    {
        global $checktypes;
        return isset($checktypes[$checktype])?$checktypes[$checktype]['title']:'';
    }

    private function log($text)
    {
        global $reqId;
        file_put_contents('./logs/uni-'.date('Y-m-d').'.log',date('Y-m-d H:i:s')." id=$reqId $text\n",FILE_APPEND);
    }

    public function prepareRequest(&$rContext)
    {
        global $keydb, $rabbitmq;
        global $reqId, $clientId, $userId;
        global $checktypes;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = $initData['checktype'];
        if (!isset($checktypes[$checktype])) {
            $rContext->setError('Неизвестный тип проверки');
            $rContext->setFinished();
            return false;
        }
//TODO: Перенести обязательные поля в таблицу CheckType и сделать универсальную проверку
        if($checktype=='cbr_person' && !isset($initData['inn'])) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='cbr_org' && !isset($initData['inn'])) {
            $rContext->setFinished();
            return false;
        }
        if(($checktype=='fms_passport' || $checktype=='fmsdb_passport') && (!isset($initData['passport_series']) || !isset($initData['passport_number']))) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='fns_disfind' && !isset($initData['inn'])) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='fns_disqualified' && (!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date']))) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='fns_mru' && !isset($initData['inn'])) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='fns_paytax' && !isset($initData['inn'])) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='fns_revexp' && !isset($initData['inn'])) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='fns_rmsp' && !isset($initData['inn'])) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='fns_snr' && !isset($initData['inn'])) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='fns_sshr' && !isset($initData['inn'])) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='fns_taxoffence' && !isset($initData['inn'])) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='fsin_person' && (!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date']))) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='minjust_person' && (!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date']))) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='minjust_inn' && !isset($initData['inn'])) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='minjust_org' && !isset($initData['inn'])) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='rosobrnadzor_license' && !isset($initData['inn'])) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='google_name' && (!isset($initData['last_name']) || !isset($initData['first_name']) || (!isset($initData['phone']) && !isset($initData['email'])))) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='samsung_name' && (!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date']) || (!isset($initData['phone']) && !isset($initData['email'])))) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='samsung_person' && (!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date']))) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='elpts' && !isset($initData['vin']) && !isset($initData['bodynum']) && !isset($initData['pts'])) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='alfastrah' && !isset($initData['vin']) && !isset($initData['regnum'])) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='kad_person' && !isset($initData['inn'])) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='kad_org' && !isset($initData['inn'])) {
            $rContext->setFinished();
            return false;
        }
        if($checktype=='2gis_inn' && !isset($initData['inn'])) {
            $rContext->setFinished();
            return false;
        }

        if($checktype=='domclick_phone' && substr($initData['phone'],0,2)!='79') {
            $rContext->setFinished();
//            $rContext->setError('Поиск производится только по мобильным телефонам в коде 9xx');
            return false;
        }
        if($checktype=='avito_phone' && substr($initData['phone'],0,2)!='79') {
            $rContext->setFinished();
//            $rContext->setError('Поиск производится только по мобильным телефонам в коде 9xx');
            return false;
        }
        if($checktype=='2gis_phone' && !preg_match("/7[346789]/",substr($initData['phone'],0,2))) {
            $rContext->setFinished();
//            $rContext->setError('Поиск производится только по телефонам РФ или Казахстана');
            return false;
        }
        if($checktype=='sber_phone' && substr($initData['phone'],0,2)!='79') {
            $rContext->setFinished();
//            $rContext->setError('Поиск производится только по мобильным телефонам в коде 9xx');
            return false;
        }
/*
        if($checktype=='xiaomi_phone' && substr($initData['phone'],0,2)!='79') {
            $rContext->setFinished();
//            $rContext->setError('Поиск производится только по мобильным телефонам в коде 9xx');
            return false;
        }
*/
        if($checktype=='krasnoebeloe_phone' && substr($initData['phone'],0,2)!='79') {
            $rContext->setFinished();
//            $rContext->setError('Поиск производится только по мобильным телефонам в коде 9xx');
            return false;
        }
        if($checktype=='winelab_phone' && substr($initData['phone'],0,2)!='79') {
            $rContext->setFinished();
//            $rContext->setError('Поиск производится только по мобильным телефонам в коде 9xx');
            return false;
        }
        if($checktype=='petrovich_phone' && substr($initData['phone'],0,2)!='79') {
            $rContext->setFinished();
//            $rContext->setError('Поиск производится только по мобильным телефонам в коде 9xx');
            return false;
        }
        if($checktype=='litres_phone' && substr($initData['phone'],0,2)!='79') {
            $rContext->setFinished();
//            $rContext->setError('Поиск производится только по мобильным телефонам в коде 9xx');
            return false;
        }
        if($checktype=='domru_phone' && substr($initData['phone'],0,2)!='79') {
            $rContext->setFinished();
//            $rContext->setError('Поиск производится только по мобильным телефонам в коде 9xx');
            return false;
        }

        if($checktype=='kad_person' && !preg_match("/^\d{12}$/",$initData['inn'])) {
            $rContext->setFinished();
            $rContext->setError('ИНН физлица должен содержать 12 цифр');
            return false;
        }
        if($checktype=='kad_org' && !preg_match("/^\d{10}$/",$initData['inn'])) {
            $rContext->setFinished();
            $rContext->setError('ИНН юрлица должен содержать 10 цифр');
            return false;
        }
        if($checktype=='2gis_inn' && !preg_match("/^\d{10,12}$/",$initData['inn'])) {
            $rContext->setFinished();
            $rContext->setError('Некорректный ИНН');
            return false;
        }

        if($checktype=='elpts' && isset($initData['pts']) && !preg_match("/^\d{15}$/",$initData['pts'])) {
            $rContext->setFinished();
//            $rContext->setError('Номер ЭПТС должен содержать 15 цифр');
            return false;
        }

        if($checktype=='ok_nickcheck') {
            $initData['url'] = 'ok.ru/'.$initData['nick'];
        }
        if($checktype=='google_nick') {
            $initData['email'] = $initData['nick'].'@gmail.com';
        }
        if($checktype=='facebook_phoneurl') {
//            if (isset($initData['phone']) && substr($initData['phone'],0,1)!='+') $initData['phone'] = '+'.$initData['phone'];
        }
        if(($clientId==264 || $clientId==265) && ($checktype=='sber_phone' || $checktype=='sber_email')) {
//            $checktypes[$checktype]['queue'] = $checktypes[$checktype]['hash'] = 'sber_simple'.substr($checktype,4);
        }
        if(($clientId==264 || $clientId==265) && ($checktype=='xiaomi_phone' || $checktype=='xiaomi_email')) {
//            $checktypes[$checktype]['queue'] = $checktypes[$checktype]['hash'] = $checktype;
        }

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        if (!isset($swapData['params'])) {
            $swapData['retries'] = $checktypes[$checktype]['retries']?intval($checktypes[$checktype]['retries']):3;
            $swapData['retry_iterations'] = $checktypes[$checktype]['retry_interval']?intval($checktypes[$checktype]['retry_interval']):10;

            $params['id'] = $reqId;
//TODO:Убрать из ключа и желательно из параметров не поддерживаемые источником поля (попадают ФИО+ДР вместе с телефоном/email, они нужны только для google_name, ok_phone и ok_email)
            $params['key'] = md5(paramsToKey($initData));
            $params['starttime'] = time();
            $params['timeout'] = $swapData['retry_iterations'];
            $params['retry'] = 0;
            unset($initData['checktype']);
            if (isset($initData['passport_series'])) $initData['passport_series'] = intval($initData['passport_series']);
            if (isset($initData['passport_number'])) $initData['passport_number'] = intval($initData['passport_number']);
            if (isset($initData['date'])) $initData['date'] = date('Y-m-d',strtotime($initData['date']));
            if ($checktypes[$checktype]['format'] && strpos($checktypes[$checktype]['format'],'text')!==false) {
                $swapData['params'] = isset($initData['phone'])?$initData['phone']:(isset($initData['email'])?$initData['email']:(isset($initData['inn'])?$initData['inn']:(isset($initData['url'])?$initData['url']:(isset($initData['nick'])?$initData['nick']:''))));
                $swapData['format'] = 'text/plain';
                $swapData['key'] = $swapData['params'];
            } else {
//                if (isset($initData['phone']) && substr($initData['phone'],0,1)!='+') $initData['phone'] = '+'.$initData['phone'];
                $swapData['params'] = array_merge($params, $initData);
                $swapData['format'] = 'application/json';
                $swapData['key'] = $params['key'];
            }
            $swapData['queue'] = isset($checktypes[$checktype]['queue'])?$checktypes[$checktype]['queue']:$checktype;
            $swapData['hash'] = isset($checktypes[$checktype]['hash'])?$checktypes[$checktype]['hash']:$checktype;
            $swapData['send'] = true;
        }
        $rContext->setSwapData($swapData);

        $start_time = microtime(true);
        if (!isset($keydb['db']) && !(isset($keydb['errors']) && $keydb['errors']>=$keydb['tries']) && !(isset($keydb['error_time']) && ($start_time-$keydb['error_time'])<$keydb['try_interval'])) { // нет глобального подключения к redis/keydb
            $db = new Redis();
            try {
                $db->connect($keydb['server'],6379,$keydb['connect_timeout'],NULL,100,$keydb['read_timeout']);
                $db->auth($keydb['auth']);
                $keydb['db'] = $db;
                $process_time = microtime(true)-$start_time;
                $process_time = number_format($process_time,2,'.','');
                $this->log("checktype=$checktype iteration={$swapData['iteration']} process_time=$process_time KeyDB connected");
                unset($keydb['error_time']);
            } catch (Exception $e) {
                if (!isset($keydb['errors'])) $keydb['errors'] = 0;
                $keydb['errors']++;
                $keydb['error_time'] = microtime(true);
                $process_time = $keydb['error_time']-$start_time;
                $process_time = number_format($process_time,2,'.','');
                $this->log("checktype=$checktype iteration={$swapData['iteration']} process_time=$process_time KeyDB connect error {$keydb['errors']}: ".$e->getMessage());
            }
        }

        if (!isset($keydb['db'])) {
            if (isset($keydb['errors']) && $keydb['errors']>=$keydb['tries']) {  // Слишком много ошибок при работе с keydb
                $rContext->setFinished();
                $rContext->setError('Внутренняя ошибка платформы');
            }
            $rContext->setSwapData($swapData);
            $rContext->setSleep(1);
            return false;
        }

        $db = $keydb['db'];
        $content = '';
        $res = false;

        try {
            $start_time = microtime(true);
            if($swapData['send'] && !isset($swapData['retry']) && $db->hexists($swapData['hash'], $swapData['key'])) { // Чистим кеш при первой отправке задания
//                $db->hdel($swapData['hash'], $swapData['key']);
//                $this->log("checktype=$checktype iteration={$swapData['iteration']} Deleted {$swapData['key']} from {$swapData['hash']}");
            }
            if($db->hexists($swapData['hash'], $swapData['key'])) { // Есть нужный ответ
                $content = $db->hget($swapData['hash'], $swapData['key']);
//                file_put_contents('./logs/uni/'.$checktype.'_'.$swapData['iteration'].'_'.time().'.txt',$content);

                $res = json_decode($content, true);
                if(!is_array($res) || !isset($res['status'])){ // Некорректный ответ
                    $this->log("checktype=$checktype iteration={$swapData['iteration']} Received bad answer for {$swapData['key']} from {$swapData['hash']}");
//                    $db->close();
                    $rContext->setFinished();
                    $rContext->setError('Некорректный ответ');
                    return false;
                }
                if(strtoupper($res['status'])=='ERROR' && $swapData['send']){ // Удаляем ответ с ошибкой
                    $db->hdel($swapData['hash'], $swapData['key']);
                    $this->log("checktype=$checktype iteration={$swapData['iteration']} Deleted previous error {$res['code']} for {$swapData['key']} from {$swapData['hash']}");
                    $res = false;
                }
                if(strtoupper($res['status'])=='INCOMPLETE' || strtoupper($res['status'])=='PROCESSING'){ // Игнорируем промежуточный ответ
                    $res = false;
                }
            }
        } catch (Exception $e) {
            $process_time = microtime(true)-$start_time;
            $process_time = number_format($process_time,2,'.','');
            $this->log("checktype=$checktype iteration={$swapData['iteration']} process_time=$process_time KeyDB read error ".$e->getMessage());
            unset($keydb['db']);
            $rContext->setSwapData($swapData);
            return false;
        }

        if (!$res) {
            if ($swapData['iteration']%$swapData['retry_iterations']==1) { // Долго нет ответа, повторно отправляем задание (обработчик не справился?)
                $swapData['send'] = true;
            }
            if ($swapData['iteration']>=$this->read_iterations) { // Слишком долго нет ответа, выдаём ошибку
//                $db->close();
                $rContext->setFinished();
                $rContext->setError('Ошибка при обработке запроса');
                return false;
            } elseif ($swapData['send']) {
                if ($checktypes[$checktype]['format'] && strpos($checktypes[$checktype]['format'],'text')!==false) {
                    $message = $swapData['params'];
                } else {
                    $swapData['params']['starttime'] = time();
                    $swapData['params']['retry'] = isset($swapData['retry'])?$swapData['retry']:0;
                    $message = json_encode($swapData['params'],true);
                }

                if (isset($swapData['retry']) && $swapData['retry']>=$swapData['retries']) { // Слишком много повторных попыток, выдаём ошибку
//                    $db->close();
                    $rContext->setFinished();
                    $rContext->setError('Превышено количество попыток получения ответа');
                    return false;
                }
                $start_time = microtime(true);
                if ($checktypes[$checktype]['format'] && strpos($checktypes[$checktype]['format'],'keydb')!==false) {
                    try {
                        if ($db->llen($swapData['queue'])>=20) {
//                            $db->close();
//                            unset($keydb['db']);
//                            $rContext->setSwapData($swapData);
                            if ($swapData['iteration']>=10) {
//                                $db->close();
                                $rContext->setFinished();
                                $rContext->setError('Слишком много запросов в очереди');
                            }
                            $process_time = microtime(true)-$start_time;
                            $process_time = number_format($process_time,2,'.','');
                            $this->log("checktype=$checktype iteration={$swapData['iteration']} retry=".(isset($swapData['retry'])?$swapData['retry']:0)." process_time=$process_time Too many queries in {$swapData['queue']}");
                            $rContext->setSwapData($swapData);
                            $rContext->setSleep(1);
                            return false;
                        }

                        if (!isset($swapData['retry']) || !$swapData['retry']) {
                            $db->rpush($swapData['queue'], $message);
                        } else {
                            $db->lpush($swapData['queue'], $message);
                        }
                        $swapData['send'] = false;
                        $process_time = microtime(true)-$start_time;
                        $process_time = number_format($process_time,2,'.','');
                        $swapData['retry'] = (!isset($swapData['retry']))?1:$swapData['retry'] + 1;
                        $this->log("checktype=$checktype iteration={$swapData['iteration']} retry={$swapData['retry']} process_time=$process_time Added $message to {$swapData['queue']}");
                    } catch (Exception $e) {
                        $process_time = microtime(true)-$start_time;
                        $process_time = number_format($process_time,2,'.','');
                        $this->log("checktype=$checktype iteration={$swapData['iteration']} process_time=$process_time KeyDB write error ".$e->getMessage());
                        unset($keydb['db']);
                        $rContext->setSwapData($swapData);
                        return false;
                    }
                } elseif (0 && $checktype=='google_name'/* && !(isset($swapData['retry']) && $swapData['retry']%2)*/) {
                    try {
                        $r = $rabbitmq;
                        $r['host'] = $rabbitmq['host1'];
                        $connection = new AMQPConnection($r);
                        $connection->connect();
                        $channel = new AMQPChannel($connection);
                        $exchange = new AMQPExchange($channel);
                        $attr = array(
//                            'delivery_mode' => 1,
                            'expiration' => $swapData['retry_iterations'],
                            'content_type' => $swapData['format'],
                            'headers' => array(
                               'X-Request-Id' => $rContext->getId(),
                            ),
                        );
                        $result = $exchange->publish($message,$swapData['queue'],AMQP_NOPARAM,$attr);
                        $connection->disconnect();
                        $swapData['send'] = false;
                        $process_time = microtime(true)-$start_time;
                        $process_time = number_format($process_time,2,'.','');
                        $swapData['retry'] = (!isset($swapData['retry']))?1:$swapData['retry'] + 1;
                        $this->log("checktype=$checktype iteration={$swapData['iteration']} retry={$swapData['retry']} process_time=$process_time Sent {$message} to {$swapData['queue']} on {$r['host']}");
                    } catch (Exception $e) {
                        $process_time = microtime(true)-$start_time;
                        $process_time = number_format($process_time,2,'.','');
                        $this->log("checktype=$checktype iteration={$swapData['iteration']} process_time=$process_time RabbitMQ error ".$e->getMessage());
                        if ($swapData['iteration']>=$this->connect_iterations && !isset($swapData['retry'])) { // Слишком много неудачных попыток отправить первое задание
                            $db->close();
                            $rContext->setFinished();
                            $rContext->setError('Внутренняя ошибка платформы');
                        }
                    }
                } elseif (!(isset($rabbitmq['errors']) && $rabbitmq['errors']>=$rabbitmq['tries']) && !(isset($rabbitmq['error_time']) && ($start_time-$rabbitmq['error_time'])<$rabbitmq['try_interval'])) {
                    try {
                        if (!isset($rabbitmq['connection'])) {
                            $rabbitmq['connection'] = new AMQPConnection($rabbitmq);
                            $rabbitmq['connection']->connect();
                            $rabbitmq['channel'] = new AMQPChannel($rabbitmq['connection']);
                            $rabbitmq['exchange'] = new AMQPExchange($rabbitmq['channel']);
                        }
                        $attr = array(
//                            'delivery_mode' => 1,
                            'expiration' => $swapData['retry_iterations'],
                            'content_type' => $swapData['format'],
                            'headers' => array(
                               'X-Request-Id' => $rContext->getId(),
                            ),
                        );
                        $result = $rabbitmq['exchange']->publish($message,$swapData['queue'],AMQP_NOPARAM,$attr);
//                        $rabbitmq['connection']->disconnect();
                        $swapData['send'] = false;
                        $process_time = microtime(true)-$start_time;
                        $process_time = number_format($process_time,2,'.','');
                        $swapData['retry'] = (!isset($swapData['retry']))?1:$swapData['retry'] + 1;
                        $this->log("checktype=$checktype iteration={$swapData['iteration']} retry={$swapData['retry']} process_time=$process_time Sent $message to {$swapData['queue']}");
                        unset($rabbitmq['error_time']);
                    } catch (Exception $e) {
                        if (!isset($rabbitmq['errors'])) $rabbitmq['errors'] = 0;
                        $rabbitmq['errors']++;
                        $rabbitmq['error_time'] = microtime(true);
                        $process_time = $rabbitmq['error_time']-$start_time;
                        $process_time = number_format($process_time,2,'.','');
                        $this->log("checktype=$checktype iteration={$swapData['iteration']} process_time=$process_time RabbitMQ error {$rabbitmq['errors']}: ".$e->getMessage());
                        unset($rabbitmq['connection']);
                    }
                }
            }

            if (isset($rabbitmq['errors']) && $rabbitmq['errors']>=$rabbitmq['tries']) { // Слишком много ошибок при работе с rabbitmq
//                $db->close();
                $rContext->setFinished();
                $rContext->setError('Внутренняя ошибка платформы');
            }
            $rContext->setSwapData($swapData);
            $rContext->setSleep(1);
            return false;
        }

        $error = false;

        if (isset($res['status']) && strtoupper($res['status'])=='OK' || strtoupper($res['status'])=='SUCCESS'){
            $this->log("checktype=$checktype iteration={$swapData['iteration']} Received success {$res['code']} for {$swapData['key']} from {$swapData['hash']}");
            global $fields;
            $source_name = $this->getName($checktype);
            if (isset($fields[$checktype])) $sfield = $fields[$checktype];
            elseif (isset($fields[$source_name])) $sfield = $fields[$source_name];

            $resultData = new ResultDataList();
            if (is_array($res) && isset($res['records'])) {
                foreach ($res['records'] as $row) {
                    if (is_array($row) && sizeof($row)) {
                        $data = array();
                        $counter = array();
                        if (isset($initData['phone']))
                            $data['phone'] = new ResultDataField('string','phone',$initData['phone'],'Телефон','Телефон');
                        if (isset($initData['email']))
                            $data['email'] = new ResultDataField('string','email',$initData['email'],'E-mail','E-mail');
                        if (isset($initData['nick']))
                            $data['nick'] = new ResultDataField('string','nick',$initData['nick'],'Псевдоним','Псевдоним');
//                        if (isset($initData['inn']))
//                            $data['inn'] = new ResultDataField('string','inn',$initData['inn'],'ИНН','ИНН');
                        foreach($row as $key => $field) {
                            if (is_array($field) && !isset($sfield[$key]) && $key===intval($key)) {
                                if (isset($field['value'])) {
                                    $values = array($field['value']);
                                    $key = $field['field'];
                                } else {
//                                    var_dump($field); echo"key = $key\n";
                                    $values = array();
                                }
                            } else {
                                if (is_array($field) && isset($field[0])) {
                                    $values = $field;
                                } elseif (is_array($field)) {
                                    $values = array(json_encode(array($field),true));
                                } else {
                                    $values = array($field);
                                }
                                $field = isset($sfield[$key])?$sfield[$key]:array('type'=>'string','title'=>$key,'description'=>$key);
                            }
                            foreach ($values as $i => $value) {
                                if (strtolower($key)=='phone') $value = preg_replace("/[\s\-\(\)]/","",trim($value));
                                if ((strtolower($key)=='phone' || strtolower($key)=='phonenumber') && isset($initData['phone']) && $value==substr($initData['phone'],strlen($initData['phone'])-strlen($value))) $value = false;
                                if (strtolower($key)=='email' && isset($initData['email']) && $value==$initData['email']) $value = false;
                                if ($value) {
                                    $r = new ResultDataField($field['type']=='bool'?'integer':$field['type']/*.($field['type']=='url'?':recursive':'')*/,$key,$value,$field['title'],$field['description']);
                                    if (!isset($counter[$key])) {
                                        $data[$key] = $r;
                                        $counter[$key] = 0;
                                    } else {
                                        $data[$key.++$counter[$key]] = $r;
                                    }
                                }
                            }
                        }
                        if (sizeof($data))
                            $resultData->addResult($data);
                    }
                }
            }
            $rContext->setResultData($resultData);
//            $db->close();
            $rContext->setFinished();
            return false;
        } elseif(isset($res['status']) && strtoupper($res['status'])=='ERROR'){
            $this->log("checktype=$checktype iteration={$swapData['iteration']} Received error {$res['code']} for {$swapData['key']} from {$swapData['hash']}");
            if (isset($res['code']) && $res['code']==404 || $res['code']==422 || $res['code']==505) { // Страна не поддерживается или некорректные данные или невозможно выполнить запрос по таким данным
//                $db->close();
                $rContext->setFinished();
                return false;
            } elseif (isset($res['message'])) {
                if (strpos($res['message'],'nexpected response')!==false || strpos($res['message'],'nknown response')!==false) $res['message'] = 'Неподдерживаемый ответ источника';
                if (strpos($res['message'],' request:')!==false) $res['message'] = 'Внутренняя ошибка платформы';
                if (strpos($res['message'],' object')!==false) $res['message'] = 'Внутренняя ошибка платформы';
                if (strpos($res['message'],'hasura')!==false || strpos($res['message'],'went away')!==false) $res['message'] = 'Внутренняя ошибка источника';
                if ((!isset($swapData['retry']) || $swapData['retry']<=$swapData['retries']) && 
                  strpos($res['message'],'ожидания') || strpos($res['message'],'попыток') || strpos($res['message'],'лимит') || strpos($res['message'],'источник') || strpos($res['message'],'времен') || strpos($res['message'],'запрос') || strpos($res['message'],'блокир') || strpos($res['message'],'unavailable') || 
                  strpos($res['message'],'нутренняя') || strpos($res['message'],'еизвестная') || strpos($res['message'],'возникла') || strpos($res['message'],'не отвечает') || strpos($res['message'],'таймаут') || strpos($res['message'],'пустой') || strpos($res['message'],'expected')!==false || 
                  strpos($res['message'],'perform') || strpos($res['message'],'onnect') || strpos($res['message'],'time')!==false || strpos($res['message'],'page') || strpos($res['message'],'parse') || strpos($res['message'],'token') || strpos($res['message'],'database') || strpos($res['message'],'recognized') || 
                  strpos($res['message'],'ccount') || strpos($res['message'],'auth') || strpos($res['message'],'session') || strpos($res['message'],'trace') || strpos($res['message'],'html') || strpos($res['message'],'request') || strpos($res['message'],'proxy') || strpos($res['message'],'ariti') || 
                  strpos($res['message'],'сесси') || strpos($res['message'],'отправки') || strpos($res['message'],'капч') || strpos($res['message'],'aptcha') || strpos($res['message'],'ккаунт') || strpos($res['message'],'пароль') || strpos($res['message'],'очеред') || !trim($res['message'])) { // Временная ошибка, повторяем задание
                    $swapData['send'] = true;
                } else { // Постоянная ошибка, выдаём в ответе
//                    $db->close();
                    $rContext->setFinished();
                    $rContext->setError(/*(isset($res['code'])?$res['code'].' ':'').*/$res['message']);
                }
            } else { // Иначе повторяем
                $swapData['send'] = true;
            }
        }
        $rContext->setSwapData($swapData);
        $rContext->setSleep(1);
        return false;
    }

    public function computeRequest(&$rContext)
    {
    }

}

?>
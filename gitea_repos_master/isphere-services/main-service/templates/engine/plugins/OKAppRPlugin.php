<?php

class OKAppRPlugin implements PluginInterface
{
    public function getName()
    {
        return 'OK';
    }

    public function getTitle($checktype = '')
    {
        $title = ['' => 'Поиск учетной записи в OK', 'ok_phoneapp' => 'OK - проверка телефона на наличие пользователя', 'ok_emailapp' => 'OK - проверка email на наличие пользователя'];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск учетной записи в OK';
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $reqId = $params['_reqId'];
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
                $rContext->setError('Сервис временно недоступен');
                $rContext->setFinished();
                return false;
        */
        $checktype = \substr($initData['checktype'], 3);
        if ('phoneapp' == $checktype && !isset($initData['phone'])) {
            $rContext->setFinished();
            //              $rContext->setError('Указаны не все обязательные параметры (телефон)');
            return false;
        }
        if ('emailapp' == $checktype && !isset($initData['email'])) {
            $rContext->setFinished();
            //              $rContext->setError('Указаны не все обязательные параметры (email)');
            return false;
        }
        $swapData['iteration'] = !isset($swapData['iteration']) ? 1 : $swapData['iteration'] + 1;
        if (!isset($swapData['db'])) {
            //            $params['start'] = time();
            //            $params['id'] = $reqId;
            $swapData['param'] = isset($initData['phone']) ? $initData['phone'] : (isset($initData['email']) ? $initData['email'] : $initData['url']);
            $connection_params = ['host' => '172.16.1.25'.(3 + $swapData['iteration'] % 2), 'port' => 5672, 'vhost' => '/', 'login' => 'default_user_FbMUOGaBu35fZ6YXHA3', 'password' => 'Fh0tqYCJH5Wl15pMl0uOoI3k1jUnGFTf'];
            $connection = new AMQPConnection($connection_params);
            $db = new Redis();
            try {
                $connection->connect();
                $channel = new AMQPChannel($connection);
                $exchange = new AMQPExchange($channel);
                /*
                                $queue = new AMQPQueue($channel);
                                $queue->setName('test');
                                $queue->setFlags(AMQP_IFUNUSED | AMQP_AUTODELETE);
                */
                $result = $exchange->publish($swapData['param'], 'ok-mobile');
                $connection->disconnect();
                $db->connect('172.16.1.25'.(3 + $swapData['iteration'] % 2), 6379, 1, null, 100, 1);
                $db->auth(['n1vTY76fuCT59MH']);
                $swapData['db'] = $db;
                //                $rContext->setSleep(1);
                //                $rContext->setSwapData($swapData);
                //                return false;
            } catch (Exception $e) {
                if ($swapData['iteration'] >= 10) {
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
            if ($db->hexists('ok-mobile', $swapData['param'])) {
                $content = $db->hget('ok-mobile', $swapData['param']);
                $db->close();
                unset($swapData['db']);
            } else {
                if ($swapData['iteration'] > 60) {
                    $db->close();
                    $rContext->setFinished();
                    $rContext->setError('Ошибка при обработке запроса');

                    return false;
                } else {
                    if ($swapData['iteration'] % 10 == 0) {
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
        //        \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/ok/okapp_'.time().'.txt',$content);
        if (!$content) {
            $rContext->setFinished();
            $rContext->setError('Ответ не получен');
        } else {
            $res = \json_decode($content, true);
            if (\is_array($res) && isset($res['status']) && 'ok' == $res['status'] && isset($res['records'])) {
                $resultData = new ResultDataList();
                //                if (sizeof($res['records'])) {
                //                    $row = $res;
                foreach ($res['records'] as $row) {
                    if (\is_array($row)) {
                        $data = [];
                        if (isset($initData['phone'])) {
                            $data['phone'] = new ResultDataField('string', 'phone', $initData['phone'], 'Телефон', 'Телефон');
                        }
                        if (isset($initData['email'])) {
                            $data['email'] = new ResultDataField('string', 'email', $initData['email'], 'E-mail', 'E-mail');
                        }
                        foreach ($row as $field) {
                            if (\is_array($field)) {
                                //                        if ($field['field']=='Name')
                                $data[$field['field']] = new ResultDataField('bool' == $field['type'] ? 'integer' : $field['type'], $field['field'], $field['value'], $field['title'], $field['description']);
                            }
                        }
                        if (\count($data)) {
                            $resultData->addResult($data);
                        }
                    }
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                return false;
            } elseif ($res && isset($res['status']) && 'ERROR' == \strtoupper($res['status']) && isset($res['message'])) {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/ok/okapp_err_'.\time().'.txt', $content);
                $error = $res['message'];
            } else {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/ok/okapp_err_'.\time().'.txt', $content);
                $error = 'Некорректный ответ';
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

    public function computeRequest(array $params, &$rContext): void
    {
    }
}

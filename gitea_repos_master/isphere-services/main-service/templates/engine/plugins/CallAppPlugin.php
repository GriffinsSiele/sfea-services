<?php

class CallAppPlugin implements PluginInterface
{
    public function __construct()
    {
    }

    public function getName()
    {
        return 'CallApp';
    }

    public function getTitle($checktype = '')
    {
        return 'Поиск телефона в CallApp';
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $reqId = $params['_reqId'];
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        /*
                $rContext->setError('Сервис временно недоступен');
                $rContext->setFinished();
                return false;
        */
        if (!isset($initData['phone'])) {
            $rContext->setFinished();
            $rContext->setError('Не задан номер телефона');

            return false;
        }
        $swapData['iteration'] = !isset($swapData['iteration']) ? 1 : $swapData['iteration'] + 1;
        if (!isset($swapData['db'])) {
            //            $params['start'] = time();
            //            $params['id'] = $reqId;
            //            $params['phone'] = $initData['phone'];
            $db = new Redis();
            try {
//                $db->connect('172.16.11.1', 6379, 1, null, 100, 1);
                $db->connect('172.16.1.25'.(3 + $swapData['iteration'] % 2), 6379, 1, null, 100, 1);
                $db->auth(['n1vTY76fuCT59MH']);
                if ($db->llen('callapp_queue') > 20) {
                    $db->close();
                    $rContext->setFinished();
                    $rContext->setError('Слишком много запросов в очереди');

                    return false;
                }
                $db->rpush('callapp_queue', $initData['phone']);
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
            if ($db->hexists('callapp', $initData['phone'])) {
                $content = $db->hget('callapp', $initData['phone']);
                $db->close();
                unset($swapData['db']);
            } else {
                if ($swapData['iteration'] > 20) {
                    $db->hdel('callapp_queue', $initData['phone']);
                    $db->close();
                    $rContext->setFinished();
                    $rContext->setError('Ошибка при обработке запроса');

                    return false;
                } else {
                    if ($swapData['iteration'] % 8 == 0) {
                        $db->rpush('callapp_queue', $initData['phone']);
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
        //        \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/callapp/callapp_'.(isset($swapData['path'])?$swapData['path'].'_':'').time().'.txt',$content);
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
                        $data['phone'] = new ResultDataField('string', 'phone', $initData['phone'], 'Телефон', 'Телефон');
                        foreach ($row as $field) {
                            if (\is_array($field)) {
                                //                        if ($field['field']=='Name')
                                $data[$field['field']] = new ResultDataField('bool' == $field['type'] ? 'integer' : $field['type'].('url' == $field['type'] ? ':recursive' : ''), $field['field'], $field['value'], $field['title'], $field['description']);
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
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/callapp/callapp_err_'.(isset($swapData['path']) ? $swapData['path'].'_' : '').\time().'.txt', $content);
                $error = $res['message'];
            } else {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/callapp/callapp_err_'.(isset($swapData['path']) ? $swapData['path'].'_' : '').\time().'.txt', $content);
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

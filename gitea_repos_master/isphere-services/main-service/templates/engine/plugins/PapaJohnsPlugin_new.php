<?php

class PapaJohnsPlugin_new implements PluginInterface
{
    public function getName()
    {
        return 'PapaJohns';
    }

    public function getTitle($checktype = '')
    {
        $title = [
            '' => 'Поиск в Papa Johns',
            'papajohns_phone' => 'Papa Johns - проверка телефона на наличие пользователя',
        ];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск в Papa Johns';
    }

    public function prepareRequest(array $params, &$rContext)
    {
        global $reqId;

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

        $swapData['iteration'] = (!isset($swapData['iteration'])) ? 1 : $swapData['iteration'] + 1;
        if (!isset($swapData['db'])) {
            //            $params['start'] = time();
            //            $params['id'] = $reqId;
            //            $params['phone'] = $initData['phone'];

            $db = new Redis();
            try {
                $db->connect('172.16.1.25'.(3 + $swapData['iteration'] % 2), 6379, 1, null, 100, 1);
                $db->auth(['n1vTY76fuCT59MH']);
                if ($db->llen('papajohns_queue') > 20) {
                    $db->close();
                    $rContext->setFinished();
                    $rContext->setError('Слишком много запросов в очереди');

                    return false;
                }
                $db->rpush('papajohns_queue', $initData['phone']);
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

            if ($db->hexists('papajohns', $initData['phone'])) {
                $content = $db->hget('papajohns', $initData['phone']);
                $db->close();
                unset($swapData['db']);
            } else {
                if ($swapData['iteration'] > 30) {
                    $db->hdel('papajohns_queue', $initData['phone']);
                    $db->close();
                    $rContext->setFinished();
                    $rContext->setError('Ошибка при обработке запроса');

                    return false;
                } else {
                    if ($swapData['iteration'] % 8 == 0) {
                        $db->rpush('papajohns_queue', $initData['phone']);
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

        \file_put_contents('./logs/papajohns/papajohns_'.\time().'.txt', $content);
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
                \file_put_contents('./logs/papajohns/papajohns_err_'.\time().'.txt', $content);
                $error = $res['message'];
            } else {
                \file_put_contents('./logs/papajohns/papajohns_err_'.\time().'.txt', $content);
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

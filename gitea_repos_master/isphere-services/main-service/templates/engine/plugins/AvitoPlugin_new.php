<?php

class AvitoPlugin_new implements PluginInterface
{
    public function getName()
    {
        return 'Avito';
    }

    public function getTitle($checktype = '')
    {
        $title = [
            '' => 'Поиск учетной записи в Avito',
            'avito_phone' => 'Avito - проверка телефона на наличие пользователя',
            'avito_email' => 'Avito - проверка email на наличие пользователя',
        ];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск учетной записи в Avito';
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
        $checktype = \substr($initData['checktype'], 6);

        if ('phone' == $checktype && !isset($initData['phone'])) {
            $rContext->setFinished();
            //              $rContext->setError('Указаны не все обязательные параметры (телефон)');
            return false;
        }

        if ('email' == $checktype && !isset($initData['email'])) {
            $rContext->setFinished();
            //              $rContext->setError('Указаны не все обязательные параметры (email)');
            return false;
        }

        if (isset($initData['phone']) && '79' != \substr($initData['phone'], 0, 2)) {
            $rContext->setFinished();
            //            $rContext->setError('Поиск производится только по мобильным телефонам в коде 9xx');
            return false;
        }

        $swapData['iteration'] = (!isset($swapData['iteration'])) ? 1 : $swapData['iteration'] + 1;
        if (!isset($swapData['db'])) {
            //            $params['start'] = time();
            //            $params['id'] = $reqId;
            $swapData['param'] = isset($initData['phone']) ? $initData['phone'] : $initData['email'];

            $db = new Redis();
            try {
                $db->connect('172.16.11.1', 6379, 1, null, 100, 1);
                $db->auth(['n1vTY76fuCT59MH']);
                if ($db->llen('avito_queue') > 20) {
                    $db->close();
                    $rContext->setFinished();
                    $rContext->setError('Слишком много запросов в очереди');

                    return false;
                }
                $db->rpush('avito_queue', $swapData['param']);
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

            if ($db->hexists('avito', $swapData['param'])) {
                $content = $db->hget('avito', $swapData['param']);
                $db->close();
                unset($swapData['db']);
            } else {
                if ($swapData['iteration'] > 30) {
                    $db->hdel('avito_queue', $swapData['param']);
                    $db->close();
                    $rContext->setFinished();
                    $rContext->setError('Ошибка при обработке запроса');

                    return false;
                } else {
                    if ($swapData['iteration'] % 8 == 0) {
                        $db->rpush('avito_queue', $swapData['param']);
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

        \file_put_contents('./logs/avito/avito_'.\time().'.txt', $content);
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
                \file_put_contents('./logs/avito/avito_err_'.\time().'.txt', $content);
                $error = $res['message'];
            } else {
                \file_put_contents('./logs/avito/avito_err_'.\time().'.txt', $content);
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

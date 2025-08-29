<?php

class OKBotPlugin_new implements PluginInterface
{
    public function getName()
    {
        return 'OK';
    }

    public function getTitle($checktype = '')
    {
        $title = [
            '' => 'Поиск учетной записи в OK',
            'ok_phonecheck' => 'OK - проверка телефона на наличие пользователя',
            'ok_emailcheck' => 'OK - проверка email на наличие пользователя',
            'ok_urlcheck' => 'OK - проверка наличия профиля',
        ];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск учетной записи в OK';
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
        $checktype = \substr($initData['checktype'], 3);

        if ('phonecheck' == $checktype && !isset($initData['phone'])) {
            $rContext->setFinished();
            //              $rContext->setError('Указаны не все обязательные параметры (телефон)');
            return false;
        }

        if ('emailcheck' == $checktype && !isset($initData['email'])) {
            $rContext->setFinished();
            //              $rContext->setError('Указаны не все обязательные параметры (email)');
            return false;
        }

        if ('urlcheck' == $checktype && !isset($initData['url'])) {
            $rContext->setFinished();
            //              $rContext->setError('Указаны не все обязательные параметры (ссылка на профиль)');
            return false;
        }

        $swapData['iteration'] = (!isset($swapData['iteration'])) ? 1 : $swapData['iteration'] + 1;
        if (!isset($swapData['db'])) {
            //            $params['start'] = time();
            //            $params['id'] = $reqId;
            $swapData['param'] = isset($initData['phone']) ? $initData['phone'] : (isset($initData['email']) ? $initData['email'] : $initData['url']);

            $db = new Redis();
            try {
                $db->connect('172.16.11.1', 6379, 1, null, 100, 1);
                $db->auth(['n1vTY76fuCT59MH']);
                if ($db->llen('okbot_queue') > 20) {
                    $db->close();
                    $rContext->setFinished();
                    //                    $rContext->setError('Слишком много запросов в очереди');
                    return false;
                }
                $db->rpush('okbot_queue', $swapData['param']);
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

            if ($db->hexists('okbot', $swapData['param'])) {
                $content = $db->hget('okbot', $swapData['param']);
                $db->close();
                unset($swapData['db']);
            } else {
                if ($swapData['iteration'] > 20) {
                    $db->hdel('okbot_queue', $swapData['param']);
                    $db->close();
                    $rContext->setFinished();
                    $rContext->setError('Ошибка при обработке запроса');

                    return false;
                } else {
                    if ($swapData['iteration'] % 8 == 0) {
                        $db->rpush('okbot_queue', $swapData['param']);
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

        \file_put_contents('./logs/ok/okbot_'.\time().'.txt', $content);
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
                \file_put_contents('./logs/ok/okbot_err_'.\time().'.txt', $content);
                $error = $res['message'];
            } else {
                \file_put_contents('./logs/ok/okbot_err_'.\time().'.txt', $content);
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

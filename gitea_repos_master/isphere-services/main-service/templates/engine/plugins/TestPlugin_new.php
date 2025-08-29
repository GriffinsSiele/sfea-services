<?php

class TestPlugin_new implements PluginInterface
{
    public function getName()
    {
        return 'Test';
    }

    public function getTitle($checktype = '')
    {
        $title = [
            '' => 'Тестовый поиск',
            'test_phone' => 'Тестовый поиск по номеру телефона',
            'test_email' => 'Тестовый поиск по email',
            'test_auto' => 'Тестовый поиск по автомобилю',
            'test_org' => 'Тестовый поиск по организации',
        ];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Тестовый поиск';
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
        $checktype = \substr($initData['checktype'], 5);

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

        if (('auto' == $checktype) && !isset($initData['regnum']) && !isset($initData['vin'])/* && !isset($initData['bodynum']) */) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (VIN или номер кузова или госномер)');
            return false;
        }

        if ('org' == $checktype && !isset($initData['inn'])) {
            $rContext->setFinished();
            //              $rContext->setError('Указаны не все обязательные параметры (ИНН)');
            return false;
        }

        $swapData['iteration'] = (!isset($swapData['iteration'])) ? 1 : $swapData['iteration'] + 1;
        if (!isset($swapData['db'])) {
            //            $params['start'] = time();
            //            $params['id'] = $reqId;
            if ('phone' == $checktype) {
                $swapData['param'] = $initData['phone'];
            } elseif ('email' == $checktype) {
                $swapData['param'] = $initData['email'];
            } elseif ('auto' == $checktype) {
                $swapData['param'] = isset($initData['regnum']) ? $initData['regnum'] : $initData['vin'];
            } elseif ('org' == $checktype) {
                $swapData['param'] = $initData['inn'];
            } else {
                $rContext->setFinished();
                $rContext->setError('Неизвестный метод проверки');

                return false;
            }

            $db = new Redis();
            try {
                $db->connect('172.16.11.1', 6379, 1, null, 100, 1);
                $db->auth(['n1vTY76fuCT59MH']);
                $db->rpush('test_queue', $swapData['param']);
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

            if ($db->hexists('test', $swapData['param'])) {
                $content = $db->hget('test', $swapData['param']);
                $db->close();
                unset($swapData['db']);
            } else {
                if ($swapData['iteration'] > 60) {
                    $db->hdel('test_queue', $swapData['param']);
                    $db->close();
                    $rContext->setFinished();
                    $rContext->setError('Ошибка при обработке запроса');

                    return false;
                } else {
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

        \file_put_contents('./logs/test/test_'.\time().'.txt', $content);
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
                            $data['Phone'] = new ResultDataField('string', 'Phone', $initData['phone'], 'Телефон', 'Телефон');
                        }
                        if (isset($initData['email'])) {
                            $data['Email'] = new ResultDataField('string', 'Email', $initData['email'], 'E-mail', 'E-mail');
                        }
                        if (isset($initData['inn'])) {
                            $data['INN'] = new ResultDataField('string', 'INN', $initData['inn'], 'ИНН', 'ИНН');
                        }
                        $counter = [];
                        foreach ($row as $field) {
                            if (\is_array($field)) {
                                $r = new ResultDataField('bool' == $field['type'] ? 'integer' : $field['type'], $field['field'], $field['value'], $field['title'], $field['description']);
                                if (!isset($counter[$field['field']])) {
                                    $data[$field['field']] = $r;
                                    $counter[$field['field']] = 0;
                                } else {
                                    $data[$field['field'].++$counter[$field['field']]] = $r;
                                }
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
                \file_put_contents('./logs/test/test_err_'.\time().'.txt', $content);
                $error = $res['message'];
            } else {
                \file_put_contents('./logs/test/test_err_'.\time().'.txt', $content);
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

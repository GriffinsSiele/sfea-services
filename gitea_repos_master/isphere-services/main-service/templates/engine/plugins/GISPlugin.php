<?php

class GISPlugin implements PluginInterface
{
    public function getName()
    {
        return '2GIS';
    }

    public function getTitle($checktype = '')
    {
        $title = ['' => 'Поиск в справочнике 2ГИС', '2gis_phone' => 'Поиск телефона в справочнике 2ГИС', '2gis_inn' => 'Поиск ИНН в справочнике 2ГИС'];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск в справочнике 2ГИС';
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
        $checktype = \substr($initData['checktype'], 5);
        if ('phone' == $checktype && !isset($initData['phone'])) {
            $rContext->setFinished();
            //              $rContext->setError('Указаны не все обязательные параметры (телефон)');
            return false;
        }
        if ('inn' == $checktype && !isset($initData['inn'])) {
            $rContext->setFinished();
            //              $rContext->setError('Указаны не все обязательные параметры (ИНН)');
            return false;
        }
        if (isset($initData['phone'])) {
            if (10 == \strlen($initData['phone'])) {
                $initData['phone'] = '7'.$initData['phone'];
            }
            if (11 == \strlen($initData['phone']) && '8' == \substr($initData['phone'], 0, 1)) {
                $initData['phone'] = '7'.\substr($initData['phone'], 1);
            }
            if (!\preg_match('/7[346789]/', \substr($initData['phone'], 0, 2))) {
                $rContext->setFinished();
                //                $rContext->setError('Поиск производится только по телефонам РФ или Казастана');
                return false;
            }
        }
        $swapData['iteration'] = !isset($swapData['iteration']) ? 1 : $swapData['iteration'] + 1;
        if (!isset($swapData['db'])) {
            //            $params['start'] = time();
            //            $params['id'] = $reqId;
            $swapData['param'] = isset($initData['phone']) ? $initData['phone'] : $initData['inn'];
            $db = new Redis();
            try {
//                $db->connect('172.16.11.1', 6379, 1, null, 100, 1);
                $db->connect('172.16.1.25'.(3 + $swapData['iteration'] % 2), 6379, 1, null, 100, 1);
                $db->auth(['n1vTY76fuCT59MH']);
                if ($db->llen('2gis_'.$checktype.'_queue') > 20) {
                    $db->close();
                    $rContext->setFinished();
                    $rContext->setError('Слишком много запросов в очереди');

                    return false;
                }
                $db->rpush('2gis_'.$checktype.'_queue', $swapData['param']);
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
            if ($db->hexists('2gis_'.$checktype, $swapData['param'])) {
                $content = $db->hget('2gis_'.$checktype, $swapData['param']);
                $db->close();
                unset($swapData['db']);
            } else {
                if ($swapData['iteration'] > 20) {
                    $db->hdel('2gis_'.$checktype.'_queue', $swapData['param']);
                    $db->close();
                    $rContext->setFinished();
                    $rContext->setError('Ошибка при обработке запроса');

                    return false;
                } else {
                    if ($swapData['iteration'] % 8 == 0) {
                        $db->rpush('2gis_'.$checktype.'_queue', $swapData['param']);
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
        //        \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/2gis/2gis_'.time().'.txt',$content);
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
                        //                    if (isset($initData['phone']))
                        //                        $data['phone'] = new ResultDataField('string','phone',$initData['phone'],'Телефон','Телефон');
                        if (isset($initData['inn'])) {
                            $data['inn'] = new ResultDataField('string', 'inn', $initData['inn'], 'ИНН', 'ИНН');
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
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/2gis/2gis_err_'.\time().'.txt', $content);
                $error = $res['message'];
            } else {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/2gis/2gis_err_'.\time().'.txt', $content);
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

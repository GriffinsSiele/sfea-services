<?php

class GooglePlugin implements PluginInterface
{
    public function getName()
    {
        return 'Google';
    }

    public function getTitle($checktype = '')
    {
        $title = ['' => 'Поиск аккаунта в Google', 'google_phone' => 'Google - проверка телефона на наличие пользователя', 'google_email' => 'Google - проверка email на наличие пользователя', 'google_name' => 'Google - проверка на соответствие фамилии и имени'];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Проверка на наличие аккаунта в Google';
    }

    public function getSessionData(array $params, $sourceid)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $sessionData = null;
        $result = $mysqli->executeQuery("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM session s WHERE sessionstatusid=2 AND sourceid={$sourceid} AND unix_timestamp(now())-unix_timestamp(lasttime)>2 ORDER BY lasttime limit 1");
        if ($result) {
            $row = $result->fetchAssociative();
            if ($row) {
                $sessionData = new \stdClass();
                $sessionData->id = $row['id'];
                $sessionData->code = $row['captcha'];
                $sessionData->token = $row['token'];
                $sessionData->starttime = $row['starttime'];
                $sessionData->lasttime = $row['lasttime'];
                $sessionData->cookies = $row['cookies'];
                $sessionData->proxyid = $row['proxyid'];
                $sessionData->proxy = $row['proxy'];
                $sessionData->proxy_auth = \strlen($row['proxy_auth']) > 1 ? $row['proxy_auth'] : false;
                $mysqli->executeStatement('UPDATE session SET lasttime=now(),used=ifnull(used,0)+1 WHERE id='.$sessionData->id);
                //                $mysqli->query("UPDATE session SET endtime=now(),sessionstatusid=3 WHERE used>=10 AND id=".$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $reqId = $params['_reqId'];
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        $checktype = \substr($initData['checktype'], 7);
        if ('phone' == $checktype && !isset($initData['phone'])) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (телефон)');
            return false;
        }
        if ('email' == $checktype && !isset($initData['email'])) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (email)');
            return false;
        }
        if ('name' == $checktype && (!isset($initData['phone']) && !isset($initData['email']) || !isset($initData['first_name']) || !isset($initData['last_name']))) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (телефон или email, фамилия и имя)');
            return false;
        }
        /*
                    if($checktype=='name'){
                        $rContext->setFinished();
                        $rContext->setError('Сервис временно недоступен');
                        return false;
                    }
        */
        // //////////////////////////////////////////////////////////////////////////////////////////////////
        $ch = $rContext->getCurlHandler();
        $swapData['iteration'] = !isset($swapData['iteration']) ? 1 : $swapData['iteration'] + 1;
        $rContext->setSwapData($swapData);
        if (isset($swapData['url'])) {
            $swapData['session'] = $this->getSessionData($params, 43);
            if (!$swapData['session']) {
                if (isset($swapData['iteration']) && $swapData['iteration'] >= 20) {
                    $rContext->setFinished();
                    $rContext->setError('Нет актуальных сессий');
                } else {
                    !isset($swapData['iteration']) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(3);
                }

                return false;
            }
            \curl_setopt($ch, \CURLOPT_URL, $swapData['url']);
            $header[] = 'Content-Type: application/json';
            \curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);
            if ($swapData['session']->proxy) {
                \curl_setopt($ch, \CURLOPT_PROXY, $swapData['session']->proxy);
                if ($swapData['session']->proxy_auth) {
                    \curl_setopt($ch, \CURLOPT_PROXYUSERPWD, $swapData['session']->proxy_auth);
                    \curl_setopt($ch, \CURLOPT_PROXYAUTH, \CURLAUTH_ANY);
                }
            }
            $rContext->setCurlHandler($ch);

            return true;
        }
        if (!isset($swapData['redis'])) {
            $queue = 'google';
            if ('phone' == $checktype) {
                $params['item'] = $initData['phone'];
            } elseif ('email' == $checktype) {
                $params['item'] = $initData['email'];
            } else {
                $queue = $initData['checktype'];
                $params['item'] = isset($initData['phone']) ? $initData['phone'] : $initData['email'];
                $params['lastname'] = $initData['last_name'];
                $params['firstname'] = $initData['first_name'];
            }
            $params['start'] = \time();
            $params['id'] = $reqId;
            $redis = new Redis();
            //            $redis->connect('127.0.0.1');
            $redis->connect('172.16.1.25'.(3 + $swapData['iteration'] % 2), 6379, 1, null, 100, 1);
            $redis->auth(['n1vTY76fuCT59MH']);
            $swapData['redis'] = $redis;
            $swapData['params'] = $params;
            $swapData['queue'] = $queue;
            if (!$redis->hexists($queue.'_Hash', $reqId.'_'.$params['item'])) {
                if ($redis->llen($queue) > 20) {
                    $redis->close();
                    $rContext->setFinished();
                    $rContext->setError('Слишком много запросов в очереди');
                } else {
                    $redis->rpush($queue, \json_encode($params));
                    $rContext->setSleep(1);
                }
            }
            $rContext->setSwapData($swapData);

            return false;
        } else {
            $content = '';
            $redis = $swapData['redis'];
            $params = $swapData['params'];
            $queue = $swapData['queue'];
            if ($redis->hexists($queue.'_Hash', $reqId.'_'.$params['item'])) {
                $content = $redis->hget($queue.'_Hash', $reqId.'_'.$params['item']);
                $redis->hdel($queue.'_Hash', $reqId.'_'.$params['item']);
                $redis->close();
                unset($swapData['redis']);
            } else {
                if ($swapData['iteration'] > 90) {
                    $error = 'Ошибка при обработке запроса';
                    $rContext->setError($error);
                    $rContext->setFinished();
                    $redis->close();

                    return false;
                } else {
                    if ($swapData['iteration'] % 20 == 0) {
                        $redis->rpush($queue, \json_encode($params));
                    }
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);

                    return false;
                }
            }
        }
        $error = false;
        if (!$content) {
            $error = 'Некорректный ответ сервиса';
        } else {
            //            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/google/google_'.$checktype.'_'.time().'.txt',$content."\r\n".(isset($initData['phone'])?$initData['phone']:$initData['email'])."\r\n".$reqId);
            //            $start = strpos($content,'{');
            //            $content = trim(substr($content,$start,strlen($content)-$start+1));
            $res = \json_decode($content, true);
            //            print_r($res);
            //            echo $checktype."<br />";
            if ('name' == $checktype && $res && isset($res['status']) && 'success' == $res['status']) {
                //                echo "first if<br />";
                $resultData = new ResultDataList();
                if ('exists' == $res['data']) {
                    $data = [];
                    $data['result'] = new ResultDataField('string', 'Result', 'Найден, '.(isset($initData['phone']) ? 'телефон' : 'e-mail').' соответствует фамилии и имени', 'Результат', 'Результат');
                    $data['result_code'] = new ResultDataField('string', 'ResultCode', 'MATCHED', 'Код результата', 'Код результата');
                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                return false;
            } elseif ($res && isset($res['status']) && 'success' == $res['status']) {
                //                echo "here 194<br />";
                //                print_r($res);
                $resultData = new ResultDataList();
                $data = [];
                $android = false;
                $online = false;
                $other = false;
                if (isset($res['data']) && \is_array($res['data'])) {
                    $data['result'] = new ResultDataField('string', 'Result', 'Найден', 'Результат', 'Результат');
                    $data['result_code'] = new ResultDataField('string', 'ResultCode', 'FOUND', 'Код результата', 'Код результата');
                    foreach ($res['data'] as $i => $text) {
                        if (\is_array($text)) {
                            if (isset($text['models'])) {
                                $models = \preg_split('/::/', $text['models']);
                                foreach ($models as $i => $model) {
                                    if ($model) {
                                        $data['device'.$i] = new ResultDataField('string', 'Device', $model, 'Устройство', 'Устройство');
                                    }
                                }
                            }
                        } else {
                            if (\preg_match('/номер телефона, указанный в настройках безопасности аккаунта \\($/', $text, $matches)) {
                                $data['phone'] = new ResultDataField('string', 'Phone', \strtr($res['data'][$i + 1], [' ' => '', '(' => '', ')' => '', '-' => '', '•' => '*']), 'Телефон', 'Телефон');
                            }
                            if (\preg_match('/адрес электронной почты, указанный для аккаунта $/', $text, $matches) || \preg_match('/код подтверждения на адрес $/', $text, $matches)) {
                                $data['email'] = new ResultDataField('string', 'Email', \strtr($res['data'][$i + 1], ['•' => '*']), 'E-mail', 'E-mail');
                            }
                            //                            if (preg_match("/устройстве $/",$text,$matches) || preg_match("/устройству $/",$text,$matches) || preg_match("/устройство $/",$text,$matches)) {
                            //                                $data['device'] = new ResultDataField('string','Device',$res['data'][$i+1],'Устройство','Устройство');
                            //                                $android = true;
                            //                            }
                            if (\preg_match('/устройстве "([^"]+)"\\.$/', $text, $matches)) {
                                $data['device'] = new ResultDataField('string', 'Device', $matches[1], 'Устройство', 'Устройство');
                            }
                            if (\preg_match('/прокрутите вправо/', $text)) {
                                $android = true;
                            }
                            if (\preg_match('/панель уведомлений/', $text)) {
                                $android = true;
                                $online = true;
                            } elseif (\preg_match('/те приложение/', $text)) {
                                $other = true;
                                $online = true;
                            }
                            if (\preg_match('/электронный ключ/', $text)) {
                                $data['securitykey'] = new ResultDataField('string', 'SecurityKey', 'Да', 'Требуется электронный ключ', 'Требуется электронный ключ');
                            }
                            if (\preg_match('/секретный вопрос$/', $text, $matches)) {
                                $data['secret'] = new ResultDataField('string', 'Secret', \strtr($res['data'][$i + 1], ['>' => '']), 'Секретный вопрос', 'Секретный вопрос');
                            }
                            if ($android) {
                                $data['android'] = new ResultDataField('string', 'Android', $android ? 'Да' : 'Нет', 'Зарегистрирован на устройстве c Android', 'Зарегистрирован на устройстве c Android');
                            }
                            if ($other) {
                                $data['other'] = new ResultDataField('string', 'Other', 'Да', 'Зарегистрирован на ином устройстве', 'Зарегистрирован на ином устройстве');
                            }
                            if ($online) {
                                $data['online'] = new ResultDataField('string', 'Online', 'Да', 'Пользователь онлайн и получил уведомление', 'Пользователь онлайн и получил уведомление');
                            }
                            if (!isset($data['gmail']) && \preg_match('/Почта (.*?)$/', $text, $matches) && !(isset($initData['email']) && $matches[1] == $initData['email'])) {
                                $data['gmail'] = new ResultDataField('email', 'gmail', $matches[1], 'Электронная почта gmail', 'Электронная почта gmail');
                            }
                            if (!isset($data['id']) && \preg_match('/GAIA ID (.*?)$/', $text, $matches) && $matches[1]) {
                                $url = 'https://www.google.com/maps/contrib/'.$matches[1];
                                $data['id'] = new ResultDataField('string', 'ID', $matches[1], 'ID пользователя', 'ID пользователя');
                                $data['map'] = new ResultDataField('url', 'Map', $url, 'Карта пользователя', 'Карта пользователя');
                                $swapData['url'] = $url;
                            }
                        }
                    }
                    if (!isset($swapData['url'])) {
                        $resultData->addResult($data);
                    } else {
                        $swapData['data'] = $data;
                        $rContext->setSwapData($swapData);
                    }
                } elseif (isset($res['data']) && \strpos($res['data'], 'exists')) {
                    if (!\strpos($res['data'], 'doesnt')) {
                        $data = [];
                        $data['result'] = new ResultDataField('string', 'Result', 'Найден', 'Результат', 'Результат');
                        $data['result_code'] = new ResultDataField('string', 'ResultCode', 'FOUND', 'Код результата', 'Код результата');
                        $resultData->addResult($data);
                    }
                }
                if (!isset($swapData['url'])) {
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();

                    return false;
                }
            } elseif ($res && isset($res['status']) && 'ERROR' == \strtoupper($res['status'])) {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/google/google_'.$checktype.'_err_'.\time().'.txt', $content."\r\n".(isset($initData['phone']) ? $initData['phone'] : $initData['email'])."\r\n".$reqId);
                $swapData['retry'] = !isset($swapData['retry']) ? 1 : $swapData['retry'] + 1;
                $rContext->setSwapData($swapData);
            } else {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/google/google_'.$checktype.'_err_'.\time().'.txt', $content."\r\n".(isset($initData['phone']) ? $initData['phone'] : $initData['email'])."\r\n".$reqId);
                $error = 'Некорректный ответ';
            }
        }
        if (!$error && isset($swapData['retry']) && $swapData['retry'] >= 10) {
            $error = 'Превышено количество попыток получения ответа';
        }
        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();

            return false;
        }

        return false;
    }

    public function computeRequest(array $params, &$rContext)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        $swapData['iteration'] = !isset($swapData['iteration']) ? 1 : $swapData['iteration'] + 1;
        $rContext->setSwapData($swapData);
        $error = false;
        $content = \curl_multi_getcontent($rContext->getCurlHandler());
        if (!$content) {
            $error = $swapData['iteration'] > 5 && \curl_error($rContext->getCurlHandler());
        } else {
            //            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/google/contrib_'.time().'.html',$content);
            $resultData = new ResultDataList();
            $data = $swapData['data'];
            if (\preg_match('/"Contributions by ([^"]+)"/', $content, $matches)) {
                $data['name'] = new ResultDataField('string', 'Name', $matches[1], 'Имя', 'Имя');
            }
            if (\preg_match('/"Level ([\\d]+) /', $content, $matches)) {
                $data['level'] = new ResultDataField('string', 'Level', $matches[1], 'Уровень', 'Уровень');
            }
            if (\preg_match('/\\| ([\\d\\,]+) Points"/', $content, $matches)) {
                $data['points'] = new ResultDataField('string', 'Points', \strtr($matches[1], [',' => '']), 'Баллов', 'Баллов');
            }
            if (\preg_match('/"Отзывы[^\\,]+\\,([\\d]+)\\,/ui', $content, $matches)) {
                $data['reviews'] = new ResultDataField('string', 'Reviews', $matches[1], 'Отзывов', 'Отзывов');
            }
            if (\preg_match('/"Оценки[^\\,]+\\,([\\d]+)\\,/ui', $content, $matches)) {
                $data['ratings'] = new ResultDataField('string', 'Ratings', $matches[1], 'Оценок', 'Оценок');
            }
            if (\preg_match('/"Фото[^\\,]+\\,([\\d]+)\\,/ui', $content, $matches)) {
                $data['photos'] = new ResultDataField('string', 'Photos', $matches[1], 'Фотографий', 'Фотографий');
            }
            if (\preg_match('/"Видео[^\\,]+\\,([\\d]+)\\,/ui', $content, $matches)) {
                $data['videos'] = new ResultDataField('string', 'Videos', $matches[1], 'Видео', 'Видео');
            }
            if (\preg_match('/"Ответы[^\\,]+\\,([\\d]+)\\,/ui', $content, $matches)) {
                $data['questions'] = new ResultDataField('string', 'Questions', $matches[1], 'Ответов', 'Ответов');
            }
            $resultData->addResult($data);
            $rContext->setResultData($resultData);
            $rContext->setFinished();

            return false;
        }
        if (!$error && isset($swapData['iteration']) && $swapData['iteration'] > 10) {
            $error = 'Превышено количество попыток получения ответа';
        }
        if ($error) {
            $data = $swapData['data'];
            $resultData = new ResultDataList();
            $resultData->addResult($data);
            $rContext->setResultData($resultData);
            //            $rContext->setError($error);
            $rContext->setFinished();

            return false;
        }

        return true;
    }
}

<?php

class TelegramPlugin_old implements PluginInterface
{
    public function __construct()
    {
    }

    public function getName()
    {
        return 'Telegram';
    }

    public function getTitle()
    {
        return 'Поиск в Telegram';
    }

    public function prepareRequest(array $params, &$rContext)
    {
        global $reqId;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration'])) ? 1 : $swapData['iteration'] + 1;

        if (1 == $swapData['iteration']) {
            if (!isset($initData['phone'])) {
                $rContext->setFinished();
                $rContext->setError('Не задан номер телефона');

                return false;
            }
            /*
                        $rContext->setFinished();
                        $rContext->setError('Сервис временно недоступен');
                        return false;
            */
            $params['start'] = \time();
            $params['id'] = $reqId;
            $params['phone'] = $initData['phone'];

            $redis = new Redis();
            $redis->connect('127.0.0.1');

            $redis->auth(['n1vTY76fuCT59MH']);

            $swapData['redis'] = $redis;
            $swapData['params'] = $params;

            $redis->rpush('telegram', \json_encode($params));

            $rContext->setSwapData($swapData);
            $rContext->setSleep(1);

            return false;
        } else {
            $content = '';

            $redis = $swapData['redis'];
            $params = $swapData['params'];

            if ($redis->hexists('telegram_Hash', $reqId.'_'.$params['phone'])) {
                $content = $redis->hget('telegram_Hash', $reqId.'_'.$params['phone']);
                $redis->hdel('telegram_Hash', $reqId.'_'.$params['phone']);
                $redis->close();
            } else {
                if ($swapData['iteration'] > 120) {
                    $error = 'time out';
                    $rContext->setError($error);
                    $rContext->setFinished();

                    $redis->close();

                    return false;
                } else {
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);

                    return false;
                }
            }
        }

        $error = false;

        \file_put_contents('./logs/telegram/telegram_'.(isset($swapData['path']) ? $swapData['path'].'_' : '').\time().'.txt', $content);
        if (!$content) {
            $rContext->setFinished();
            $rContext->setError('empty content error');
        } else {
            $res = \json_decode($content, true);

            if ($res && isset($res['status']) && 'success' == $res['status']) {
                $resultData = new ResultDataList();
                if (\is_array($res['data'])) {
                    $data = [];
                    $data['phone'] = new ResultDataField('string', 'phone', $initData['phone'], 'Телефон', 'Телефон');
                    $name = '';
                    if (isset($res['data']['first_name']) && $res['data']['first_name']) {
                        $data['first_name'] = new ResultDataField('string', 'first_name', $res['data']['first_name'], 'Имя', 'Имя');
                        $name = $res['data']['first_name'];
                    }
                    if (isset($res['data']['last_name']) && $res['data']['last_name']) {
                        $data['last_name'] = new ResultDataField('string', 'last_name', $res['data']['last_name'], 'Фамилия', 'Фамилия');
                        $name = \trim($name.' '.$res['data']['last_name']);
                    }
                    if ($name) {
                        $data['name'] = new ResultDataField('string', 'name', $name, 'Полное имя', 'Полное имя');
                    }
                    if (isset($res['data']['id']) && $res['data']['id']) {
                        $data['id'] = new ResultDataField('string', 'id', $res['data']['id'], 'ID', 'ID');
                    }
                    if (isset($res['data']['username']) && $res['data']['username']) {
                        $data['login'] = new ResultDataField('string', 'login', $res['data']['username'], 'Логин', 'Логин');
                    }
                    if (isset($res['data']['about']) && $res['data']['about']) {
                        $data['about'] = new ResultDataField('string', 'about', $res['data']['about'], 'О себе', 'О себе');
                    }
                    if (isset($res['data']['was_online']) && $res['data']['was_online']) {
                        $data['lastvisited'] = new ResultDataField('string', 'lastvisited', $res['data']['was_online'], 'Был в сети', 'Был в сети');
                    }
                    if (isset($res['data']['state']) && $res['data']['state']) {
                        $data['state'] = new ResultDataField('string', 'state', $res['data']['state'], 'Статус', 'Статус');
                    }
                    if (isset($res['data']['photos']) && \is_array($res['data']['photos'])) {
                        foreach ($res['data']['photos'] as $i => $photo) {
                            $data['photo'.($i ? $i + 1 : '')] = new ResultDataField('image', 'Photo'.($i ? $i + 1 : ''), 'data:image/png;base64,'.$photo, 'Фото'.($i ? $i + 1 : ''), 'Фото'.($i ? $i + 1 : ''));
                        }
                    }
                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                return false;
            } elseif ($res && isset($res['data']) && false !== \strpos($res['data'], 'error')) {
            } elseif ($res && isset($res['data']) && 'unconventional phone' == $res['data']) {
                $error = 'Некорректный номер телефона';
            } elseif ($res && isset($res['data']) && $res['data']) {
                \file_put_contents('./logs/telegram/telegram_err_'.(isset($swapData['path']) ? $swapData['path'].'_' : '').\time().'.txt', $content);
            } else {
                \file_put_contents('./logs/telegram/telegram_err_'.(isset($swapData['path']) ? $swapData['path'].'_' : '').\time().'.txt', $content);
                if ($swapData['iteration'] > 5) {
                    if (\strpos($content, 'nginx')) {
                        $error = 'Сервис временно недоступен';
                    } else {
                        $error = 'Некорректный ответ';
                    }
                }
            }
        }
        $rContext->setSwapData($swapData);

        if (!$error && isset($swapData['iteration']) && $swapData['iteration'] >= 1) {
            $error = 'Превышено количество попыток получения ответа';
        }
        if ($error) {
            //            $rContext->setResultData(new ResultDataList());
            $rContext->setError($error);
            $rContext->setFinished();

            return false;
        }

        //        $rContext->setError('Сервис временно недоступен');
        //        $rContext->setResultData(new ResultDataList());
        //        $rContext->setFinished();

        $rContext->setSleep(1);

        return true;
    }

    public function computeRequest(array $params, &$rContext): void
    {
    }
}

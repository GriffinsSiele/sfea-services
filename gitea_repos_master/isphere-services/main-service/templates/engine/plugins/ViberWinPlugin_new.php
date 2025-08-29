<?php

class ViberWinPlugin_new implements PluginInterface
{
    public function __construct()
    {
    }

    public function getName()
    {
        return 'Viber';
    }

    public function getTitle()
    {
        return 'Поиск в Viber';
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if (!isset($initData['phone'])) {
            $rContext->setFinished();
            $rContext->setError('Не задан номер телефона');

            return false;
        }
        /*
                $rContext->setError("Сервис временно недоступен");
                $rContext->setFinished();
                return false;
        */
        //        if (strlen($initData['phone'])==10)
        //            $initData['phone']='7'.$initData['phone'];
        //        if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
        //            $initData['phone']='7'.substr($initData['phone'],1);

        // //////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $phone = $initData['phone'];
        // $url = 'https://src1.i-sphere.ru/viber/?phone='.$phone;
        // $url = 'https://i-sphere.ru/vbgatenew/?phone='.$phone;
        $url = 'http://localhost:8091/?phone='.$phone;
        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_SSL_VERIFYPEER, false);

        global $total_timeout;
        \curl_setopt($ch, \CURLOPT_TIMEOUT, $total_timeout + 15);

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration'])) ? 1 : $swapData['iteration'] + 1;
        $rContext->setSwapData($swapData);

        $error = false;
        $content = \curl_multi_getcontent($rContext->getCurlHandler());

        if (!$content) {
            $error = /* ($swapData['iteration']>3) && */ \curl_error($rContext->getCurlHandler());
            $rContext->setSleep(1);
        } else {
            \file_put_contents('./logs/viber/viber_'.(isset($swapData['path']) ? $swapData['path'].'_' : '').\time().'.txt', $content);
            $res = \json_decode($content, true);
            /*            if($res && isset($res['status']) && !isset($swapData['path'])){
                            if ($res['status']=='success')  {
                                $swapData['path'] = $res['respDir'];
                            }
                            $rContext->setSleep(3);
                        } else
            */
            if ($res && isset($res['status']) && 'success' == $res['status'] && isset($res['viberstatus']) && ('error' != $res['viberstatus'] || $swapData['iteration'] > 10)) {
                $resultData = new ResultDataList();
                if (1 == $res['viberstatus']) {
                    $data = [];
                    $data['phone'] = new ResultDataField('string', 'phone', $initData['phone'], 'Телефон', 'Телефон');
                    if (isset($res['nic']) && $res['nic']) {
                        $data['name'] = new ResultDataField('string', 'name', $res['nic'], 'Имя', 'Имя');
                    }
                    if (isset($res['base64']) && $res['base64'] && \strlen($res['base64']) > 30) {
                        $data['photo'] = new ResultDataField('image', 'Photo', 'data:image/png;base64,'.$res['base64'], 'Фото', 'Фото');
                        /*
                                                $name = 'logs/viber/'.$initData['phone'].'.jpg';
                                                file_put_contents('./'.$name,base64_decode($res['base64']));
                                                global $serviceurl;
                                                $data['photo'] = new ResultDataField('image', 'Photo', $serviceurl.$name, 'Фото', 'Фото');
                        */
                    }
                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            } elseif ($res && isset($res['data']) && false !== \strpos($res['data'], 'error')) {
            } elseif ($res && isset($res['data']) && 'in queue already' == $res['data'] && $swapData['iteration'] <= 5) {
                $rContext->setSleep(10);

                return true;
            } elseif ($res && isset($res['data']) && 'unconventional phone' == $res['data']) {
                $error = 'Некорректный номер телефона';
            } elseif ($res && isset($res['data']) && $res['data']) {
                \file_put_contents('./logs/viber/viber_err_'.(isset($swapData['path']) ? $swapData['path'].'_' : '').\time().'.txt', $content);
            } else {
                \file_put_contents('./logs/viber/viber_err_'.(isset($swapData['path']) ? $swapData['path'].'_' : '').\time().'.txt', $content);
                if ($swapData['iteration'] > 10) {
                    if (\strpos($content, 'nginx')) {
                        $error = 'Сервис временно недоступен';
                    } else {
                        $error = 'Некорректный ответ';
                    }
                }
            }
        }
        $rContext->setSwapData($swapData);

        if (!$error && isset($swapData['iteration']) && $swapData['iteration'] > 5) {
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
}

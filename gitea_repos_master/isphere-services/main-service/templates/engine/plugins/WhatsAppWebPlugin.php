<?php

class WhatsAppWebPlugin implements PluginInterface
{
    public function __construct()
    {
    }

    public function getName()
    {
        return 'WhatsApp';
    }

    public function getTitle()
    {
        return 'Поиск в WhatsApp';
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
        // $src = (!isset($swapData['iteration']) || ($swapData['iteration']%2))?1:2;
        // $url = 'https://src'.$src.'.i-sphere.ru/whatsapp/?phone='.$phone;
        // $url = 'https://src1.i-sphere.ru/wagatenew/?phone='.$phone;
        // $url = 'https://i-sphere.ru/wagatenew/?phone='.$phone;
        $url = 'http://localhost:8007/?phone='.$phone;
        \curl_setopt($ch, \CURLOPT_URL, $url);
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
            //            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/whatsapp/whatsapp_'.(isset($initData['phone'])?$initData['phone'].'_':'').time().'.txt',$content);
            $res = \json_decode($content, true);
            /*            if($res && isset($res['status']) && !isset($swapData['path'])){
                            if ($res['status']=='success')  {
                                $swapData['path'] = $res['respDir'];
                            }
                            $rContext->setSleep(3);
                        } else
            */
            if ($res && isset($res['status']) && 'success' == $res['status']) {
                $resultData = new ResultDataList();
                $data = [];
                if (0 === \strpos($res['data'], 'https') || 0 === \strpos($res['data'], 'default')) {
                    $data['phone'] = new ResultDataField('string', 'phone', $initData['phone'], 'Телефон', 'Телефон');
                    if (0 === \strpos($res['data'], 'https')) {
                        if (isset($res['base64']) && 'error' != $res['base64']) {
                            $data['photo'] = new ResultDataField('image', 'Photo', 'data:image/png;base64,'.$res['base64'], 'Аватар', 'Аватар');
                        }
                        $data['fullphoto'] = new ResultDataField('image', 'FullPhoto', $res['data'], 'Фото', 'Фото');
                    }

                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                return true;
            } elseif ($res && isset($res['data']) && false !== \strpos($res['data'], 'error')) {
            } elseif ($res && isset($res['data']) && ('unconventional phone' == $res['data'] || 'invalid phone' == $res['data'])) {
                $error = 'Некорректный номер телефона';
            } elseif ($res && isset($res['data']) && $res['data']) {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/whatsapp/whatsapp_err_'.(isset($initData['phone']) ? $initData['phone'].'_' : '').\time().'.txt', $content);
            } else {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/whatsapp/whatsapp_err_'.(isset($initData['phone']) ? $initData['phone'].'_' : '').\time().'.txt', $content);
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

        if (!$error && isset($swapData['iteration']) && $swapData['iteration'] >= 10) {
            $error = 'Превышено количество попыток получения ответа';
        }
        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();

            return false;
        }

        $rContext->setSleep(3);

        return true;
    }
}

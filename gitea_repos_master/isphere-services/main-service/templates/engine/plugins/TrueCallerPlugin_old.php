<?php

class TrueCallerPlugin_old implements PluginInterface
{
    public function getName()
    {
        return 'TrueCaller';
    }

    public function getTitle($checktype = '')
    {
        return 'Поиск телефона в TrueCaller';
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = \substr($initData['checktype'], 11);

        if (!isset($initData['phone'])) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (телефон)');

            return false;
        }

        //        if (strlen($initData['phone'])==10)
        //            $initData['phone']='7'.$initData['phone'];
        //        if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
        //            $initData['phone']='7'.substr($initData['phone'],1);
        /*
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
                return false;
        */
        // //////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $phone = $initData['phone'];
        $url = 'http://global.d0o.ru/api/truecaller?token=94def889184bb7b8de213422790e40f7&phone='.$phone;
        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_TIMEOUT, 20);
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration'])) ? 1 : $swapData['iteration'] + 1;
        $error = ($swapData['iteration'] > 2) && \curl_error($rContext->getCurlHandler());
        if (!$error) {
            $content = \curl_multi_getcontent($rContext->getCurlHandler());
            //            file_put_contents('./logs/truecaller/truecaller_'.time().'.txt',$content);

            $res = \json_decode($content, true);
            if ($res && isset($res['data'])) {
                $resultData = new ResultDataList();
                $row = $res['data'];
                //                foreach($res['data'] as $row) {
                $data = [];
                if (isset($row['name'])) {
                    $data['name'] = new ResultDataField('string', 'Name', $row['name'], 'Имя', 'Имя');
                }

                if (\count($data)) {
                    $resultData->addResult($data);
                }
                //                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            } elseif ($res && isset($res['message'])) {
                $error = $res['message'];
            } else {
                if ($res) {
                    $error = 'Некорректный ответ';
                }
            }
        }

        if (!$error && isset($swapData['iteration']) && $swapData['iteration'] > 2) {
            $error = 'Превышено количество попыток получения ответа';
        }

        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();

            return false;
        }

        return true;
    }
}

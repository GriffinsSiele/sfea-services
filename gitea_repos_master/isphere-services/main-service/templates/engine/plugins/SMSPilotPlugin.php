<?php

class SMSPilotPlugin implements PluginInterface
{
    private $session = '702O825T8PERL66536E82HED5W1Z85VQ32562287FCC8HOQ6FW99WGQ3A4MFTI3B';

    public function getName()
    {
        return 'HLR';
    }

    public function getTitle()
    {
        return 'Проверка доступности абонента мобильной связи';
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
        //        if (strlen($initData['phone'])==10)
        //            $initData['phone']='7'.$initData['phone'];
        //        if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
        //            $initData['phone']='7'.substr($initData['phone'],1);
        /*
                if(substr($initData['phone'],0,2)!='79')
                {
                    $rContext->setFinished();
                    $rContext->setError('Поиск производится только по мобильным телефонам в коде 9xx');

                    return false;
                }
        */
        /*
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
                return false;
        */
        // //////////////////////////////////////////////////////////////////////////////////////////////////
        $ch = $rContext->getCurlHandler();
        $params = ['apikey' => $this->session, 'format' => 'json'];
        if (!isset($swapData['id'])) {
            $params['send'] = 'HLR';
            $params['to'] = $initData['phone'];
        } else {
            $params['check'] = $swapData['id'];
        }
        $url = 'https://smspilot.ru/api.php?'.\http_build_query($params);
        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(array $params, &$rContext)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        $swapData['iteration'] = !isset($swapData['iteration']) ? 1 : $swapData['iteration'] + 1;
        $error = $swapData['iteration'] > 5 && \curl_error($rContext->getCurlHandler());
        if (!$error) {
            $content = \curl_multi_getcontent($rContext->getCurlHandler());
            //            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/smspilot/'.(!isset($swapData['id'])?'send':'check').'_'.time().'.txt', $content."\n\n".$swapData['iteration']);
            $ares = \json_decode($content, true);
            if ($ares && !isset($swapData['id']) && isset($ares['send'][0]['server_id']) && (int) $ares['send'][0]['server_id']) {
                $swapData['id'] = (int) $ares['send'][0]['server_id'];
            } elseif ($ares && isset($swapData['id']) && isset($ares['check'][0]['status']) && (0 == (int) $ares['check'][0]['status'] || 1 == (int) $ares['check'][0]['status'])) {
                --$swapData['iteration'];
                $rContext->setSwapData($swapData);
                $rContext->setSleep(20);

                return true;
            } elseif ($ares && isset($swapData['id']) && isset($ares['check'][0]['status'])) {
                $data['phone'] = new ResultDataField('string', 'PhoneNumber', $initData['phone'], 'Номер', 'Номер телефона');
                $data['hlr_status'] = new ResultDataField('string', 'HLRStatus', (int) $ares['check'][0]['status'] > 0 ? 'Доступен' : 'Недоступен', 'Статус', 'Статус абонента');
                $resultData = new ResultDataList();
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                return true;
            } elseif ($ares && isset($ares['error'])) {
                $error = $ares['error']['description'];
            } else {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/smspilot/'.(!isset($swapData['id']) ? 'send' : 'get').'_err_'.\time().'.txt', $content."\n\n".$swapData['iteration']);
                $error = 'Некорректный ответ сервиса';
            }
        }
        $rContext->setSwapData($swapData);
        $rContext->setSleep(5);
        if (isset($swapData['iteration']) && $swapData['iteration'] >= 5) {
            $rContext->setFinished();
            $rContext->setError('' == $error ? 'Превышено количество попыток получения ответа' : $error);

            return false;
        }

        return true;
    }
}

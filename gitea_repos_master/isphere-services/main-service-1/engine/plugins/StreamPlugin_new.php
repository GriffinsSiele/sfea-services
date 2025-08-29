<?php

class StreamPlugin implements PluginInterface
{
    private $session = 'dbb8244e772f35ebd4c58931fb5f5ba6';

    public function getName()
    {
        return 'HLR';
    }

    public function getTitle()
    {
        return 'Проверка доступности абонента мобильной связи';
    }

    private $hlrStatus = array(
        0 => 'Доступен',
        42 => 'Недоступен',
    );

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if(!isset($initData['phone']))
        {
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
        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $params = array(
            'sessionId' => $this->session,
        );
        $url = 'https://gateway.api.sc/rest/';
        if (!isset($swapData['id'])) {
            $params['destinationAddress'] = $initData['phone'];
            $url .= 'Send/SendHLR/';
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            $header = array(
                'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        } else {
            $params['messageId'] = $swapData['id'];
            $url .= 'State/HLR/?'.http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);      

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $error = ($swapData['iteration']>5) && curl_error($rContext->getCurlHandler());
        if (!$error) {
            $content = curl_multi_getcontent($rContext->getCurlHandler());
            file_put_contents('./logs/stream/'.(!isset($swapData['id'])?'send':'get').'_'.time().'.txt', $content."\n\n".$swapData['iteration']);
            $ares = json_decode($content, true);

            if ($ares && !isset($swapData['id']) && isset($ares[0]) && intval($ares[0])) {
                $swapData['id'] = intval($ares[0]);
            } elseif ($ares && isset($swapData['id']) && isset($ares['State']) && intval($ares['State'])<0) {
                $swapData['iteration']--;
            } elseif ($ares && isset($swapData['id']) && isset($ares['State'])) {
                $data['phone'] = new ResultDataField('string','PhoneNumber', $initData['phone'], 'Номер', 'Номер телефона');
                $data['hlr_status'] = new ResultDataField('string','HLRStatus', $this->hlrStatus[$ares['State']], 'Статус', 'Статус абонента');
                if (isset($ares['PORT']))
                    $data['ported'] = new ResultDataField('string','Ported', $ares['PORT'], 'Перенос номера', 'Перенос номера');
                if (isset($ares['ERROR'])) {
                    $error = $ares['ERROR'];
                }

                $resultData = new ResultDataList();
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                return true;
            } elseif ($ares && isset($ares[0]) && $ares[0]=='') {
                $error = 'Номер не поддерживается';
            } elseif ($ares && isset($ares['Desc']) && $ares['Desc']=='phone_code_user') {
                $error = 'Страна не поддерживается';
            } else {
                file_put_contents('./logs/stream/'.(!isset($swapData['id'])?'send':'get').'_err_'.time().'.txt', $content."\n\n".$swapData['iteration']);
                $error = 'Некорректный ответ сервиса';
            }
        }

        $rContext->setSwapData($swapData);
        $rContext->setSleep(1);

        if(isset($swapData['iteration']) && $swapData['iteration']>=5)
        {
            $rContext->setFinished();
            $rContext->setError($error==''?'Превышено количество попыток получения ответа':$error);

            return false;
        }

        return true;
    }
}

?>
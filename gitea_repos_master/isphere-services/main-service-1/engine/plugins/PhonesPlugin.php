<?php

class PhonesPlugin implements PluginInterface
{
    public function getName()
    {
        return 'Phones';
    }

    public function getTitle($checktype = '')
    {
        return 'Поиск дополнительных телефонов';
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],10);

        if(!isset($initData['phone'])) {
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
        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $phone = $initData['phone'];
        $url = 'http://global.d0o.ru/api/adjacent?token=94def889184bb7b8de213422790e40f7&phone='.$phone;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_TIMEOUT,10);
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $error = false; //$swapData['iteration']>2 ? curl_error($rContext->getCurlHandler()) : false;

        if(!$error) {
            $content = curl_multi_getcontent($rContext->getCurlHandler());
//            file_put_contents('./logs/phones/adjacent_'.time().'.txt',$content);

            $res = json_decode($content, true);               
            if($res && isset($res['data'])){
                $resultData = new ResultDataList();
                foreach($res['data'] as $row) {
                    $data = array();
                    $data['phone'] = new ResultDataField('phone','Phone',$row,'Телефон','Телефон');
                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                return true;
            } elseif($res && isset($res['message'])) {
                $error = $res['message'];
                if (strpos($error,'SQLSTATE')!==false) $error='Внутренняя ошибка источника';
            } else {
                if ($res) $error = "Некорректный ответ";
            }
        }
        $rContext->setSwapData($swapData);

        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>=2)
            $error='Превышено количество попыток получения ответа';

        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();
            return false;
        }

        return true;
    }

}

?>
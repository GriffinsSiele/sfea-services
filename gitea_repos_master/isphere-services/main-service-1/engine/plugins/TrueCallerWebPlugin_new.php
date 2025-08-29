<?php

class TrueCallerWebPlugin implements PluginInterface
{
    public function getName()
    {
        return 'TrueCaller';
    }

    public function getTitle($checktype = '')
    {
        return 'Поиск телефона в TrueCaller';
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],11);

        if(!isset($initData['phone'])) {
            $rContext->setFinished();
//            $rContext->setError('Указаны не все обязательные параметры (телефон)');

            return false;
        }

//        if (strlen($initData['phone'])==10)
//            $initData['phone']='7'.$initData['phone'];
//        if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
//            $initData['phone']='7'.substr($initData['phone'],1);

        if(isset($initData['phone']) && substr($initData['phone'],0,1)!='7'){
            $rContext->setFinished();
            $rContext->setError('Поиск временно производится только по российским телефонам');
            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $phone = $initData['phone'];
        $src = 2; //(!isset($swapData['iteration']) || ($swapData['iteration']%2))?1:2;
        $url = 'https://src'.$src.'.i-sphere.ru/true/?phone='.substr($phone,1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $error = ($swapData['iteration']>2) && curl_error($rContext->getCurlHandler());
        if(!$error) {
            $content = curl_multi_getcontent($rContext->getCurlHandler());
            file_put_contents('./logs/truecaller/truecallerweb_'.time().'.txt',$content);

            $res = json_decode($content, true);               
            if($res && isset($res['status']) && $res['status']=='success' && isset($res['data'])){
                if (is_array($res['data'])) {
                    $resultData = new ResultDataList();
                    if (is_array($res['data']) && sizeof($res['data'])>3) {
                        $data = array();
                        $data['phone'] = new ResultDataField('string','Phone',$initData['phone'],'Телефон','Телефон');
                        if (isset($res['data'][0]) && trim($res['data'][0]))
                             $data['name'] = new ResultDataField('string','Name',trim($res['data'][0]),'Имя','Имя');
                        if (isset($res['data']['name']) && trim($res['data']['name']))
                             $data['name'] = new ResultDataField('string','Name',trim($res['data']['name']),'Имя','Имя');
                        if (isset($res['data']['email']) && $res['data']['email'])
                             $data['email'] = new ResultDataField('email','Email',trim($res['data']['email']),'E-mail','E-mail');
                        if (sizeof($data)) {
                            $data['phone'] = new ResultDataField('string','Phone',$initData['phone'],'Телефон','Телефон');
                            $resultData->addResult($data);
                        }
                    }

                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                } elseif ($res['data']='No result found') {
                    $resultData = new ResultDataList();
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                }
            } elseif($res && isset($res['status']) && $res['status']=='error'){
                file_put_contents('./logs/truecaller/truecallerweb_err_'.(isset($swapData['path'])?$swapData['path'].'_':'').time().'.txt',$content);
            } else {
                file_put_contents('./logs/truecaller/truecallerweb_err_'.(isset($swapData['path'])?$swapData['path'].'_':'').time().'.txt',$content);
                if ($swapData['iteration']>1) {
                    if (strpos($content,'nginx')) {
                        $error = "Сервис временно недоступен";
                    } else {
                        $error = "Некорректный ответ";
                    }
                }
            }
        }
        $rContext->setSwapData($swapData);

        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>=3) {
            $error='Превышено количество попыток получения ответа';
        }
        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();
            return false;
        }

        $rContext->setSleep(1);

        return true;
    }
}

?>
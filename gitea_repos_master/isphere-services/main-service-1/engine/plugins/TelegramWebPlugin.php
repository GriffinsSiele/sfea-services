<?php

class TelegramWebPlugin implements PluginInterface
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
/*
        $rContext->setError("Сервис временно недоступен");
        $rContext->setFinished();
        return false;
*/
//        if (strlen($initData['phone'])==10)
//            $initData['phone']='7'.$initData['phone'];
//        if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
//            $initData['phone']='7'.substr($initData['phone'],1);

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $phone = $initData['phone'];
        $url = 'https://src2.i-sphere.ru/telegramnew/?phone='.$phone;
        curl_setopt($ch, CURLOPT_URL, $url);

        global $total_timeout;
        curl_setopt($ch, CURLOPT_TIMEOUT,$total_timeout+15);

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $rContext->setSwapData($swapData);

        $error = false;
        $content = curl_multi_getcontent($rContext->getCurlHandler());

        if (!$content) {
            $error = /*($swapData['iteration']>3) && */ curl_error($rContext->getCurlHandler());
            $rContext->setSleep(1);
        } else {
//            file_put_contents('./logs/telegram/telegram_'.(isset($swapData['path'])?$swapData['path'].'_':'').time().'.txt',$content);
            $res = json_decode($content, true);
/*            if($res && isset($res['status']) && !isset($swapData['path'])){
                if ($res['status']=='success')  {
                    $swapData['path'] = $res['respDir'];
                }
                $rContext->setSleep(3);
            } else
*/
            if($res && isset($res['status']) && $res['status']=='success'){
                $resultData = new ResultDataList();
                if (is_array($res['data'])) {
                    $data = array();
                    $data['phone'] = new ResultDataField('string','phone',$initData['phone'],'Телефон','Телефон');
                    if(isset($res['data']['name']) && $res['data']['name']){
                        $data['name'] = new ResultDataField('string','name',$res['data']['name'],'Имя','Имя');
                    }
                    if(isset($res['data']['nic']) && $res['data']['nic']){
                        $data['login'] = new ResultDataField('string','login',$res['data']['nic'],'Логин','Логин');
                    }
                    if(isset($res['data']['about']) && $res['data']['about']){
                        $data['about'] = new ResultDataField('string','about',$res['data']['about'],'О себе','О себе');
                    }
                    if(isset($res['data']['lastTime']) && $res['data']['lastTime']){
                        $data['lastvisited'] = new ResultDataField('string','lastvisited',$res['data']['lastTime'],'Был в сети','Был в сети');
                    }
                    if (isset($res['data']['img']) && $res['data']['img']) {
                        $data['photo'] = new ResultDataField('image','Photo','data:image/png;base64,'.$res['data']['img'],'Фото','Фото');
                    }
                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                return true;
            } elseif($res && isset($res['data']) && strpos($res['data'],'error')!==false){
            } elseif($res && isset($res['data']) && $res['data']=="unconventional phone") {
                $error = "Некорректный номер телефона";
            } elseif($res && isset($res['data']) && $res['data']=="in queue already") {
                $error = "Запрос по этому телефону уже обрабатывается";
            } elseif($res && isset($res['data']) && $res['data']=="no room") {
                $error = "Нет доступных аккаунтов для выполнения запроса";
            } elseif($res && isset($res['data']) && $res['data']) {
                file_put_contents('./logs/telegram/telegram_err_'.(isset($swapData['path'])?$swapData['path'].'_':'').time().'.txt',$content);
            } else {
                file_put_contents('./logs/telegram/telegram_err_'.(isset($swapData['path'])?$swapData['path'].'_':'').time().'.txt',$content);
                if ($swapData['iteration']>5) {
                    if (strpos($content,'nginx')) {
                        $error = "Сервис временно недоступен";
                    } else {
                        $error = "Некорректный ответ";
                    }
                }
            }
        }
        $rContext->setSwapData($swapData);

        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>10) {
            $error='Превышено количество попыток получения ответа';
        }
        if ($error) {
//            $rContext->setResultData(new ResultDataList());
            $rContext->setError($error);
            $rContext->setFinished();
            return false;
        }
        $rContext->setSleep(1);
        return true;
    }
}

?>
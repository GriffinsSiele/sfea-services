<?php

class SypexGeoPlugin implements PluginInterface
{
    public function getName()
    {
        return 'SypexGeo';
    }

    public function getTitle()
    {
        return 'Определение города/страны по IP';
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if(!isset($initData['ip']))
        {
            $rContext->setFinished();
            $rContext->setError('Не задан IP-адрес');

            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $url = 'https://api.sypexgeo.net/json/'.$initData['ip'];
        if ($ch){
          curl_setopt($ch, CURLOPT_URL, $url);
          $rContext->setCurlHandler($ch);
        }
        return true;
    }

    public function computeRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
        $rContext->setSwapData($swapData);

        $error = false;
        $curl_error = curl_error($rContext->getCurlHandler());
        if($curl_error && $swapData['iteration']>3)
        {
            $rContext->setFinished();
            $rContext->setError($curl_error==''?'Превышено количество попыток получения ответа':$curl_error);
            return false;
        }

        $content = curl_multi_getcontent($rContext->getCurlHandler());
//        file_put_contents('./logs/sypexgeo/sypexgeo_'.time().'.json',$content);
        $res = json_decode($content, true);

        if($res && isset($res['ip'])){
             $resultData = new ResultDataList();
             $data = array();

             if(isset($res['country']) && $res['country']['name_ru']){
                 $data['country_code'] = new ResultDataField('string','country_code',$res['country']['iso'],'Код страны','Код страны');
                 $data['country'] = new ResultDataField('string','country',$res['country']['name_ru'],'Страна','Страна');
             }
             if(isset($res['region']['name_ru']) && $res['region']['name_ru']){
                 $data['region'] = new ResultDataField('string','region',$res['region']['name_ru'],'Регион','Регион');
             }
             if(isset($res['city']) && $res['city']['name_ru']){
                 $data['city'] = new ResultDataField('string','city',$res['city']['name_ru'],'Город','Город');
                 if (isset($res['city']['lat']) && isset($res['city']['lon'])) {
                     $map = array(array('coords' => array(floatval($res['city']['lat']),floatval($res['city']['lon'])), 'text' => strval($res['city']['name_ru'])));
                     $data['coords'] = new ResultDataField('map','Location',strtr(json_encode($map,JSON_UNESCAPED_UNICODE),array("},{"=>"},\n{")),'Местоположение','Местоположение');
                 }
             }
             if (sizeof($data))
                 $resultData->addResult($data);
             $rContext->setResultData($resultData);
             $rContext->setFinished();						      
        } elseif($res && isset($res['error']) && $res['error']){
             $error = $res['error'];
        } else {
             $error = 'Ошибка обработки ответа';
        }
       
        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>10)
            $error='Превышено количество попыток получения ответа';

        if($error && isset($swapData['iteration']) && $swapData['iteration']>3) {
            $rContext->setError($error);
            $rContext->setFinished();
            return false;
        }

        return true;
    }
}

?>
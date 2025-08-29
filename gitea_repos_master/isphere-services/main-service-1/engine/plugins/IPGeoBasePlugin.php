<?php

class IPGeoBasePlugin implements PluginInterface
{
    public function getName()
    {
        return 'IPGeoBase';
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

        $url = 'http://ipgeobase.ru:7020/geo?ip='.$initData['ip'];
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
//        file_put_contents('./logs/ipgeobase/ipgeobase_'.time().'.xml',$content);

	if(strpos($content,'temporarily unavailable')){
            $error = 'Сервис временно недоступен';
	} elseif(@ $xml = simplexml_load_string($content) ){
             $resultData = new ResultDataList();

             if (isset($xml->ip) && !isset($xml->ip->message)) {
                 $data = array();
                 $data['country_code'] = new ResultDataField('string','country_code',$xml->ip->country,'Код страны','Код страны');
                 $data['region'] = new ResultDataField('string','region',$xml->ip->region,'Регион','Регион');
                 $data['city'] = new ResultDataField('string','city',$xml->ip->city,'Город','Город');
                 if (isset($xml->ip->lat) && isset($xml->ip->lng)) {
                     $map = array(array('coords' => array(floatval($xml->ip->lat),floatval($xml->ip->lng)), 'text' => strval($xml->ip->city)));
                     $data['coords'] = new ResultDataField('map','Location',strtr(json_encode($map,JSON_UNESCAPED_UNICODE),array("},{"=>"},\n{")),'Местоположение','Местоположение');
                 }
                 $resultData->addResult($data);
             }
             $rContext->setResultData($resultData);
             $rContext->setFinished();						      
        } else {
             $error = 'Ошибка обработки ответа';
        }
       
        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>10)
            $error='Превышено количество попыток получения ответа';

        if($error && isset($swapData['iteration']) && $swapData['iteration']>5) {
            $rContext->setError($error);
            $rContext->setFinished();
            return false;
        }

        return true;
    }
}

?>
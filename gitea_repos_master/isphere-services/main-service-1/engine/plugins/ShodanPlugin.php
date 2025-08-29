<?php

class ShodanPlugin implements PluginInterface
{
    public function getName()
    {
        return 'Shodan';
    }

    public function getTitle()
    {
        return 'Поиск в Shodan';
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

        $url = 'https://api.shodan.io/shodan/host/'.$initData['ip'].'?key=czRm5qCElRJOX1yS7KvTbaAfNJSeL0b9';
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
//        file_put_contents('./logs/shodan/shodan_'.time().'.json',$content);
        $res = json_decode($content, true);

        if($res && isset($res['ip'])){
             $resultData = new ResultDataList();
             $data = array();

             if(isset($res['country_code'])){
                 $data['country_code'] = new ResultDataField('string','country_code',$res['country_code'],'Код страны','Код страны');
                 $data['country'] = new ResultDataField('string','country',$res['country_name'],'Страна','Страна');
             }
             if(isset($res['city'])){
                 $data['city'] = new ResultDataField('string','city',$res['city'],'Город','Город');
             }
             if (isset($res['latitude']) && isset($res['longitude']) && $res['latitude'] && $res['longitude']) {
                 $map = array(array('coords' => array(floatval($res['latitude']),floatval($res['longitude'])), 'text' => ''));
                 $data['coords'] = new ResultDataField('map','Location',strtr(json_encode($map,JSON_UNESCAPED_UNICODE),array("},{"=>"},\n{")),'Местоположение','Местоположение');
             }
             if (isset($res['org'])) {
                 $data['organization'] = new ResultDataField('string','organization',$res['org'],'Организация','Организация');
             }
             if (isset($res['isp'])) {
                 $data['provider'] = new ResultDataField('string','provider',$res['isp'],'Провайдер','Провайдер');
             }
             if (isset($res['asn'])) {
                 $data['asn'] = new ResultDataField('string','asn',$res['asn'],'ASN','ASN');
             }
             if (isset($res['hostnames']) && sizeof($res['hostnames'])) {
                 $data['hostnames'] = new ResultDataField('string','hostnames',implode(',',$res['hostnames']),'Хосты','Хосты');
             }
             if (isset($res['os'])) {
                 $data['os'] = new ResultDataField('string','os',$res['os'],'Операционная система','Операционная система');
             }
             if (isset($res['ports']) && sizeof($res['ports'])) {
                 $data['ports'] = new ResultDataField('string','ports',implode(',',$res['ports']),'Открытые порты','Открытые порты');
             }
             if (isset($res['tags']) && sizeof($res['tags'])) {
                 $data['tags'] = new ResultDataField('string','tags',implode(',',$res['tags']),'Признаки','Признаки');
             }
             $data['recordtype'] = new ResultDataField('string','recordtype','ip','Тип записи','Тип записи');
             $resultData->addResult($data);

             if (isset($res['data']) && sizeof($res['data'])) {
                 $data = array();
                 foreach ($res['data'] as $rec) {
                     $data['port'] = new ResultDataField('string','port',$rec['port'],'Порт','Порт');
                     $data['transport'] = new ResultDataField('string','transport',$rec['transport'],'Транспортный протокол','Транспортный протокол');
                     if (isset($rec['_shodan']['module']))
                         $data['service'] = new ResultDataField('string','service',$rec['_shodan']['module'],'Сервис','Сервис');
                     if (isset($rec['product']))
                         $data['product'] = new ResultDataField('string','product',$rec['product'],'Продукт','Продукт');
                     if (isset($rec['version']))
                         $data['version'] = new ResultDataField('string','version',$rec['version'],'Версия','Версия');
                     if (isset($rec['tags']))
                         $data['tags'] = new ResultDataField('string','tags',implode(',',$rec['tags']),'Признаки','Признаки');
//                     if (isset($rec['data']))
//                         $data['data'] = new ResultDataField('string','data',htmlspecialchars($rec['data']),'Данные ответа','Данные ответа');
                 }
                 $data['recordtype'] = new ResultDataField('string','recordtype','service','Тип записи','Тип записи');
                 $resultData->addResult($data);
             }
             $rContext->setResultData($resultData);
             $rContext->setFinished();						      
        } elseif($res && isset($res['error']) && $res['error']){
             if (strpos($res['error'],'No information')!==false) {
                 $resultData = new ResultDataList();
                 $rContext->setResultData($resultData);
                 $rContext->setFinished();						      
             } else {
                 $error = $res['error'];
             }
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
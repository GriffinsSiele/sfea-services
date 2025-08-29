<?php

class CensysPlugin implements PluginInterface
{
    public function getName()
    {
        return 'Censys';
    }

    public function getTitle()
    {
        return 'Поиск в Censys';
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

        $url = 'https://search.censys.io/api/v2/hosts/'.$initData['ip'];
        if ($ch){
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); 
//            curl_setopt($ch, CURLOPT_USERPWD, 'afb2d686-6073-44db-92e7-34c96ffc8833:CLzsCMfcaycchdvki5MZgTCFQQznZcY4');
            curl_setopt($ch, CURLOPT_USERPWD, 'd7abf356-4a81-4f2e-b435-9eb9a995ffd8:1jnSwn4x9cf3TCVDiVJB80qeYJ9aXjFh');
//            curl_setopt($ch, CURLOPT_USERPWD, rand(0,1)?'afb2d686-6073-44db-92e7-34c96ffc8833:CLzsCMfcaycchdvki5MZgTCFQQznZcY4':'d7abf356-4a81-4f2e-b435-9eb9a995ffd8:1jnSwn4x9cf3TCVDiVJB80qeYJ9aXjFh');

//            curl_setopt($ch,CURLOPT_PROXY,'tcp://185.165.194.149:8000');
//            curl_setopt($ch,CURLOPT_PROXYUSERPWD,'0garP3:XHXkDG'); 
//            curl_setopt($ch,CURLOPT_PROXYAUTH,CURLAUTH_ANY); 

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
            $rContext->setError($curl_error==''?'Превышено количество попыток получения ответа':$curl_error.' (curl errno = '.curl_errno().')');
            return false;
        }

        $content = curl_multi_getcontent($rContext->getCurlHandler());
//        file_put_contents('./logs/censys/censys_'.time().'.json',$content);
        $res = json_decode($content, true);

        if($res && isset($res['result']['ip'])){
             $res = $res['result'];
             $resultData = new ResultDataList();
             $data = array();

             $locationtext = '';
             if(isset($res['location']['country'])){
                 $data['country_code'] = new ResultDataField('string','country_code',$res['location']['country_code'],'Код страны','Код страны');
                 $data['country'] = new ResultDataField('string','country',$res['location']['country'],'Страна','Страна');
                 $locationtext = $res['location']['country'];
             }
             if(isset($res['location']['province'])){
                 $data['province'] = new ResultDataField('string','province',$res['location']['province'],'Регион','Регион');
                 $locationtext = $res['location']['province'];
             }
             if(isset($res['location']['city'])){
                 $data['city'] = new ResultDataField('string','city',$res['location']['city'],'Город','Город');
                 $locationtext = $res['location']['city'];
             }
             if(isset($res['location']['timezone'])){
                 $data['timezone'] = new ResultDataField('string','timezone',$res['location']['timezone'],'Временная зона','Временная зона');
                 $locationtext = $res['location']['timezone'];
             }
             if (isset($res['location']['coordinates']['latitude']) && isset($res['location']['coordinates']['longitude']) && $res['location']['coordinates']['latitude'] && $res['location']['coordinates']['longitude']) {
                 $map = array(array('coords' => array(floatval($res['location']['coordinates']['latitude']),floatval($res['location']['coordinates']['longitude'])), 'text' => $locationtext));
                 $data['coords'] = new ResultDataField('map','Location',strtr(json_encode($map,JSON_UNESCAPED_UNICODE),array("},{"=>"},\n{")),'Местоположение','Местоположение');
             }
             if (isset($res['autonomous_system']['asn'])) {
                 $data['asn'] = new ResultDataField('string','asn',$res['autonomous_system']['asn'],'ASN','ASN');
             }
             if (isset($res['autonomous_system']['name'])) {
                 $data['organization'] = new ResultDataField('string','organization',$res['autonomous_system']['name'],'Организация','Организация');
             }
             if (isset($res['dns']['names']) && sizeof($res['dns']['names'])) {
                 $data['hostnames'] = new ResultDataField('string','hostnames',implode(',',$res['dns']['names']),'Хосты','Хосты');
             }
/*
             if (isset($res['tags']) && sizeof($res['tags'])) {
                 $data['tags'] = new ResultDataField('string','tags',implode(',',$res['tags']),'Признаки','Признаки');
             }
*/
             $data['recordtype'] = new ResultDataField('string','recordtype','ip','Тип записи','Тип записи');
             $resultData->addResult($data);

             if (isset($res['services']) && sizeof($res['services'])) {
                 $data = array();
                 foreach ($res['services'] as $service) {
                     $data['port'] = new ResultDataField('string','port',$service['port'],'Порт','Порт');
                     $data['service'] = new ResultDataField('string','service',$service['service_name'],'Сервис','Сервис');
                     $data['transport'] = new ResultDataField('string','transport',$service['transport_protocol'],'Транспортный протокол','Транспортный протокол');
                     $data['recordtype'] = new ResultDataField('string','recordtype','service','Тип записи','Тип записи');
                     $resultData->addResult($data);
                 }
             }
             $rContext->setResultData($resultData);
             $rContext->setFinished();						      
        } elseif($res && isset($res['error']) && $res['error']){
             if (strpos($res['error'],'don\'t know')!==false) {
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
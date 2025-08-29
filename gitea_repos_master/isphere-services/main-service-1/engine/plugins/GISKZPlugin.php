<?php

class GISKZPlugin implements PluginInterface
{
    private $phone2reg = array();
    private $reg2gis = array();

    private $days_ru = array(
                               'Mon' => 'Пн',
                               'Tue' => 'Вт',
                               'Wed' => 'Ср',
                               'Thu' => 'Чт',
                               'Fri' => 'Пт',
                               'Sat' => 'Сб',
                               'Sun' => 'Вс',
                       );

    public function __construct()
    {
//           require(__DIR__.'/list.txt');
	   $tmparray = file(__DIR__.'/list.txt');
	   foreach($tmparray as $val){
	         $tmparr = explode('|', trim($val));
		 $tmpcodes = explode(',', $tmparr[3]);
		 $tmpreg = explode(',', $tmparr[2]);
		 foreach($tmpcodes as $v){
		        if(!isset($this->phone2reg[$v])){
			        $this->phone2reg[$v][] = $tmparr[0];
			}
			else{
		                if(!in_array($tmparr[0], $this->phone2reg[$v])){
		                         $this->phone2reg[$v][] = $tmparr[0];
			        }
		        }
		 }
		 foreach($tmpreg as $vv){
		        if(!isset($this->reg2gis[$vv])){
			         $this->reg2gis[$vv][] = $tmparr[0];
			}
			else{
		                if(!in_array($tmparr[0], $this->reg2gis[$vv])){
		                      $this->reg2gis[$vv][] = $tmparr[0];
			        }
			}
		 }
	   }
//	   print_r($this->phone2reg);
//	   print_r($this->reg2gis);
    }

    public function getName()
    {
        return '2GIS';
    }

    public function getTitle()
    {
        return 'Поиск в справочнике 2ГИС';
    }

    public function prepareRequest(&$rContext)
    {
        global $mysqli;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if(!isset($initData['phone']))
        {
            $rContext->setFinished();
            $rContext->setError('Не задан номер телефона');

            return false;
        }

        if (isset($initData['phone'])) {
            if (strlen($initData['phone'])==10)
                $initData['phone']='7'.$initData['phone'];
            if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
                $initData['phone']='7'.substr($initData['phone'],1);

            if(substr($initData['phone'],0,1)!='7'){
                $rContext->setFinished();
                $rContext->setError('Поиск производится только по казахстанским телефонам');
                return false;
            }
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////


        $ch = $rContext->getCurlHandler();

        $url = 'http://beta.2gis.kz/search/'.$initData['phone'];
//	echo $url;
//	exit;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $rContext->setSwapData($swapData);

        $curlError = curl_error($rContext->getCurlHandler());
        if($curlError && ($swapData['iteration']>10)){
            $rContext->setFinished();
            $rContext->setError($curlError==''? 'Unknown Error' : $curlError); 
            return false;
        } else {
            if(!$curlError)
                $content = curl_multi_getcontent($rContext->getCurlHandler());
	}

//        file_put_contents('./logs/2gis/2gis'.time().'.html',$content);
        $resultData = new ResultDataList();

        if (preg_match("/var initialState \= (\{.*?\}); /",$content,$matches)) {
                $gisres = json_decode($matches[1], true);
                if(isset($gisres['data']['entity']['profile'])){
	            foreach($gisres['data']['entity']['profile'] as $profile){
                        $orgdata = $profile['data'];
                        if (array_key_exists('name',$orgdata))
                            $data['name'] = new ResultDataField('string','name',$orgdata['name'],'Название','Название организации');
                        if (array_key_exists('address',$orgdata)) {
                            $data['address'] = new ResultDataField('string','address',$orgdata['address']['postcode'].', './*$orgdata['adm_div']['0']['name'].', '.*/$orgdata['address_name'],'Адрес','Адрес организации');
                        }
                        if (array_key_exists('address_comment',$orgdata)) {
                            $data['address_comment'] = new ResultDataField('string','address_comment',$orgdata['address_comment'],'Примечание к адресу','Примечание к адресу организации');
                        }
                        if (array_key_exists('point',$orgdata)) {
//                            $data['latitude'] = new ResultDataField('string','latitude',$orgdata['point']['lat'],'Широта','Широта');
//                            $data['longitude'] = new ResultDataField('string','longitude',$orgdata['point']['lon'],'Долгота','Долгота');
                            $map = array(array('coords'=>array($orgdata['point']['lat'],$orgdata['point']['lon']),'text'=>/*$orgdata['adm_div']['0']['name'].', '.*/$orgdata['address_name']));
                            $data['address_map'] = new ResultDataField('map','address_map',json_encode($map,JSON_UNESCAPED_UNICODE),'Местоположение','Местоположение');
                        }
                        if (array_key_exists('contact_groups',$orgdata) && sizeof($orgdata['contact_groups'])){
                            $contacts = array();
                            foreach( $orgdata['contact_groups'] as $contact_groups ){
                                $contact_types = array (
                                    'phone' => array('Телефон','phone'),
                                    'fax' => array('Факс','phone'),
                                    'website' => array('Сайт','url'),
                                    'facebook' => array('Страница в Facebook','url'),
                                    'vkontakte' => array('Страница в VK','url'),
                                    'twitter' => array('Канал в Twitter','url'),
                                );
                                foreach($contact_groups['contacts'] as $contact){
                                    if(array_key_exists($contact['type'],$contact_types))
                                        $data[$contact['type']] = new ResultDataField($contact_types[$contact['type']][1],$contact['type'],str_replace(array('+','(',')','-','‒',' '),'',$contact['text']),$contact_types[$contact['type']][0],$contact_types[$contact['type']][0]);
                                }
                            }
//                            $data['contacts'] = new ResultDataField('string','contacts',implode($contacts, ', '),'Контакты','Контакты организации');
                        }
                        if (array_key_exists('schedule',$orgdata)){
                            $schedule = array();
                            foreach($orgdata['schedule'] as $key => $val){
			            if(isset($this->days_ru[$key])){
                                             $schedule[] = $this->days_ru[$key].' '.$val['working_hours']['0']['from'].'-'.$val['working_hours']['0']['to'];
				    }
                            }
                            $data['hours'] = new ResultDataField('string','hours',implode($schedule,', '),'График','График работы организации');
                        }
                        if (array_key_exists('rubrics',$orgdata) && sizeof($orgdata['rubrics'])) {
                            $categories = array();
                            foreach ($orgdata['rubrics'] as $cat)
                                $categories[]=$cat['name'];
                                $data['categories'] = new ResultDataField('string','categories',implode($categories,', '),'Категории','Категории организации');
                        }
                        $resultData->addResult($data);
                    }
               } elseif (isset($gisres['error']['message'])) {
                   $rContext->setError($gisres['error']['message']);
               } else {
                   $rContext->setError('Некорректный ответ сервиса');
               }
        }

        $rContext->setResultData($resultData);
        $rContext->setFinished();
    }
}

?>
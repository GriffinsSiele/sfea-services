<?php

class GooglePlusPlugin implements PluginInterface
{
    public function __construct()
    {
    }

    public function getName()
    {
        return 'GooglePlus';
    }

    public function getTitle()
    {
        return 'Поиск в Google+';
    }

    public function getSessionData($sourceid)
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=$sourceid AND lasttime<DATE_SUB(now(), INTERVAL 1 SECOND) ORDER BY lasttime limit 1");

        if($result)
        {
            $row = $result->fetch_object();

            if ($row)
            {
                $sessionData = new \StdClass;

                $sessionData->id = $row->id;
                $sessionData->code = $row->captcha;
                $sessionData->token = $row->token;
                $sessionData->starttime = $row->starttime;
                $sessionData->lasttime = $row->lasttime;
                $sessionData->cookies = $row->cookies;
                $sessionData->proxyid = $row->proxyid;
                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;

                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1 WHERE id=".$sessionData->id);
//                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3 WHERE used>=10 AND id=".$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if(!isset($initData['phone']) && !isset($initData['email'])) {
            $rContext->setFinished();
            $rContext->setError('Указаны не все обязательные параметры (телефон или email)');

            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $swapData['session'] = $this->getSessionData(isset($swapData['url'])?43:17);
//        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        $rContext->setSwapData($swapData);

        if(!$swapData['session']) {
            if (isset($swapData['iteration']) && ($swapData['iteration']>=20)) {
                $rContext->setFinished();
                $rContext->setError('Нет актуальных сессий');
            } else {
                (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
                $rContext->setSwapData($swapData);
                $rContext->setSleep(3);
            }
            return false;
        }
/*
        $rContext->setFinished();
        $rContext->setError('Сервис временно недоступен');
        return false;
*/
        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        if (isset($initData['phone'])) {
//            if (strlen($initData['phone'])==10)
//                $initData['phone']='7'.$initData['phone'];
//            if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
//                $initData['phone']='7'.substr($initData['phone'],1);
        }

        if (!isset($swapData['url'])) {
            $url = 'https://people.googleapis.com/v1/people:createContact?&access_token='.$swapData['session']->token;
            if (isset($initData['phone'])) {
                $params = '{"phoneNumbers":[{"value":"'.$initData['phone'].'"}]}'; 
            } else {
                $params = '{"emailAddresses":[{"value":"'.$initData['email'].'"}]}';
            }
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        } else {
            $url = $swapData['url'];
        }        
        curl_setopt($ch, CURLOPT_URL, $url);
        $header[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        if ($swapData['session']->proxy) {
            curl_setopt($ch,CURLOPT_PROXY,$swapData['session']->proxy);
            if ($swapData['session']->proxy_auth) {
                curl_setopt($ch,CURLOPT_PROXYUSERPWD,$swapData['session']->proxy_auth); 
                curl_setopt($ch,CURLOPT_PROXYAUTH,CURLAUTH_ANY); 
            }
        }
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $rContext->setSwapData($swapData);

        $error = false;
        $content = curl_multi_getcontent($rContext->getCurlHandler());

        if (!$content) {
            $error = ($swapData['iteration']>5) && false; //curl_error($rContext->getCurlHandler());
        } elseif (isset($swapData['url'])) {
//            file_put_contents('./logs/googleplus/contrib_'.time().'.html',$content);
            $resultData = new ResultDataList();
            $data = $swapData['data'];
            if (preg_match("/\"Contributions by ([^\"]+)\"/",$content,$matches)) {
                $data['name'] = new ResultDataField('string','Name',$matches[1],'Имя','Имя');
            }
            if (preg_match("/\\\"(https:\/\/lh\d\.googleusercontent\.com\/a-\/[A-Za-z0-9\_\-\/]+)\\\\/",$content,$matches)) {
                $data['avatar'] = new ResultDataField('image','Avatar',$matches[1].'=s400','Аватар','Аватар');
            }
            if (preg_match("/\"Level ([\d]+) /",$content,$matches)) {
                $data['level'] = new ResultDataField('string','Level',$matches[1],'Уровень','Уровень');
            }
            if (preg_match("/\| ([\d\,]+) Points\"/",$content,$matches)) {
                $data['points'] = new ResultDataField('string','Points',strtr($matches[1],array(','=>'')),'Баллов','Баллов');
            }
            if (preg_match("/\"Отзывы[^\,]+\,([\d]+)\,/ui",$content,$matches)) {
                $data['reviews'] = new ResultDataField('string','Reviews',$matches[1],'Отзывов','Отзывов');
            }
            if (preg_match("/\"Оценки[^\,]+\,([\d]+)\,/ui",$content,$matches)) {
                $data['ratings'] = new ResultDataField('string','Ratings',$matches[1],'Оценок','Оценок');
            }
            if (preg_match("/\"Фото[^\,]+\,([\d]+)\,/ui",$content,$matches)) {
                $data['photos'] = new ResultDataField('string','Photos',$matches[1],'Фотографий','Фотографий');
            }
            if (preg_match("/\"Видео[^\,]+\,([\d]+)\,/ui",$content,$matches)) {
                $data['videos'] = new ResultDataField('string','Videos',$matches[1],'Видео','Видео');
            }
            if (preg_match("/\"Ответы[^\,]+\,([\d]+)\,/ui",$content,$matches)) {
                $data['questions'] = new ResultDataField('string','Questions',$matches[1],'Ответов','Ответов');
            }
            $resultData->addResult($data);
            $rContext->setResultData($resultData);
            $rContext->setFinished();
        } else {
//            file_put_contents('./logs/googleplus/googleplus_'.time().'.txt',$content);
            $res = json_decode($content, true);               
            if($res && isset($res['resourceName'])){
                $data = array();
                if(isset($res['metadata']['sources']) && sizeof($res['metadata']['sources'])>1){
                    if (isset($res['phoneNumbers'])) {
                        foreach($res['phoneNumbers'] as $i => $rec)
                            $data['phone'.($i?$i+1:'')] = new ResultDataField('phone','Phone'.($i?$i+1:''),strtr($rec['value'],array('+'=>'')),'Телефон','Телефон');
                    }
                    if (isset($res['emailAddresses'])) {
                        foreach($res['emailAddresses'] as $i => $rec)
                            $data['email'.($i?$i+1:'')] = new ResultDataField('email','Email'.($i?$i+1:''),$rec['value'],'Email','Email');
                    }
                    if (isset($res['names'][0]['displayName']))
                        $data['name'] = new ResultDataField('string','Name',$res['names'][0]['displayName'],'Имя','Имя');
                    if (isset($res['genders'][0]['value']))
                        $data['gender'] = new ResultDataField('string','Gender',$res['genders'][0]['value'],'Пол','Пол');
                    if (isset($res['residences'][0]['value']))
                        $data['location'] = new ResultDataField('string','Location',$res['residences'][0]['value'],'Местоположение','Местоположение');
                    if (isset($res['urls'][0]['value']))
                        $data['profile'] = new ResultDataField('url','Profile',$res['urls'][0]['value'],'Профиль','Профиль');
                    if (isset($res['photos'])/* && sizeof($res['metadata']['sources'])>1*/) {
                        foreach($res['photos'] as $i => $rec)
                            if (isset($rec['metadata']['source']['type']) && ($rec['metadata']['source']['type']=='PROFILE' || $rec['metadata']['source']['type']=='DOMAIN_PROFILE') && !strpos($rec['url'],'/AAAAAAAAAA'))
                                $data['photo'.($i?$i+1:'')] = new ResultDataField('image','Photo'.($i?$i+1:''),strtr($rec['url'],array('/s100/'=>'/','=s100'=>'')),'Фото','Фото');
                    }
                    if (isset($res['occupations'])) {
                        foreach($res['occupations'] as $i => $rec)
                            $data['occupation'.($i+1)] = new ResultDataField('string','Occupation'.($i+1),$rec['value'],'Род занятий '.($i+1),'Род занятий '.($i+1));
                    }
/*
                    if (isset($res['organizations'])) {
                        foreach($res['organizations'] as $i => $rec)
                            $data['organization'.($i+1)] = new ResultDataField('string','Organization'.($i+1),$rec['value'],'Место '.($i+1),'Место '.($i+1));
                    }
*/
                    if (isset($res['metadata']['sources'][1]['id'])) {
                        $id = $res['metadata']['sources'][1]['id'];
                        $url = 'https://www.google.com/maps/contrib/'.$id;
                        $data['id'] = new ResultDataField('string','ID',$id,'ID пользователя','ID пользователя');
                        $data['map'] = new ResultDataField('url','Map',$url,'Карта пользователя','Карта пользователя');
                        $swapData['url'] = $url;
                        $swapData['data'] = $data;
                        $rContext->setSwapData($swapData);
                    } else {
                        $resultData = new ResultDataList();
                        $resultData->addResult($data);
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
                    }
                } else {
                    $resultData = new ResultDataList();
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                }
            } elseif ($res && isset($res['error']['message'])) {
                if (strpos($res['error']['message'],'access token')) {
                    $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=5 WHERE id=" . $swapData['session']->id);
                    unset($swapData['session']);
                } else {
                    $error = $res['error']['message'];
                }
            } else {
                $error = "Некорректный ответ";
            }
        }

        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>10) {
            $error='Превышено количество попыток получения ответа';
        }
        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();
            return false;
        }

        return true;
    }
}

?>
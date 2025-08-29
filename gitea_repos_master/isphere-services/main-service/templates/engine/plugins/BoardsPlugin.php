<?php

class BoardsPlugin implements PluginInterface
{
    public function getName()
    {
        return 'boards';
    }

    public function getTitle($checktype = '')
    {
        $title = ['' => 'Поиск на досках объявлений', 'boards_phone' => 'Поиск телефона на досках объявлений', 'boards_phone_kz' => 'Поиск телефона на досках объявлений (Казахстан)', 'boards_phone_pl' => 'Поиск телефона на досках объявлений (Польша)', 'boards_phone_ua' => 'Поиск телефона на досках объявлений (Украина)', 'boards_phone_uz' => 'Поиск телефона на досках объявлений (Узбекистан)', 'boards_phone_ro' => 'Поиск телефона на досках объявлений (Румыния)', 'boards_phone_pt' => 'Поиск телефона на досках объявлений (Португалия)', 'boards_phone_bg' => 'Поиск телефона на досках объявлений (Болгария)'];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск телефона на досках объявлений';
    }

    public function getSessionData(array $params, $sessionid = false)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $sessionData = null;
        $result = $mysqli->executeQuery("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM session s WHERE sessionstatusid=2 AND sourceid=54 AND unix_timestamp(now())-unix_timestamp(lasttime)>0 ORDER BY lasttime limit 1");
        if ($result) {
            $row = $result->fetchAssociative();
            if ($row) {
                $sessionData = new \stdClass();
                $sessionData->id = $row['id'];
                $sessionData->code = $row['captcha'];
                $sessionData->token = $row['token'];
                $sessionData->starttime = $row['starttime'];
                $sessionData->lasttime = $row['lasttime'];
                $sessionData->cookies = $row['cookies'];
                $sessionData->proxyid = $row['proxyid'];
                $mysqli->executeStatement("UPDATE session SET lasttime=now(),used=ifnull(used,0)+1,statuscode='used' WHERE id=".$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        $checktype = \substr($initData['checktype'], 7);
        $country = \substr($initData['checktype'], 13);
        if (!isset($initData['phone'])) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (телефон)');
            return false;
        }
        //        if (strlen($initData['phone'])==10)
        //            $initData['phone']='7'.$initData['phone'];
        //        if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
        //            $initData['phone']='7'.substr($initData['phone'],1);
        /*
                if(substr($initData['phone'],0,1)!='8'){
                    $rContext->setFinished();
                    $rContext->setError('Поиск производится только по российским и казахстанским телефонам');
                    return false;
                }
        */
        if ('phone' == $checktype && !\preg_match('/7[3489]/', \substr($initData['phone'], 0, 2))) {
            $rContext->setFinished();
            //            $rContext->setError('Поиск производится только по российским телефонам');
            return false;
        }
        if ('phone_kz' == $checktype && !\preg_match('/7[678]/', \substr($initData['phone'], 0, 2))) {
            $rContext->setFinished();
            //            $rContext->setError('Поиск производится только по казахстанским телефонам');
            return false;
        }
        if ('phone_by' == $checktype && !\preg_match('/375/', \substr($initData['phone'], 0, 3))) {
            $rContext->setFinished();
            //            $rContext->setError('Поиск производится только по белорусским телефонам');
            return false;
        }
        if ('phone_pl' == $checktype && !\preg_match('/48/', \substr($initData['phone'], 0, 2))) {
            $rContext->setFinished();
            //            $rContext->setError('Поиск производится только по польским телефонам');
            return false;
        }
        if ('phone_ua' == $checktype && !\preg_match('/380/', \substr($initData['phone'], 0, 3))) {
            $rContext->setFinished();
            //            $rContext->setError('Поиск производится только по украинским телефонам');
            return false;
        }
        if ('phone_uz' == $checktype && !\preg_match('/998/', \substr($initData['phone'], 0, 3))) {
            $rContext->setFinished();
            //            $rContext->setError('Поиск производится только по узбекистанским телефонам');
            return false;
        }
        if ('phone_ro' == $checktype && !\preg_match('/40/', \substr($initData['phone'], 0, 2))) {
            $rContext->setFinished();
            //            $rContext->setError('Поиск производится только по румынским телефонам');
            return false;
        }
        if ('phone_pt' == $checktype && !\preg_match('/351/', \substr($initData['phone'], 0, 3))) {
            $rContext->setFinished();
            //            $rContext->setError('Поиск производится только по португальским телефонам');
            return false;
        }
        if ('phone_bg' == $checktype && !\preg_match('/359/', \substr($initData['phone'], 0, 3))) {
            $rContext->setFinished();
            //            $rContext->setError('Поиск производится только по болгарским телефонам');
            return false;
        }
        /*
                if($checktype=='phone') {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                    return false;
                }
        */
        // //////////////////////////////////////////////////////////////////////////////////////////////////
        /*
                if(!isset($swapData['session'])) {
                    $swapData['session'] = $this->getSessionData($params);
                    if (isset($swapData['session'])) {
                        $swapData['iteration']=0;
                        $rContext->setSwapData($swapData);
                    }
                }
                if(!$swapData['session']) {
                    if (isset($swapData['iteration']) && ($swapData['iteration']>=30)) {
                        $rContext->setFinished();
                        $rContext->setError('Слишком много запросов в очереди');
                    } else {
                        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
                        $rContext->setSwapData($swapData);
                        $rContext->setSleep(2);
                    }
                    return false;
                }
        */
        // //////////////////////////////////////////////////////////////////////////////////////////////////
        $ch = $rContext->getCurlHandler();
        $phone = $initData['phone'];
        if ('pt' == $country) {
            $phone = \substr($phone, 3);
        }
        $url = 'http://global.d0o.ru/api/';
        $token = '94def889184bb7b8de213422790e40f7';
        // $swapData['session']->token;
        if (1 || $country) {
            $url .= 'ads?token='.$token.($country ? '&country='.$country : '').'&phone='.$phone;
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 5);
        } elseif (!isset($swapData['id'])) {
            $url .= 'create?token='.$token.($country ? '&country='.$country : '').'&phone='.$phone;
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 2);
        } else {
            $url .= 'result?token='.$token.'&id='.$swapData['id'];
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 2);
        }
        \curl_setopt($ch, \CURLOPT_URL, $url);
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(array $params, &$rContext)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        $swapData['iteration'] = !isset($swapData['iteration']) ? 1 : $swapData['iteration'] + 1;
        $error = $swapData['iteration'] > 2 ? \curl_error($rContext->getCurlHandler()) : false;
        $content = \curl_multi_getcontent($rContext->getCurlHandler());
        if ($content) {
            //            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/boards/boards_'.time().'.txt',$content);
            $res = \json_decode($content, true);
            if ($res && isset($res['data'])) {
                $resultData = new ResultDataList();
                foreach ($res['data'] as $row) {
                    $data = [];
                    if (isset($row['name'])) {
                        $data['name'] = new ResultDataField('string', 'Name', $row['name'], 'Имя', 'Имя');
                    }
                    if (isset($row['time'])) {
                        $data['time'] = new ResultDataField('string', 'Time', $row['time'], 'Дата', 'Дата');
                    }
                    if (isset($row['location'])) {
                        $data['location'] = new ResultDataField('string', 'Location', $row['location'], 'Местоположение', 'Местоположение');
                    }
                    if (isset($row['source'])) {
                        $data['source'] = new ResultDataField('string', 'Source', $row['source'], 'Источник', 'Источник');
                    }
                    if (isset($row['url'])) {
                        $data['url'] = new ResultDataField('url', 'URL', $row['url'], 'URL', 'URL');
                    }
                    if (isset($row['category'])) {
                        $data['category'] = new ResultDataField('string', 'Category', $row['category'], 'Категория', 'Категория');
                    }
                    if (isset($row['title'])) {
                        $data['title'] = new ResultDataField('string', 'Title', $row['title'], 'Заголовок', 'Заголовок');
                    }
                    if (isset($row['description'])) {
                        $data['description'] = new ResultDataField('string', 'Description', \iconv('windows-1251', 'utf-8', \iconv('utf-8', 'windows-1251//IGNORE', $row['description'])), 'Описание', 'Описание');
                    }
                    if (isset($row['price'])) {
                        $data['price'] = new ResultDataField('float', 'Price', 'youla.io' == $row['source'] ? $row['price'] / 100 : $row['price'], 'Цена', 'Цена');
                    }
                    /*
                                        if (isset($row['coords']['lat']) && isset($row['coords']['lng'])) {
                                          $map = array(array('coords' => array($row['coords']['lat'],$row['coords']['lng']), 'text' => ''));
                                          $data['coords'] = new ResultDataField('map','Location',strtr(json_encode($map,JSON_UNESCAPED_UNICODE),array("},{"=>"},\n{")),'Местоположение','Местоположение');
                                        }
                    */
                    if (\count($data)) {
                        $resultData->addResult($data);
                    }
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                return true;
            } elseif ($res && isset($res['id'])) {
                --$swapData['iteration'];
                $swapData['id'] = $res['id'];
            } elseif ($res && isset($res['status']) && 'processing' == $res['status']) {
                --$swapData['iteration'];
            } elseif ($res && isset($res['message'])) {
                $error = $res['message'];
            } else {
                if ($res) {
                    $error = 'Некорректный ответ';
                }
            }
        }
        $rContext->setSwapData($swapData);
        if (!$error && isset($swapData['iteration']) && $swapData['iteration'] >= 3) {
            $error = 'Превышено количество попыток получения ответа';
        }
        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();

            return false;
        }
        $rContext->setSleep(2);

        return true;
    }
}

<?php

class WebMoneyPlugin implements PluginInterface
{
    public function getName()
    {
        return 'WebMoney';
    }

    public function getTitle()
    {
        return 'Поиск в WebMoney';
    }

     public function str_uprus($text) {
        $up = array(
                'а' => 'А',
                'б' => 'Б',
                'в' => 'В',
                'г' => 'Г',
                'д' => 'Д',
                'е' => 'Е',
                'ё' => 'Е',
                'ж' => 'Ж',
                'з' => 'З',
                'и' => 'И',
                'й' => 'Й',
                'к' => 'К',
                'л' => 'Л',
                'м' => 'М',
                'н' => 'Н',
                'о' => 'О',
                'п' => 'П',
                'р' => 'Р',
                'с' => 'С',
                'т' => 'Т',
                'у' => 'У',
                'ф' => 'Ф',
                'х' => 'Х',
                'ц' => 'Ц',
                'ч' => 'Ч',
                'ш' => 'Ш',
                'щ' => 'Щ',
                'ъ' => 'Ъ',
                'ы' => 'Ы',
                'ь' => 'Ь',
                'э' => 'Э',
                'ю' => 'Ю',
                'я' => 'Я',
        );
        if (preg_match("/[а-яё]/", $text))
            $text = strtr($text, $up);
        return $text;
    }

    public function getSessionData()
    {
        global $mysqli,$userId;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,server,starttime,lasttime,sourceaccessid FROM isphere.session WHERE sessionstatusid=2 AND sourceid=7 AND sourceaccessid IN (20,21,22,23) ORDER BY lasttime limit 1");

        if($result)
        {
            $row = $result->fetch_object();

            if ($row)
            {
                $sessionData = new \StdClass;

                $sessionData->id = $row->id;
                $sessionData->starttime = $row->starttime;
                $sessionData->lasttime = $row->lasttime;
                $sessionData->cookies = $row->cookies;
                $sessionData->server = $row->server;
                $sessionData->sourceaccessid = $row->sourceaccessid;

                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1 WHERE id=".$sessionData->id);
//                $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6 WHERE id=".$sessionData->id);
            }
        }

        return $sessionData;
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

        if (strlen($initData['phone'])==10)
            $initData['phone']='7'.$initData['phone'];
        if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
            $initData['phone']='7'.substr($initData['phone'],1);
/*
        if(substr($initData['phone'],0,2)!='79')
        {
            $rContext->setFinished();
            $rContext->setError('Поиск производится только по мобильным телефонам в коде 9xx');

            return false;
        }
*/
        if(substr($initData['phone'],0,1)!='7'){
            $rContext->setFinished();
//            $rContext->setError('Поиск производится только по российским телефонам');
            return false;
        }

        $rContext->setFinished();
//        $rContext->setError('Сервис временно недоступен');
        return false;

        $phone = $initData['phone'];

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        if(!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData();

            if(!$swapData['session']) {
//                if($swapData['iteration']>5) {
                    $rContext->setError('Сервис временно недоступен');
                    $rContext->setFinished();
//                }
//                $rContext->setSleep(3);
                return false;
            }
            $rContext->setSwapData($swapData);
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $path = $swapData['session']->server.'/PhizIC/private/';
        $form = $path.'payments/servicesPayments/edit.do';
        $recipient = array(
              20 => 500525137,
              21 => 500530797,
              22 => 500565036,
              23 => 748594,
        );
        $params = '?recipient='.$recipient[$swapData['session']->sourceaccessid].'&fromResource=&needSelectProvider=true';

        $cookies = $swapData['session']->cookies;

        $header = array();
        if(isset($swapData['post']) && isset($swapData['code'])){
            $url = $path.'cards/currency/code.do';
            $post = array(
                'field(type)'=>'phone',
                'field(phoneNumber)'=>$phone,
                'PAGE_TOKEN'=>$swapData['post']['PAGE_TOKEN'],
            );
            $header[] = 'X-Requested-With: XMLHttpRequest';
        } else {
            $url = $form;
            if(isset($swapData['post'])){
                $post = $swapData['post'];
                $post['field(course)']='';
                $post['field(S659126977919A554936337100)']=$phone;
                $post['field(S659126977919A554936336207)']='';
                $post['field(S659126977919A554936337795)']=100;
                $post['operation']='button.next';
//                unset($post['externalCardNumber']);

                $cookies = implode('; ',array(
                    $cookies,
                    'currentUrl='.$form,
                    'isFormSubmit='.(isset($swapData['post']) && !isset($swapData['code']) ? 'true' : 'false'),
                    'offset=805',
                ));
            } else {
                $url .= $params;
            }
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $form.$params);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_COOKIE, $cookies);
        if(isset($post)){
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
            $header[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if (!$swapData['session']) {
            $rContext->setSleep(3);
            return true;
        }

        $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;
        $curl_error = curl_error($rContext->getCurlHandler());
        $error = false;

        if(!$curl_error) {
            $content = curl_multi_getcontent($rContext->getCurlHandler());

            if (!isset($swapData['post'])) {
//                file_put_contents('./logs/sberbank/webmoney_'.time().'.html',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
		$content = iconv('windows-1251','utf-8',$content);

                if(preg_match("/<form name=\"CreateAutoSubscriptionPaymentForm\" .*?<\/form>/sim", $content, $matches)){
                    $post = array();
                    $form = $matches[0];
                    if(preg_match_all("/<input ([^>]+)>/", $form, $matches)){
                        foreach ($matches[1] as $match) {
                            if(preg_match("/name=\"([^\"]+)/", $match, $val)){
                                $name = $val[1];
                                $post[$name] = '';
                                if(preg_match("/value=\"([^\"]+)/", $match, $val)){
                                    $post[$name] = $val[1];
                                }
                            }
                        }
                    }
                    if(preg_match_all("/<select .*?<\/select>/sim", $form, $matches)){
                        foreach ($matches[0] as $match) {
                            if(preg_match("/name=\"([^\"]+)/", $match, $val)){
                                $name = $val[1];

                                if(preg_match_all("/<option value=\"([^\"]+)[^>]+>([^<]+)/", $match, $vals)){
                                    foreach ($vals[2] as $k => $v) {
                                        if(!preg_match("/ 0\./", $v)){
                                            $post[$name] = $vals[1][$k];
                                        }
                                    }
                                }
                            }
                        }
                    }

//                    $post['currency'] = '';
                    $swapData['post'] = $post;
//                    $swapData['code'] = true;
                    $rContext->setSleep(1);
                } elseif (strpos($content,'невозможна')) {
                    unset($swapData['session']);
                    unset($swapData['post']);
//                    $rContext->setSwapData($swapData);
//                    return true;
                } elseif (strpos($content,'попытку')) {
                    if (isset($swapData['session']))
                        $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 hour),sessionstatusid=6 WHERE id=" . $swapData['session']->id);
//                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3 WHERE id=" . $swapData['session']->id);
                    unset($swapData['session']);
                    unset($swapData['post']);
//                    $rContext->setSwapData($swapData);
//                    return true;
                } elseif (strpos($content,'заблокированы')) {
                    if (isset($swapData['session']))
                        $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(current_date(),interval 1 day),sessionstatusid=6 WHERE id=" . $swapData['session']->id);
                    unset($swapData['session']);
                    unset($swapData['post']);
//                    $rContext->setSwapData($swapData);
//                    return true;
                } elseif(!preg_match("/\"\/PhizIC\/logoff.do\"/", $content)) {
                    if (isset($swapData['session']))
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3 WHERE id=" . $swapData['session']->id);
                    unset($swapData['session']);
                    unset($swapData['post']);
//                    $rContext->setSwapData($swapData);
//                    return true;
                } else {
//                    $error = 'Ошибка при передаче запроса';
                    if (isset($swapData['session']))
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3 WHERE id=" . $swapData['session']->id);
                    unset($swapData['session']);
                    unset($swapData['post']);
                }
            } elseif (isset($swapData['code'])) {
                $zelonow = time();
//                file_put_contents('./logs/sberbank/webmoney_code_'.$zelonow.'.html',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
	        $content = iconv('windows-1251','utf-8',$content);
                unset($swapData['code']);

                if(preg_match("/name=\"currency\" value=\"([^\"]+)\"/", $content, $matches) && $matches[1]){
                    $post['currency'] = $matches[1];
                    $rContext->setSleep(1);
                }
                if(preg_match("/currencyErrorMessageAr.push\(\"([^\"]+)\"/", $content, $matches) && ($matches[1])) {
                    $matches[1] = html_entity_decode($matches[1]);
                    $post['isErrorCurrency'] = true;
                    if (strpos($matches[1],'невозможно') || strpos($matches[1],'не подключен')) {
                        $resultData = new ResultDataList();
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
                    } elseif (strpos($matches[1],'разных')) {
                        $resultData = new ResultDataList();
                        $data['result'] = new ResultDataField('string','result', 'По телефону '.$initData['phone'].' найдено несколько клиентов', 'Результат', 'Результат');
                        $data['result_code'] = new ResultDataField('string','result_code', 'FOUND_SEVERAL', 'Код результата', 'Код результата');
                        $resultData->addResult($data);
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
                    } elseif (strpos($matches[1],'заблокированы')!==false) {
                        if (isset($swapData['session']))
                            $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(current_date(),interval 1 day),sessionstatusid=6 WHERE id=" . $swapData['session']->id);
                        unset($swapData['session']);
                        unset($swapData['post']);
//                        $rContext->setSwapData($swapData);
//                        return true;
                    } elseif (strpos($matches[1],'Не удается')!==false || strpos($matches[1],'не найдено')!==false) {
                        $resultData = new ResultDataList();
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
                    } else {
                        $error = trim($matches[1]);
                    }
                }
            } else {
                $zelonow = time();
//                file_put_contents('./logs/sberbank/webmoney_result_'.$zelonow.'.html',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
		$content = iconv('windows-1251','utf-8',$content);
                $resultData = new ResultDataList();
                $data = array();

                if(preg_match("/<form name=\"ConfirmPaymentByFormForm\" .*?<\/form>/sim", $content, $matches)){
                    if(preg_match_all("/<span class=\"paymentTextLabel\">([^:]+):<[^<]*<[^<]*<[^<]*<[^<]*<b>([^<]+)/", $content, $matches)){
                        $data['result'] = new ResultDataField('string','result', 'По телефону '.$initData['phone'].' найден кошелек WebMoney', 'Результат', 'Результат');
                        $data['result_code'] = new ResultDataField('string','result_code', 'FOUND', 'Код результата', 'Код результата');
                        $match_code = '';
                        foreach($matches[1] as $i => $label){
                            $matches[2][$i] = trim(strip_tags($matches[2][$i]));
                            if ($label=='ФИО получателя' && $matches[2][$i]!='.') {
                                $name = $matches[2][$i];
                                $data['name'] = new ResultDataField('string','name', $name, $label, $label);
                                if (isset($initData['first_name'])) {
                                    $split_name = explode(' ',$this->str_uprus($name));
                                    $first_name='';
                                    for($i=0; $i<sizeof($split_name)-1; $i++)
                                        $first_name = trim($first_name.' '.$split_name[$i]);
                                    $last_name = $split_name[sizeof($split_name)-1];
                                    if ($this->str_uprus($initData['first_name'])==$first_name) {
                                        if (isset($initData['last_name']) && ($this->str_uprus(substr($initData['last_name'],0,1))==substr($last_name,0,1))) {
                                            $match_code = 'MATCHED';
                                        } else {
                                            $match_code = 'MATCHED_NAME_ONLY';
                                        }
                                    } else {
                                        $match_code = 'NOT_MATCHED';
                                    }
                                    $data['match_code'] = new ResultDataField('string','match_code', $match_code, 'Результат сравнения имени', 'Результат сравнения имени');
                                }
                            }
                            if ($label=='Номер кошелька') {
                                $number = strtr($matches[2][$i],array('•'=>'*','&bull;'=>'*'));
                                $data['number'] = new ResultDataField('string','number', $number, $label, $label);
                            }
                        }
                    }
                    $resultData->addResult($data);
                } elseif(preg_match_all("/\"itemDiv\">([^<]+)/", $content, $matches)){
                    $match = trim(html_entity_decode($matches[1][sizeof($matches[1])-1]));
                    if (strpos($match,'невозможно') || strpos($match,'не найден')) {
                        $resultData = new ResultDataList();
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
/*
                    } elseif (strpos($matches[1],'разных')) {
                        $data['result'] = new ResultDataField('string','result', 'По телефону '.$initData['phone'].' найдено несколько клиентов', 'Результат', 'Результат');
                        $data['result_code'] = new ResultDataField('string','result_code', 'FOUND_SEVERAL', 'Код результата', 'Код результата');
                        $resultData->addResult($data);
*/
/*
                    } elseif (strpos($matches[1],'заблокированы')) {
                        if (isset($swapData['session']))
                            $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(current_date(),interval 1 day),sessionstatusid=6 WHERE id=" . $swapData['session']->id);
                        unset($swapData['session']);
                        unset($swapData['post']);
                        $rContext->setSwapData($swapData);
                        return true;
*/
                    } elseif (strpos($match,'попытку') || strpos($match,'лимит')) {
                        if (isset($swapData['session']))
                            $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 hour),sessionstatusid=6 WHERE id=" . $swapData['session']->id);
//                            $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3 WHERE id=" . $swapData['session']->id);
                        unset($swapData['session']);
                        unset($swapData['post']);
                        $rContext->setSwapData($swapData);
                        return true;
                    } elseif (strpos($match,'Не удается')===false) {
                        $error = trim($match);
                    }
                } elseif(!preg_match("/\"\/PhizIC\/logoff.do\"/", $content)) {
                    if (isset($swapData['session']))
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3 WHERE id=" . $swapData['session']->id);
                    unset($swapData['session']);
                    unset($swapData['post']);
                    $rContext->setSwapData($swapData);
                    return true;
                } else {
//                    $error = 'Невозможно обработать ответ';
//                    if (isset($swapData['session']))
//                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3 WHERE id=" . $swapData['session']->id);
//                    unset($swapData['session']);
                    unset($swapData['post']);
                    $rContext->setSwapData($swapData);
                    return true;
                }

                if (!$error) {
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                }
            }
            $rContext->setSwapData($swapData);
        }

        if(!$error && isset($swapData['iteration']) && ($swapData['iteration']>10))
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
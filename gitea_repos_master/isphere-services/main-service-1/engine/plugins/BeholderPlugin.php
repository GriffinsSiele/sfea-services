<?php

class BeholderPlugin implements PluginInterface
{
    public function __construct()
    {
    }

    public function getName()
    {
        return 'Beholder';
    }

    public function getTitle()
    {
        return 'Поиск в справочнике beholder.pro';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

//        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=10 ORDER BY lasttime limit 1");
        $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE status=1 ORDER BY lasttime limit 1");

        if($result)
        {
            $row = $result->fetch_object();

            if ($row)
            {
                $sessionData = new \StdClass;

                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;
/*
                $sessionData->id = $row->id;
                $sessionData->code = $row->captcha;
                $sessionData->token = $row->token;
                $sessionData->starttime = $row->starttime;
                $sessionData->lasttime = $row->lasttime;
                $sessionData->cookies = $row->cookies;

                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1 WHERE id=".$sessionData->id);
                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3 WHERE used=1 AND id=".$sessionData->id);
*/
                $mysqli->query("UPDATE isphere.proxy SET lasttime=now() WHERE id=".$row->proxyid);
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
        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        if(!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData();
            $rContext->setSwapData($swapData);
        }

        $url = 'https://beholder.pro';
/*
        if(isset($swapData['found']) && !isset($swapData['id'])) {
            if(!isset($swapData['session'])) {
                $swapData['session'] = $this->getSessionData();
                $rContext->setSwapData($swapData);
            }

            if(!$swapData['session']) {
//                $rContext->setFinished();
//                $rContext->setError('Нет актуальных сессий');
                $rContext->setSleep(3);
                return false;
            }

            $url .= '/ajax/captcha.php';
            $post = array(
                'phone'=>$initData['phone'],
                'captcha' => $swapData['session']->code,
            );
            $header[] = 'X-Requested-With: XMLHttpRequest';
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        } else {
*/
            $url .= '/numbers/'.substr($initData['phone'],1,10) /*.(isset($swapData['id']) ? '/'.$swapData['id'] : '') */ ;
/*
        }
        if (isset($swapData['session']))
            curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
*/

        curl_setopt($ch, CURLOPT_URL, $url);
        if ($swapData['session']->proxy) {
            curl_setopt($ch,CURLOPT_PROXY,$swapData['session']->proxy);
            if ($swapData['session']->proxy_auth) {
                curl_setopt($ch,CURLOPT_PROXYUSERPWD,$swapData['session']->proxy_auth); 
//                curl_setopt($ch,CURLOPT_PROXYAUTH,CURLAUTH_ANY); 
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
        $curl_error = curl_error($rContext->getCurlHandler());

        if(!$curl_error) {
            $content = curl_multi_getcontent($rContext->getCurlHandler());
//            file_put_contents('./logs/beholder/beholder_' . time() . '.html', $content);

            $resultData = new ResultDataList();

            if (preg_match("/<div id=\"blockResultUser\">/",$content)) {
//                $records = preg_split("/<div class=\"item\"/", $content);
                $records = explode('<div class="item"',$content);
                array_shift($records);

                foreach($records as $rec) {
                    $data = array();
                    if (preg_match("/<span class=\"name\"[^<]+<b>([^<]+)<\/b>/",$rec,$matches)) {
                        $data['name'] = new ResultDataField('string','Name',$matches[1],'Имя','Имя');
                    }
                    if (preg_match("/<span class=\"name\"[^<]+<b>[^<]+<\/b> \| ([^<]+)</",$rec,$matches)) {
                        $data['type'] = new ResultDataField('string','Type',$matches[1],'Тип','Тип');
                    }
                    if (preg_match("/<img src=\"(data:image[^\"]+)\"/",$rec,$matches)) {
                        $data['photo'] = new ResultDataField('image','Photo',$matches[1],'Фото профиля','Фото профиля');
                    }
                    if (sizeof($data)) $resultData->addResult($data);
                }
            }
            if (preg_match("/<div id=\"blockComments\">/",$content)) {
//                $records = preg_split("/<div class=\"item\"/", $content);
                $records = explode('<div class="item"',$content);
                array_shift($records);

                foreach($records as $rec) {
                    $data = array();
                    $data['type'] = new ResultDataField('string','Type','Отзыв','Тип','Тип');
                    if (preg_match("/<span class=\"name\">([^<]+)<\/span>/",$rec,$matches)) {
                        $data['author'] = new ResultDataField('string','Author',$matches[1],'Автор','Автор отзыва');
                    }
                    if (preg_match("/<span class=\"date\">([^<]+)<\/span>/",$rec,$matches)) {
                        $repl = array(' '=>'.',
                            'янв'=>'01','фев'=>'02','мар'=>'03','апр'=>'04','мая'=>'05','июн'=>'06',
                            'июл'=>'07','авг'=>'08','сен'=>'09','окт'=>'10','ноя'=>'11','дек'=>'12');
                        if (strpos($matches[1],' ')==1) $matches[1] = '0'.$matches[1];
                        $matches[1] = strtr($matches[1],$repl);
                        $data['date'] = new ResultDataField('string','Date',$matches[1],'Дата','Дата отзыва');
                    }
                    if (preg_match("/<span class=\"text\">([^<]+)<\/span>/",$rec,$matches)) {
                        $data['text'] = new ResultDataField('string','Text',$matches[1],'Текст','Текст отзыва');
                    }
                    if (preg_match("/<span>([^<]+)<\/span>/",$rec,$matches)) {
                        $data['rating'] = new ResultDataField('string','Rating',$matches[1],'Рейтинг','Рейтинг отзыва');
                    }
                    if (sizeof($data)) $resultData->addResult($data);
                }
            }

            if (preg_match("/<span id=\"textNotFound\">/",$content) || preg_match("/<span id=\"textFound\">/",$content) || preg_match("/<span id=\"textSocialNetworks\">/",$content)) {
                $rContext->setResultData($resultData);
                $rContext->setFinished();
            } elseif (preg_match("/was not found/",$content)) {
                $error = 'Сервис недоступен';
            } else {
                $error = 'Ошибка обработки ответа';
                file_put_contents('./logs/beholder/beholder_err_' . time() . '.html', $content);
            }

/*
            if(!isset($swapData['found'])) {
                file_put_contents('./logs/beholder/beholder_'.time().'.html',$content);
                if (preg_match("/<div id=\"blockResultUser\">/",$content)) {
                    $swapData['found'] = true;
                } elseif (preg_match("/<span id=\"textNotFound\">/",$content)) {
                    $resultData = new ResultDataList();
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                } else {
                    $error = 'Ошибка обработки ответа';
                    file_put_contents('./logs/beholder/beholder_err_' . time() . '.html', $content);
                }
            } elseif(!isset($swapData['id'])) {
                file_put_contents('./logs/beholder/beholder_captcha_'.time().'.html',$content);
                $res = json_decode($content, true);

                if($res && is_array($res) && isset($res['status'])){
                    if ($res['status']=='ok') {
                        $swapData['id'] = $res['id'];
                    } elseif ($res['status']=='error') {
                        if (!isset($res['reason']))  {
                            $error = 'Некорректный ответ сервиса';
                        } elseif ($res['reason']=='invalidCaptcha') {
                            if (isset($swapData['session']))
                                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=4 WHERE id=".$swapData['session']->id);
//                            $rContext->setSleep(3);
                            unset($swapData['session']);
                            $rContext->setSwapData($swapData);
                            return true;
                        } else {
                            $error = $res['reason'];
                        }
                    } else {
                        $error = 'Ошибка обработки капчи';
                        file_put_contents('./logs/beholder/beholder_captcha_err_' . time() . '.html', $content);
                    }
                } else {
//                    $error = 'Ошибка обработки капчи';
//                    file_put_contents('./logs/beholder/beholder_captcha_err_' . time() . '.html', $content);
                }
            } else {
                file_put_contents('./logs/beholder/beholder_id_'.time().'.html',$content);
                $resultData = new ResultDataList();

                if (preg_match("/<div id=\"blockResultUser\">/",$content)) {
//                    $records = preg_split("/<div class=\"item\">/", $content);
                    $records = explode('<div class="item">',$content);
                    array_shift($records);

                    foreach($records as $rec) {
                        $data = array();
                        if (preg_match("/<a class=\"link\" href=\"([^\"]+)\"/",$rec,$matches)) {
                            $data['link'] = new ResultDataField('url','Link',$matches[1],'Ссылка','Ссылка на профиль');
                        } elseif (preg_match("/<a class=\"link\" href=\"\" [^>]+>([^<]+)<\/a>/",$rec,$matches)) {
                            $data['link'] = new ResultDataField('url','Link','https://'.strtr($matches[1],array(' > '=>'/')),'Ссылка','Ссылка на профиль');
                        } 
                        if (preg_match("/<a class=\"name\" [^<]+<b>([^<]+)<\/b>/",$rec,$matches)) {
                            $data['name'] = new ResultDataField('string','Name',$matches[1],'Имя','Имя');
                        }
                        if (preg_match("/<a [^<]+<img src=\"(http[^\"]+)\"/",$rec,$matches)) {
                            $data['photo'] = new ResultDataField('image','Photo',$matches[1],'Фото профиля','Фото профиля');
                        }
                        if (preg_match("/<span class=\"descr\">([^<]+)<\/span>/",$rec,$matches)) {
                            $data['description'] = new ResultDataField('string','Description',$matches[1],'Описание','Описание');
                        }
                        if (sizeof($data)) $resultData->addResult($data);
                    }
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                } elseif (preg_match("/<span id=\"textNotFound\">/",$content)) {
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                } else {
                    $error = 'Ошибка обработки ответа';
                    file_put_contents('./logs/beholder/beholder_id_err_' . time() . '.html', $content);
                }
            }
            $rContext->setSwapData($swapData);
*/
        }

        if(!$error && isset($swapData['iteration']) && $swapData['iteration']>10)
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
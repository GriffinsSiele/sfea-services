<?php

class VKPhonePlugin implements PluginInterface
{
    private $names = array (
                           'Ваш ip адрес' => false,
                           'Номер телефона' => array('phone', 'Номер телефона', 'Номер телефона'),
                           'Страна' => array('country', 'Страна', 'Страна'),
                           'Регион' => array('region', 'Регион', 'Регион'),
                           'Оператор' => array('operator', 'Оператор номера', 'Оператор номера'),
                           'Проверка по MNP*' => array('service_operator', 'Обслуживающий оператор', 'Обслуживающий оператор'),
                           'Статус абонента HLR*' => array('status', 'Статус абонента', 'Статус абонента'),
                           'Наличие в социальных сетях' => array('presence', 'Наличие в социальных сетях', 'Наличие в социальных сетях'),
    );

    public function __construct()
    {
    }

    public function getName()
    {
        return 'VK-Phone';
    }

    public function getTitle()
    {
        return 'Проверка телефона на наличие аккаунта VK';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token FROM isphere.session WHERE sessionstatusid=2 AND sourceid=6 ORDER BY lasttime limit 1");

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

                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1 WHERE id=".$sessionData->id);
//                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3 WHERE used=1 AND id=".$sessionData->id);
            }
        }

        return $sessionData;
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
        $ch = $rContext->getCurlHandler();

        $swapData['session'] = $this->getSessionData();
        $rContext->setSwapData($swapData);
        if(!$swapData['session']) {
//            $rContext->setFinished();
//            $rContext->setError('Нет актуальных сессий');
            $rContext->setSleep(3);
            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $url = 'https://vk-phone.info/test.php';
        $post = array(
            'phone'=>'+'.$initData['phone'],
            'captcha_vk_tel'=>$swapData['session']->code,
        );

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);

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
            $swapData['iteration'] = (!isset($swapData['iteration']))?1:$swapData['iteration'] + 1;

//            if(!isset($swapData['path'])) {
                if(strpos($content,"charset=utf-8")===false){
                    $content = iconv('windows-1251','utf-8',$content);
                }
//                file_put_contents('./logs/vkphone/vkphone_'.time().'.html',$content);

                if(preg_match("/<meta[^>]+>(.*?)<hr>/", $content,$matches)){
                    $resultData = new ResultDataList();
                    $rows = preg_split('/<br[^>]*>/', $matches[1]);
                    $counter = 0;

                    foreach($rows as $row){
                        if(strpos($row, ': ')){
                            $val = explode(': ', $row);
                            $title = trim(strip_tags($val[0]));
                            $text = str_replace("&#039;", "'", html_entity_decode(strip_tags(trim($val[1]))));
                            if (isset($this->names[$title])){
                                $field = $this->names[$title];
                                if($field && $text)
                                    $data[$field[0]] = new ResultDataField(isset($field[3])?$field[3]:'string', $field[0], $text, $field[1], $field[2]);
                            } else {
                                $counter++;
                                if($text)
                                    $data['other'.$counter] = new ResultDataField('string', 'other'.$counter, $text, $title, $title);
//                                file_put_contents('./logs/fields/vkphone_'.time().'_'.$title , $title."\n".$text);
                            }
                        }
                    }
                    if (sizeof($rows)) $resultData->addResult($data);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                } elseif(strpos($content,"Текст введен не верно")!==false){
                    if (isset($swapData['session']))
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=4 WHERE id=" . $swapData['session']->id);
                    $rContext->setSleep(3);
                    return true;
                } else {
                    $error = 'Не удалось выполнить поиск';
                }
//            }
//            $rContext->setSwapData($swapData);
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
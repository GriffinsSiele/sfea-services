<?php

class FNPPlugin implements PluginInterface
{
    public function getName()
    {
        return 'FNP';
    }

    public function getTitle()
    {
        return 'Проверка по реестру залогов федеральной нотариальной палаты';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token FROM isphere.session WHERE sessionstatusid=2 AND sourceid=5 ORDER BY lasttime limit 1");

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
                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3 WHERE used=1 AND id=".$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if( !isset($initData['vin']) )
        {
            $rContext->setFinished();
            $rContext->setError('Указаны не все обязательные параметры (VIN)');

            return false;
        }

        $swapData['session'] = $this->getSessionData();

        $rContext->setSwapData($swapData);

        if(!$swapData['session'])
        {
//            $rContext->setFinished();
//            $rContext->setError('Нет актуальных сессий');
            $rContext->setSleep(3);
            return false;
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $url = 'https://www.reestr-zalogov.ru';

        if (!isset($swapData['verified'])) {
            $url .= '/Captcha/Verify';
            $params = array(
                'captchaDeText' => $swapData['session']->token,
                'captchaInputText' => $swapData['session']->code,
            );
            $header = array();
//            echo "POST {$url}: Token={$swapData['session']->token} Captcha={$swapData['session']->code}\n";
        } else {
            $url .= '/api/pledgesearch/ByVIN';
            $params = '{"Filter":{"VIN":"'.$initData['vin'].'","FormFields":["'.$initData['vin'].'"],"errors":[]},"PageSize":10,"PageIndex":0}';
            $header = array(
                'Content-Type: application/json; charset=utf-8',
                'X-Requested-With: XMLHttpRequest',
            );
//            echo "POST {$url}: $params\n";
        }


        curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        global $mysqli;
        $swapData = $rContext->getSwapData();
        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        $curlError = curl_error($rContext->getCurlHandler());

        if($curlError && $swapData['iteration']>10)
        {
            $rContext->setFinished();
            $rContext->setError($curlError==''?'Превышено количество попыток получения ответа':$curlError);

            return false;
        }

        $rContext->setSwapData($swapData);

        $content = curl_multi_getcontent($rContext->getCurlHandler());
//        file_put_contents('./logs/fnp/fnp_'.time().'.html',$content);
        $res = json_decode($content, true);
        echo "{$content}\n";

        if (!isset($swapData['verified'])) {
            if (isset($res['IsOk'])) {
                if($res['IsOk']) {
                    $swapData['verified'] = true;
                    $rContext->setSwapData($swapData);
                } else {
                    if (isset($swapData['session']))
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=4 WHERE id=" . $swapData['session']->id);
                    $rContext->setSleep(3);
                }
                return true;
            } else {
                if($swapData['iteration']>10) {
                    $rContext->setFinished();
                    $rContext->setError("Некорректный ответ сервиса");
                }
                return false;
            }
        } elseif (isset($res['InfoMessage']) && $res['InfoMessage']) {
            $data['Result'] = new ResultDataField('string','Result', $res['InfoMessage'], 'Результат проверки', 'Результат проверки');

            $resultData = new ResultDataList();
            $resultData->addResult($data);
            $rContext->setResultData($resultData);
            $rContext->setFinished();
        } elseif (isset($res['NeedCapcha']) && $res['NeedCapcha']) {
//            if (isset($swapData['session']))
//                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=4 WHERE id=" . $swapData['session']->id);
            $rContext->setSleep(3);
            unset($swapData['verified']);
            $rContext->setSwapData($swapData);
            return true;
        } elseif (isset($res['ErrorMessage']) && $res['ErrorMessage']) {
            $error = $res['ErrorMessage'];

            $rContext->setFinished();
            $rContext->setError(trim($error));
            return false;
        } else {
            if($swapData['iteration']>10) {
                $rContext->setFinished();
                $rContext->setError("Некорректный ответ сервиса");
            }
            return false;
        }
    }
}

?>
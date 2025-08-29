<?php

class YaMapPlugin_new1 implements PluginInterface
{
    public function __construct()
    {
    }

    public function getName()
    {
        return 'YaMap';
    }

    public function getTitle()
    {
        return 'Поиск в справочнике Яндекс.Карты';
    }

    public function getSessionData()
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

        $mysqli->query('UPDATE isphere.session s SET lasttime=now(),request_id='.$reqId.' WHERE request_id IS NULL AND sessionstatusid=2 AND sourceid=25 AND unix_timestamp(now())-unix_timestamp(lasttime)>5 ORDER BY lasttime limit 1');
        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sourceid=25 AND request_id=".$reqId);

        if ($result) {
            $row = $result->fetch_object();

            if ($row) {
                $sessionData = new \stdClass();

                $sessionData->id = $row->id;
                $sessionData->code = $row->captcha;
                $sessionData->token = $row->token;
                $sessionData->starttime = $row->starttime;
                $sessionData->lasttime = $row->lasttime;
                $sessionData->cookies = $row->cookies;
                $sessionData->proxyid = $row->proxyid;
                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = \strlen($row->proxy_auth) > 1 ? $row->proxy_auth : false;

                $mysqli->query('UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1,request_id=NULL WHERE id='.$sessionData->id);
                //                if ($sessionData->proxyid)
                //                    $mysqli->query("UPDATE isphere.proxy SET lasttime=now(),used=used+1 WHERE id=".$sessionData->proxyid);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        if (!isset($initData['url']) && !isset($initData['phone']) && !isset($initData['email'])) {
            $rContext->setFinished();
            $rContext->setError('Не указаны параметры для поиска (ссылка, телефон или email)');

            return false;
        }

        if (isset($initData['phone'])) {
            //            if (strlen($initData['phone'])==10)
            //                $initData['phone']='7'.$initData['phone'];
            //            if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
            //                $initData['phone']='7'.substr($initData['phone'],1);
            /*
                        if(substr($initData['phone'],0,1)!='7'){
                            $rContext->setFinished();
            //                $rContext->setError('Поиск производится только по российским телефонам');
                            return false;
                        }
            */
        } elseif (isset($initData['email'])) {
        }

        // //////////////////////////////////////////////////////////////////////////////////////////////////

        (!isset($swapData['iteration'])) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
        if (!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData();
            if (!$swapData['session']) {
                if (isset($swapData['iteration']) && ($swapData['iteration'] >= 10)) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                } else {
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);
                }

                return false;
            }
            $rContext->setSwapData($swapData);
        }

        // //////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $text = isset($initData['phone']) ? $initData['phone'] : (isset($initData['email']) ? $initData['email'] : $initData['url']);
        //        $params = array(
        //            'geo_id' => 225,
        //            'text' => $text,
        //            'region' => 'Россия',
        //            'country' => 'Россия',
        //        );
        $url = 'https://yandex.ru/maps/?text='.$text;
        //        $url = $page.'?'.http_build_query($params);
        $header = [
            'authority: yandex.ru',
            'pragma: no-cache',
            'cache-control: no-cache',
            'device-memory: 8',
            'dpr: 0.9',
            'viewport-width: 2133',
            'rtt: 50',
            'downlink: 10',
            'ect: 4g',
            'sec-ch-ua: " Not;A Brand";v="99", "Google Chrome";v="91", "Chromium";v="91"',
            'sec-ch-ua-mobile: ?0',
            'upgrade-insecure-requests: 1',
            'user-agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'sec-fetch-site: none',
            'sec-fetch-mode: navigate',
            'sec-fetch-user: ?1',
            'sec-fetch-dest: document',
            'accept-language: ru-RU,ru;q=0.9',
        ];

        \curl_setopt($ch, \CURLOPT_URL, $url);
        //        curl_setopt($ch, CURLOPT_REFERER, $page);
        \curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, 1);
        \curl_setopt($ch, \CURLOPT_ENCODING, '');
        \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
        \curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);
        \curl_setopt($ch, \CURLOPT_HEADER, true);
        \curl_setopt($ch, \CURLINFO_HEADER_OUT, true);

        if ($swapData['session']->proxy) {
            \curl_setopt($ch, \CURLOPT_PROXY, $swapData['session']->proxy);
            if ($swapData['session']->proxy_auth) {
                \curl_setopt($ch, \CURLOPT_PROXYUSERPWD, $swapData['session']->proxy_auth);
                \curl_setopt($ch, \CURLOPT_PROXYAUTH, \CURLAUTH_ANY);
            }
        }

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(array $params, &$rContext)
    {
        global $mysqli;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration'])) ? 1 : $swapData['iteration'] + 1;
        $rContext->setSwapData($swapData);

        $error = \curl_error($rContext->getCurlHandler());

        if ($error && ($swapData['iteration'] > 3)) {
            $rContext->setFinished();
            $rContext->setError($error);

            return false;
        }

        $content = \curl_multi_getcontent($rContext->getCurlHandler());
        \file_put_contents('./logs/yamap/yamap_'.\time().'.html', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);

        if ($content && \strpos($content, '<script type="application/json" class="config-view">')) {
            $resultData = new ResultDataList();

            if (\preg_match("/<script type=\"application\/json\" class=\"config-view\">(.*?)<\/script>/", $content, $matches)) {
                $data = \json_decode($matches[1], true);
                // print_r($data['searchPreloadedResults']);
            }

            if ($data['searchPreloadedResults']['totalResultCount'] > 0) {
                foreach ($data['searchPreloadedResults']['items'] as $item) {
                }
            }

            //            $parts = preg_split("/<div class=\"company-snippet__simple/",$content);
            //            if (sizeof($parts)>1) {
            //                array_shift($parts);
            //                foreach ($parts as $part) {
            //                    $data = array();
            //                    $phone_hit = false;
            //                    if (preg_match("/company-card__title\">([^<]+)/",$part,$matches))
            $data['name'] = new ResultDataField('string', 'name', $item['title'], 'Название', 'Название организации');
            //                    if (preg_match("/company-card__address\">([^<]+)/",$part,$matches))
            $data['address'] = new ResultDataField('address', 'address', $item['address'], 'Адрес', 'Адрес организации');
            //                    if (preg_match("/company-card__phone\">([^<]+)/",$part,$matches)) {
            //                        $phone = normal_phone($matches[1]);
            //                        if (isset($initData['phone']) && $initData['phone']==$phone) {
            //                            $data['phone'] = new ResultDataField('phone','phone',$phone,'Телефон','Телефон');
            //                            $phone_hit = true;
            //                        }
            //                    }
            //                      if (preg_match("/company-card__site\"><a [^>]+>([^<]+)/",$part,$matches))
            $data['url'] = new ResultDataField('url', 'url', $item['url'][0], 'Сайт', 'Сайт организации');
            //                    if (preg_match("/company-card__rubrics\">([^<]+)/",$part,$matches))
            //                      $data['categories'] = new ResultDataField('string','categories',$matches[1],'Категории','Категории организации');
            //                    if (isset($initData['phone']) && $phone_hit) {
            //                        $resultData->addResult($data);
            //                    }
            //                }
            //            }

            $rContext->setResultData($resultData);
            $rContext->setFinished();

            return true;
        } elseif ($content && !$error && $swapData['iteration'] >= 1) {
            $error = 'Невозможно обработать ответ';
            \file_put_contents('./logs/yamap/yamap_err_'.\time().'.html', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);
        }
        $rContext->setSwapData($swapData);

        if (!$error && $swapData['iteration'] > 1) {
            $error = 'Превышено количество попыток получения ответа';
        }

        if ($error && $swapData['iteration'] >= 1) {
            $rContext->setError($error);
            $rContext->setFinished();

            return false;
        }

        $rContext->setSleep(1);

        return true;
    }
}

<?php

class YaMapPlugin_old implements PluginInterface
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

        $mysqli->query('UPDATE isphere.session s SET lasttime=now(),request_id='.$reqId.' WHERE request_id IS NULL AND sessionstatusid=2 AND sourceid=25 AND unix_timestamp(now())-unix_timestamp(lasttime)>10 ORDER BY lasttime limit 1');
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

                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1,statuscode='used',request_id=NULL WHERE id=".$sessionData->id);
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
            if (10 == \strlen($initData['phone'])) {
                $initData['phone'] = '7'.$initData['phone'];
            }
            if ((11 == \strlen($initData['phone'])) && ('8' == \substr($initData['phone'], 0, 1))) {
                $initData['phone'] = '7'.\substr($initData['phone'], 1);
            }

            if ('7' != \substr($initData['phone'], 0, 1)) {
                $rContext->setFinished();
                //                $rContext->setError('Поиск производится только по российским телефонам');
                return false;
            }
        }

        // //////////////////////////////////////////////////////////////////////////////////////////////////

        (!isset($swapData['iteration'])) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
        if (!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData();
            if (!$swapData['session']) {
                if (isset($swapData['iteration']) && ($swapData['iteration'] >= 20)) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                } else {
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(3);
                }

                return false;
            }
            $rContext->setSwapData($swapData);
        }

        // //////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $text = isset($initData['phone']) ? $initData['phone'] : (isset($initData['email']) ? $initData['email'] : $initData['url']);
        $params = [
            'geo_id' => 225,
            'text' => $text,
            'region' => 'Россия',
            'country' => 'Россия',
        ];
        $page = 'https://yandex.ru/sprav/search';
        $url = $page.'?'.\http_build_query($params);
        $header = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
            'Connection: keep-alive',
            'TE: Trailers',
            'Upgrade-Insecure-Requests: 1',
        ];

        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_REFERER, $page);
        \curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, 1);
        \curl_setopt($ch, \CURLOPT_ENCODING, '');
        \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
        \curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);
        //        curl_setopt($ch, CURLOPT_HEADER, true);
        //        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

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
        //        file_put_contents('./logs/yamap/yamap_'.time().'.html',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);

        if ($content) {
            $cookies = str_cookies($swapData['session']->cookies);
            foreach (\curl_getinfo($rContext->getCurlHandler(), \CURLINFO_COOKIELIST) as $cookie) {
                //                print 'Response cookie '.$cookie."\n";
                $arr = \explode('	', $cookie);
                if (!isset($cookies[$arr[5]]) || $cookies[$arr[5]] != $arr[6]) {
                    $cookies[$arr[5]] = $arr[6];
                    //                    print 'New cookie '.$arr[5].' = '.$arr[6]."\n";
                }
            }
            $new_cookies = cookies_str($cookies);
            if ($swapData['session']->cookies != $new_cookies) {
                $swapData['session']->cookies = $new_cookies;
                $rContext->setSwapData($swapData);
                \file_put_contents('./logs/yamap/yamap_'.\time().'.cookies', $new_cookies);
                $mysqli->query("UPDATE isphere.session SET cookies='$new_cookies' WHERE id=".$swapData['session']->id);
            }
        }

        if ($content && \strpos($content, 'page-search__companies-found')) {
            $resultData = new ResultDataList();

            $parts = \preg_split('/<div class="company-snippet__simple/', $content);
            if (\count($parts) > 1) {
                \array_shift($parts);
                foreach ($parts as $part) {
                    $data = [];
                    $phone_hit = false;
                    if (\preg_match('/company-card__title">([^<]+)/', $part, $matches)) {
                        $data['name'] = new ResultDataField('string', 'name', $matches[1], 'Название', 'Название организации');
                    }
                    if (\preg_match('/company-card__address">([^<]+)/', $part, $matches)) {
                        $data['address'] = new ResultDataField('address', 'address', $matches[1], 'Адрес', 'Адрес организации');
                    }
                    if (\preg_match('/company-card__phone">([^<]+)/', $part, $matches)) {
                        $phone = normal_phone($matches[1]);
                        if (isset($initData['phone']) && $initData['phone'] == $phone) {
                            $data['phone'] = new ResultDataField('phone', 'phone', $phone, 'Телефон', 'Телефон');
                            $phone_hit = true;
                        }
                    }
                    if (\preg_match('/company-card__site"><a [^>]+>([^<]+)/', $part, $matches)) {
                        $data['url'] = new ResultDataField('url', 'url', $matches[1], 'Сайт', 'Сайт организации');
                    }
                    if (\preg_match('/company-card__rubrics">([^<]+)/', $part, $matches)) {
                        $data['categories'] = new ResultDataField('string', 'categories', $matches[1], 'Категории', 'Категории организации');
                    }
                    if (isset($initData['phone']) && $phone_hit) {
                        $resultData->addResult($data);
                    }
                }
            }
            $rContext->setResultData($resultData);
            $rContext->setFinished();

            $mysqli->query('INSERT INTO isphere.proxyusage (sourceid,proxyid,success) VALUES(25,'.$swapData['session']->proxyid.',1)');
            $mysqli->query("UPDATE isphere.session SET successtime=now(),success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);

            return true;
        } elseif (!$content || \strpos($content, 'не робот')) {
            $mysqli->query('INSERT INTO isphere.proxyusage (sourceid,proxyid,success) VALUES(25,'.$swapData['session']->proxyid.',0)');
            $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE enabled=1 AND status=1 AND (rotation>0 OR id NOT IN (SELECT proxyid FROM proxysourcestats WHERE sourceid=25 AND successrate<0.1)) ORDER BY lasttime limit 1");
            if ($result) {
                $row = $result->fetch_object();
                if ($row) {
                    $swapData['session']->proxyid = $row->proxyid;
                    $swapData['session']->proxy = $row->proxy;
                    $swapData['session']->proxy_auth = \strlen($row->proxy_auth) > 1 ? $row->proxy_auth : false;

                    $mysqli->query('UPDATE isphere.proxy SET lasttime=now() WHERE id='.$row->proxyid);
                    $mysqli->query('UPDATE isphere.session SET lasttime=now(),proxyid='.$row->proxyid.' WHERE id='.$swapData['session']->id);
                }
            }
        } elseif ($content && !$error && $swapData['iteration'] >= 1) {
            $error = 'Невозможно обработать ответ';
            \file_put_contents('./logs/yamap/yamap_err_'.\time().'.html', \curl_getinfo($rContext->getCurlHandler(), \CURLINFO_HEADER_OUT)."\r\n".$content);
        }
        $rContext->setSwapData($swapData);

        if (!$error && $swapData['iteration'] > 10) {
            $error = 'Превышено количество попыток получения ответа';
        }

        if ($error && $swapData['iteration'] >= 1) {
            $rContext->setError($error);
            $rContext->setFinished();

            return false;
        }

        $rContext->setSleep(3);

        return true;
    }
}

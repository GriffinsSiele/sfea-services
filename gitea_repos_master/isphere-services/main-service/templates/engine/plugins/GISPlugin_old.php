<?php

class GISPlugin_old implements PluginInterface
{
    private $phone2reg = [];
    private $reg2gis = [];

    private $days_ru = [
                               'Mon' => 'Пн',
                               'Tue' => 'Вт',
                               'Wed' => 'Ср',
                               'Thu' => 'Чт',
                               'Fri' => 'Пт',
                               'Sat' => 'Сб',
                               'Sun' => 'Вс',
                       ];

    public function __construct()
    {
        //           require(__DIR__.'/list.txt');
        $tmparray = \file(__DIR__.'/list.txt');
        foreach ($tmparray as $val) {
            $tmparr = \explode('|', \trim($val));
            $tmpcodes = \explode(',', $tmparr[3]);
            $tmpreg = \explode(',', $tmparr[2]);
            foreach ($tmpcodes as $v) {
                if (!isset($this->phone2reg[$v])) {
                    $this->phone2reg[$v][] = $tmparr[0];
                } else {
                    if (!\in_array($tmparr[0], $this->phone2reg[$v])) {
                        $this->phone2reg[$v][] = $tmparr[0];
                    }
                }
            }
            foreach ($tmpreg as $vv) {
                if (!isset($this->reg2gis[$vv])) {
                    $this->reg2gis[$vv][] = $tmparr[0];
                } else {
                    if (!\in_array($tmparr[0], $this->reg2gis[$vv])) {
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

    public function getTitle($checktype = '')
    {
        $title = [
            '' => 'Поиск в справочнике 2ГИС',
            '2gis_phone' => 'Поиск в справочнике 2ГИС',
            '2gis_phone_kz' => 'Поиск в справочнике 2ГИС (Казахстан)',
        ];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск в справочнике 2ГИС';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        //        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND sourceid=0 ORDER BY lasttime limit 1");
        $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE status=1 ORDER BY lasttime limit 1");

        if ($result) {
            $row = $result->fetch_object();

            if ($row) {
                $sessionData = new \stdClass();

                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = \strlen($row->proxy_auth) > 1 ? $row->proxy_auth : false;
                /*
                                $sessionData->id = $row->id;
                                $sessionData->code = $row->captcha;
                                $sessionData->token = $row->token;
                                $sessionData->starttime = $row->starttime;
                                $sessionData->lasttime = $row->lasttime;
                                $sessionData->cookies = $row->cookies;

                                $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1 WHERE id=".$sessionData->id);
                */
                $mysqli->query('UPDATE isphere.proxy SET lasttime=now() WHERE id='.$row->proxyid);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        global $mysqli;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = \substr($initData['checktype'], 5);

        if (!isset($initData['phone'])) {
            $rContext->setFinished();
            $rContext->setError('Не задан номер телефона');

            return false;
        }

        if (10 == \strlen($initData['phone'])) {
            $initData['phone'] = '7'.$initData['phone'];
        }
        if ((11 == \strlen($initData['phone'])) && ('8' == \substr($initData['phone'], 0, 1))) {
            $initData['phone'] = '7'.\substr($initData['phone'], 1);
        }

        /*
                if(substr($initData['phone'],0,1)!='7'){
                    $rContext->setFinished();
        //            $rContext->setError('Поиск производится только по российским и казахстанским телефонам');
                    return false;
                }
        */

        if ('phone' == $checktype && !\preg_match('/7[3489]/', \substr($initData['phone'], 0, 2))) {
            $rContext->setFinished();
            //            $rContext->setError('Поиск производится только российским телефонам');
            return false;
        }

        if ('phone_kz' == $checktype && !\preg_match('/7[678]/', \substr($initData['phone'], 0, 2))) {
            $rContext->setFinished();
            //            $rContext->setError('Поиск производится только казастанским телефонам');
            return false;
        }

        $swapData['session'] = $this->getSessionData();
        $rContext->setSwapData($swapData);
        if (!$swapData['session']) {
            //            $rContext->setFinished();
            //            $rContext->setError('Нет актуальных сессий');
            $rContext->setSleep(1);

            return false;
        }

        // //////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $url = 'https://2gis.ru/search/'.$initData['phone'];
        // $url = 'http://2gis.'.($checktype=='phone_kz'?'kz':'ru').'/search/'.$initData['phone'];

        $header = [
            'authority: 2gis.ru',
            'pragma: no-cache',
            'cache-control: no-cache',
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
        //	echo $url;
        //	exit;

        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);
        \curl_setopt($ch, \CURLOPT_SSL_VERIFYPEER, false);
        \curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, 1);
        if ($swapData['session']->proxy) {
            \curl_setopt($ch, \CURLOPT_PROXY, $swapData['session']->proxy);
            if ($swapData['session']->proxy_auth) {
                \curl_setopt($ch, \CURLOPT_PROXYUSERPWD, $swapData['session']->proxy_auth);
                \curl_setopt($ch, \CURLOPT_PROXYAUTH, \CURLAUTH_ANY);
            }
        }
        \curl_setopt($ch, \CURLOPT_ENCODING, '');

        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $swapData['iteration'] = (!isset($swapData['iteration'])) ? 1 : $swapData['iteration'] + 1;
        $rContext->setSwapData($swapData);

        $curlError = \curl_error($rContext->getCurlHandler());
        if ($curlError && ($swapData['iteration'] > 3)) {
            $rContext->setFinished();
            $rContext->setError('' == $curlError ? 'Unknown Error' : $curlError);

            return false;
        } else {
            if (!$curlError) {
                $content = \curl_multi_getcontent($rContext->getCurlHandler());
            }
        }

        //        file_put_contents('./logs/2gis/2gis'.time().'.html',$content);
        $resultData = new ResultDataList();

        //        if (preg_match("/var initialState \= (\{.*?\}); /",$content,$matches)) {
        if (\preg_match("/var initialState \= JSON\.parse\(\'([^\']+)\'/si", $content, $matches)) {
            $gisres = \json_decode($matches[1], true);
            if (isset($gisres['data']['entity']['profile'])) {
                foreach ($gisres['data']['entity']['profile'] as $profile) {
                    $orgdata = $profile['data'];
                    if (\array_key_exists('name', $orgdata)) {
                        $data['name'] = new ResultDataField('string', 'name', $orgdata['name'], 'Название', 'Название организации');
                    }
                    if (\array_key_exists('address', $orgdata)) {
                        $data['address'] = new ResultDataField('string', 'address', $orgdata['address']['postcode'].', './* $orgdata['adm_div']['0']['name'].', '. */ $orgdata['address_name'], 'Адрес', 'Адрес организации');
                    }
                    if (\array_key_exists('address_comment', $orgdata)) {
                        $data['address_comment'] = new ResultDataField('string', 'address_comment', $orgdata['address_comment'], 'Примечание к адресу', 'Примечание к адресу организации');
                    }
                    if (\array_key_exists('point', $orgdata)) {
                        //                            $data['latitude'] = new ResultDataField('string','latitude',$orgdata['point']['lat'],'Широта','Широта');
                        //                            $data['longitude'] = new ResultDataField('string','longitude',$orgdata['point']['lon'],'Долгота','Долгота');
                        $map = [['coords' => [$orgdata['point']['lat'], $orgdata['point']['lon']], 'text' => /* $orgdata['adm_div']['0']['name'].', '. */ $orgdata['address_name']]];
                        $data['address_map'] = new ResultDataField('map', 'address_map', \json_encode($map, \JSON_UNESCAPED_UNICODE), 'Местоположение', 'Местоположение');
                    }
                    if (\array_key_exists('contact_groups', $orgdata) && \count($orgdata['contact_groups'])) {
                        $contacts = [];
                        foreach ($orgdata['contact_groups'] as $contact_groups) {
                            $contact_types = [
                                'phone' => ['Телефон', 'phone'],
                                'fax' => ['Факс', 'phone'],
                                'website' => ['Сайт', 'url'],
                                'facebook' => ['Страница в Facebook', 'url'],
                                'vkontakte' => ['Страница в VK', 'url'],
                                'twitter' => ['Канал в Twitter', 'url'],
                            ];
                            foreach ($contact_groups['contacts'] as $contact) {
                                if (\array_key_exists($contact['type'], $contact_types)) {
                                    $data[$contact['type']] = new ResultDataField($contact_types[$contact['type']][1], $contact['type'], \str_replace(['+', '(', ')', '-', '‒', ' '], '', $contact['text']), $contact_types[$contact['type']][0], $contact_types[$contact['type']][0]);
                                }
                            }
                        }
                        //                            $data['contacts'] = new ResultDataField('string','contacts',implode($contacts, ', '),'Контакты','Контакты организации');
                    }
                    if (\array_key_exists('schedule', $orgdata)) {
                        $schedule = [];
                        foreach ($orgdata['schedule'] as $key => $val) {
                            if (isset($this->days_ru[$key])) {
                                $schedule[] = $this->days_ru[$key].' '.$val['working_hours']['0']['from'].'-'.$val['working_hours']['0']['to'];
                            }
                        }
                        $data['hours'] = new ResultDataField('string', 'hours', \implode(', ', $schedule), 'График', 'График работы организации');
                    }
                    if (\array_key_exists('rubrics', $orgdata) && \count($orgdata['rubrics'])) {
                        $categories = [];
                        foreach ($orgdata['rubrics'] as $cat) {
                            $categories[] = $cat['name'];
                        }
                        $data['categories'] = new ResultDataField('string', 'categories', \implode(', ', $categories), 'Категории', 'Категории организации');
                    }
                    $resultData->addResult($data);
                }
            } elseif (isset($gisres['error']['message'])) {
                $rContext->setError($gisres['error']['message']);
            } else {
                $rContext->setError('Некорректный ответ сервиса');
            }

            $rContext->setResultData($resultData);
            $rContext->setFinished();
        } elseif ($swapData['iteration'] > 3) {
            \file_put_contents('./logs/2gis/2gis_err_'.\time().'.html', $content);
            $rContext->setError('Ошибка при выполнении запроса');
            $rContext->setFinished();
        }
    }
}

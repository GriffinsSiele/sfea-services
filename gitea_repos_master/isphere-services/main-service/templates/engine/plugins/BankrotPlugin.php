<?php

use Doctrine\DBAL\Connection;

class BankrotPlugin implements PluginInterface
{
    private $titles = ['lastname' => ['Фамилия', 'Фамилия'], 'firstname' => ['Имя', 'Имя'], 'middlename' => ['Отчество', 'Отчество'], 'birthdate' => ['Дата рождения', 'Дата рождения'], 'birthplace' => ['Место рождения', 'Место рождения'], 'phone' => ['Телефон', 'Телефон'], 'region' => ['Регион', 'Регион'], 'address' => ['Адрес', 'Адрес'], 'inn' => ['ИНН', 'ИНН'], 'ogrn' => ['ОГРН', 'ОГРН'], 'snils' => ['СНИЛС', 'СНИЛС'], 'namehistory' => ['Предыдущее ФИО', 'Предыдущее ФИО'], 'categoryname' => ['Категория', 'Категория'], 'advinfo' => ['Дополнительная информация', 'Дополнительная информация']];
    private $bankrot_regions = ['01' => '79', '02' => '80', '03' => '81', '04' => '84', '05' => '82', '06' => '26', '07' => '83', '08' => '85', '09' => '91', '10' => '86', '11' => '87', '12' => '88', '13' => '89', '14' => '98', '15' => '102', '16' => '92', '17' => '93', '18' => '94', '19' => '95', '20' => '96', '21' => '97', '22' => '01', '23' => '03', '24' => '04', '25' => '05', '26' => '07', '27' => '08', '28' => '10', '29' => '11', '30' => '12', '31' => '14', '32' => '15', '33' => '17', '34' => '18', '35' => '19', '36' => '20', '37' => '24', '38' => '25', '39' => '27', '40' => '29', '41' => '30', '42' => '32', '43' => '33', '44' => '34', '45' => '37', '46' => '38', '47' => '41', '48' => '42', '49' => '44', '50' => '46', '51' => '47', '52' => '22', '53' => '49', '54' => '50', '55' => '52', '56' => '53', '57' => '54', '58' => '56', '59' => '57', '60' => '58', '61' => '60', '62' => '61', '63' => '36', '64' => '63', '65' => '64', '66' => '65', '67' => '66', '68' => '68', '69' => '28', '70' => '69', '71' => '70', '72' => '71', '73' => '73', '74' => '75', '75' => '101', '76' => '78', '77' => '45', '78' => '40', '79' => '99', '80' => '101', '81' => '57', '82' => '30', '83' => '200', '84' => '04', '85' => '25', '86' => '103', '87' => '77', '88' => '04', '89' => '104', '91' => '35', '92' => '201', '99' => '203'];

    public function getName()
    {
        return 'Bankrot';
    }

    public function getTitle($checktype = '')
    {
        $title = ['' => 'Проверка по реестру банкротов', 'bankrot_person' => 'Проверка физлица на банкротство по ЕФРСБ', 'bankrot_inn' => 'Проверка ИНН физлица на банкротство по ЕФРСБ', 'bankrot_org' => 'Проверка организации на банкротство по ЕФРСБ'];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Проверка по реестру банкротов';
    }

    public function getSessionData(array $params)
    {
        $connection = $params['_connection'];
        \assert($connection instanceof Connection);
        $reqId = $params['_reqId'];
        $sessionData = null;
        $connection->executeStatement('UPDATE session s SET lasttime=now(),request_id='.$reqId.' WHERE request_id IS NULL AND sessionstatusid=2 AND sourceid=8 AND unix_timestamp(now())-unix_timestamp(lasttime)>5 ORDER BY lasttime limit 1');
        $result = $connection->executeQuery("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM session s WHERE sourceid=8 AND request_id=".$reqId);
        //        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM session s WHERE sourceid=8 AND sessionstatusid=2 ORDER BY lasttime limit 1");
        if ($result) {
            $row = $result->fetchAssociative();
            if ($row) {
                $sessionData = new \stdClass();
                $sessionData->id = $row['id'];
                //                $sessionData->code = $row['captcha'];
                //                $sessionData->token = $row['token'];
                //                $sessionData->starttime = $row['starttime'];
                //                $sessionData->lasttime = $row['lasttime'];
                $sessionData->cookies = $row['cookies'];
                $sessionData->proxyid = $row['proxyid'];
                $sessionData->proxy = $row['proxy'];
                $sessionData->proxy_auth = \strlen($row['proxy_auth']) > 1 ? $row['proxy_auth'] : false;
                $connection->executeStatement("UPDATE session SET statuscode='used',lasttime=now(),used=ifnull(used,0)+1,request_id=NULL WHERE id=".$sessionData->id);
                if (!$row['proxyid']) {
                    //                    $result = $mysqli->query("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM proxy WHERE enabled=1 AND status=1 AND country='ru' AND rotation>0 ORDER BY lasttime limit 1");
                    $result = $connection->executeQuery("SELECT id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM proxy WHERE enabled=1 AND status=1 AND country='ru' ORDER BY lasttime limit 1");
                    if ($result) {
                        $row = $result->fetchAssociative();
                        if ($row) {
                            $sessionData->proxyid = $row['proxyid'];
                            $sessionData->proxy = $row['proxy'];
                            $sessionData->proxy_auth = \strlen($row['proxy_auth']) > 1 ? $row['proxy_auth'] : false;
                            //                            $mysqli->query("UPDATE proxy SET lasttime=now() WHERE id=".$row['proxyid']);
                            $connection->executeStatement('UPDATE session SET proxyid='.$row['proxyid'].' WHERE id='.$sessionData->id);
                        }
                    }
                }
                //                if ($sessionData->proxyid)
                //                    $mysqli->query("UPDATE proxy SET lasttime=now(),used=used+1 WHERE id=".$sessionData->proxyid);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        $checktype = \substr($initData['checktype'], 8);
        if ('person' == $checktype && (!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date']))) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (фамилия, имя и дата рождения)');
            return false;
        }
        if ('org' == $checktype && !isset($initData['name']) && !isset($initData['inn'])) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (название или ИНН организации)');
            return false;
        }
        if ('inn' == $checktype && !isset($initData['inn'])) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (ИНН физлица)');
            return false;
        }
        if ('inn' == $checktype && isset($initData['inn']) && !\preg_match('/\\d{12}/', $initData['inn'])) {
            $rContext->setFinished();
            $rContext->setError('ИНН физлица должен содержать 12 цифр');

            return false;
        }
        if ('org' == $checktype && isset($initData['inn']) && !\preg_match('/\\d{10}/', $initData['inn'])) {
            $rContext->setFinished();
            $rContext->setError('ИНН юрлица должен содержать 10 цифр');

            return false;
        }
        if ('person' == $checktype && isset($initData['last_name']) && isset($initData['first_name']) && \preg_match('/[^А-Яа-яЁё\\s\\-\\.]/ui', $initData['last_name'].' '.$initData['first_name'].(isset($initData['patronymic']) ? ' '.$initData['patronymic'] : ''))) {
            $rContext->setFinished();
            $rContext->setError('Имя может содержать только русские буквы');

            return false;
        }
        /*
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
                return false;
        */
        // //////////////////////////////////////////////////////////////////////////////////////////////////
        !isset($swapData['iteration']) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
        if (!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData($params);
            if (!$swapData['session']) {
                if (isset($swapData['iteration']) && $swapData['iteration'] >= 20) {
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
        $post = false;
        if (isset($swapData['cookieparams'])) {
            //            $url = "https://src2.i-sphere.ru/fed/?a=".$swapData['cookieparams'][0]."&b=".$swapData['cookieparams'][1]."&c=".$swapData['cookieparams'][2];
            //            $url = "http://10.10.10.1:8038/?a=".$swapData['cookieparams'][0]."&b=".$swapData['cookieparams'][1]."&c=".$swapData['cookieparams'][2];
            $url = 'http://172.16.1.25'.(3 + $swapData['iteration'] % 2).':8038/?a='.$swapData['cookieparams'][0].'&b='.$swapData['cookieparams'][1].'&c='.$swapData['cookieparams'][2];
            \curl_setopt($ch, \CURLOPT_SSL_VERIFYPEER, false);
        } elseif (isset($swapData['pagetype'])) {
            $header = ['Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3', 'Referer: '.$swapData['url'][$swapData['urlnum']], 'Origin: https://old.bankrot.fedresurs.ru', 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8', 'X-Requested-With: XMLHttpRequest', 'X-MicrosoftAjax: Delta=true'];
            \curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);
            //            curl_setopt($ch, CURLOPT_HEADER, true);
            //            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            $post = ['ctl00$PrivateOffice1$ctl00' => 'ctl00$cphBody$'.('Messages' == $swapData['pagetype'] ? 'MessagesUpdatePanel' : 'upKadDocuments').'|ctl00$cphBody$gv'.$swapData['pagetype'], '__EVENTTARGET' => 'ctl00$cphBody$gv'.$swapData['pagetype'], '__EVENTARGUMENT' => 'Page$'.('Messages' == $swapData['pagetype'] ? $swapData['msgpage'] : $swapData['docpage']), '__ASYNCPOST' => 'true'];
            $url = $swapData['url'][$swapData['urlnum']].'&attempt=1';
            \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
        } elseif (isset($swapData['url'])) {
            $url = $swapData['url'][$swapData['urlnum']].'&attempt=1';
            $header = ['Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3', 'Referer: https://old.bankrot.fedresurs.ru/DebtorsSearch.aspx', 'Origin: https://old.bankrot.fedresurs.ru'];
            \curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);
            //            curl_setopt($ch, CURLOPT_HEADER, true);
            //            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
        } elseif (1) {
            // (isset($initData['inn'])) {
            $url = 'https://old.bankrot.fedresurs.ru/DebtorsSearch.aspx'.($swapData['session']->cookies ? '?attempt=1' : '');
            $header = ['Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3', 'Referer: https://old.bankrot.fedresurs.ru/DebtorsSearch.aspx', 'Origin: https://old.bankrot.fedresurs.ru'];
            \curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);
            //            curl_setopt($ch, CURLOPT_HEADER, true);
            //            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            $params = ['typeofsearch' => 'org' == $checktype ? 'Organizations' : 'Persons', 'orgname' => 'org' == $checktype && isset($initData['name']) ? $initData['name'] : '', 'orgaddress' => '', 'orgregionid' => isset($initData['region_id']) && isset($this->bankrot_regions[$initData['region_id']]) ? $this->bankrot_regions[$initData['region_id']] : '', 'orgogrn' => '', 'orginn' => 'org' == $checktype && isset($initData['inn']) ? $initData['inn'] : '', 'orgokpo' => '', 'OrgCategory' => '', 'prslastname' => 'person' == $checktype && isset($initData['last_name']) ? $initData['last_name'] : '', 'prsfirstname' => 'person' == $checktype && isset($initData['first_name']) ? $initData['first_name'] : '', 'prsmiddlename' => 'person' == $checktype && isset($initData['patronymic']) ? $initData['patronymic'] : '', 'prsaddress' => '', 'prsregionid' => isset($initData['region_id']) && isset($this->bankrot_regions[$initData['region_id']]) ? $this->bankrot_regions[$initData['region_id']] : '', 'prsinn' => 'inn' == $checktype && isset($initData['inn']) ? $initData['inn'] : '', 'prsogrn' => '', 'prssnils' => '', 'PrsCategory' => '', 'pagenumber' => 0];
            if ($swapData['session']->cookies) {
                $cookies = \App\Utils\Legacy\CookieUtilStatic::str_cookies($swapData['session']->cookies);
                $cookies['debtorsearch'] = \http_build_query($params);
                $swapData['session']->cookies = \App\Utils\Legacy\CookieUtilStatic::cookies_str($cookies);
                \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
                //                echo "Cookie: ".$swapData['session']->cookies."\n";
            }
        } else {
            $url = 'https://i-sphere.ru';
        }
        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_TIMEOUT, 5);
        //        echo "$url\n";
        if (\is_array($post)) {
            \curl_setopt($ch, \CURLOPT_POST, true);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, \http_build_query($post));
            //            var_dump($post); echo "\n";
        }
        if ($swapData['session']->proxy && !isset($swapData['cookieparams'])) {
            \curl_setopt($ch, \CURLOPT_PROXY, $swapData['session']->proxy);
            //            echo "Proxy: ".$swapData['session']->proxy."\n";
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
        $connection = $params['_connection'];
        \assert($connection instanceof Connection);
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        $checktype = \substr($initData['checktype'], 8);
        $error = false;
        !isset($swapData['iteration']) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
        $rContext->setSwapData($swapData);
        $content = \curl_multi_getcontent($rContext->getCurlHandler());
        //        \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/fedresurs/bankrot_'.time().'.html',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
        if (!$content && !isset($swapData['cookieparams'])) {
            $connection->executeStatement('UPDATE session SET proxyid=NULL,unlocktime=date_add(now(),interval '.($swapData['session']->proxyid < 100 ? '30 second' : '5 minute')."),sessionstatusid=6,statuscode='empty' WHERE statuscode='used' AND id=".$swapData['session']->id);
            unset($swapData['session']);
            $rContext->setSwapData($swapData);
            $rContext->setSleep(1);
            if ($swapData['iteration'] > 20) {
                $rContext->setFinished();
                $rContext->setError('Сервис не отвечает');
            }

            return true;
        }
        if (\strpos($content, 'setting cookie...') && \preg_match_all('/\\=toNumbers\\("([^"]+)/sim', $content, $matches)) {
            //            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/fedresurs/bankrot_start_'.time().'.html',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
            $swapData['cookieparams'] = $matches[1];
            $rContext->setSwapData($swapData);
        } elseif (isset($swapData['cookieparams'])) {
            //            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/fedresurs/bankrot_cookie_'.time().'.txt',$content);
            if (\strlen($content) < 10) {
                $connection->executeStatement("UPDATE session SET proxyid=NULL,unlocktime=date_add(now(),interval 15 minute),sessionstatusid=6,statuscode='cookieerror' WHERE statuscode='used' AND id=".$swapData['session']->id);
                unset($swapData['session']);
                unset($swapData['cookieparams']);
                $rContext->setSwapData($swapData);
            } elseif (false === \strpos($content, '>')) {
                $swapData['session']->cookies = 'bankrotcookie='.$content;
                unset($swapData['cookieparams']);
                $rContext->setSwapData($swapData);
                $connection->executeStatement("UPDATE session SET cookies='".$swapData['session']->cookies."' WHERE id=".$swapData['session']->id);
            }
        } elseif (isset($swapData['url'])) {
            //            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/fedresurs/bankrot_'.$checktype.'_'.$swapData['urlnum'].'_'.(isset($swapData['pagetype'])?$swapData['pagetype'].'_'.($swapData['pagetype']=='messages'?$swapData['msgpage']:$swapData['docpage']).'_':'').time().'.html',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
            $resultData = $swapData['result'];
            $data = isset($swapData['data']) ? $swapData['data'] : [];
            if (isset($swapData['pagetype'])) {
            } elseif (\preg_match_all('/<span id="ctl00_cphBody_lbl([^"]+)[^>]+>(.*?)<\\/span>/', $content, $matches)) {
                foreach ($matches[1] as $i => $key) {
                    $key = \strtolower($key);
                    $vals = \explode('<br/>', $matches[2][$i]);
                    foreach ($vals as $i => $val) {
                        $val = \trim(\strip_tags($val));
                        if ($val && '(н/д)' != $val && isset($this->titles[$key][0])) {
                            $data[$key.($i ?: '')] = new ResultDataField('string', $key, $val, $this->titles[$key][0], $this->titles[$key][1]);
                        }
                    }
                }
                if (isset($data['lastname'])) {
                    $name = \trim($data['lastname']->getValue().' '.$data['firstname']->getValue().(isset($data['middlename']) ? ' '.$data['middlename']->getValue() : ''));
                    $data['name'] = new ResultDataField('string', 'name', $name, 'ФИО должника', 'ФИО должника');
                }
                $data['url'] = new ResultDataField('url', 'url', $swapData['url'][$swapData['urlnum']], 'Карточка должника', 'Карточка должника');
                $swapData['msgpage'] = 1;
                if (\preg_match('/ctl00_cphBody_paiMessages_tdPaggingAdvInfo[^>]+[^:]+[^\\d]+([\\d]+)/', $content, $matches)) {
                    $data['msgcount'] = new ResultDataField('string', 'msgcount', (int) $matches[1], 'Количество сообщений', 'Количество сообщений');
                    $swapData['msgpages'] = 1 + (int) (((int) $matches[1] - 1) / 20);
                    //                    echo $matches[1]." messages ".$swapData['msgpages']." pages\n";
                }
                $swapData['docpage'] = 1;
                if (\preg_match('/ctl00_cphBody_paiKadDocuments_tdPaggingAdvInfo[^>]+[^:]+[^\\d]+([\\d]+)/', $content, $matches)) {
                    $data['doccount'] = new ResultDataField('string', 'doccount', (int) $matches[1], 'Количество документов', 'Количество документов');
                    $swapData['docpages'] = 1 + (int) (((int) $matches[1] - 1) / 20);
                    //                    echo $matches[1]." docs ".$swapData['docpages']." pages\n";
                }
            } else {
                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/fedresurs/bankrot_err_'.\time().'.html', $content);
                $error = 'Ошибка при обработке ответа';
            }
            if (\preg_match_all("/<tr[^<]+<td[^>]*>([^<]+)<\\/td[^<]+<td[^<]+<a title=\"[^\"]+\" href=\\'([^\\']+)\\'[^>]*>([^<]+)<\\/a[^<]+<\\/td[^<]+<td[^<]+<a title=\"[^\"]+\" href=\"([^\"]*)\"[^>]*>([^<]*)<\\/a[^<]+<\\/td[^<]+<td[^<]+<a id=\"[^\"]+\" title=\"[^\"]+\" href=\"([^\"]*)\"[^>]*>([^<]*)<\\/a[^<]+<\\/td[^<]+<\\/tr>/si", $content, $matches) || \preg_match_all("/<tr[^<]+<td[^>]*>([^<]+)<\\/td[^<]+<td[^<]+<a title=\"[^\"]+\" href=\\'([^\\']+)\\'[^>]*>([^<]+)<\\/a[^<]+<\\/td[^<]+<td[^<]+(.*?)<\\/td[^<]+<td[^<]+(.*?)<\\/td[^<]+<\\/tr>/si", $content, $matches)) {
                foreach ($matches[1] as $key => $val) {
                    if (1 == $swapData['msgpage'] && 0 == $key) {
                        $data['lastmsgdate'] = new ResultDataField('string', 'lastmsgdate', \trim(\html_entity_decode($matches[1][$key])), 'Дата и время последнего сообщения', 'Дата и время последнего сообщения');
                        $data['lastmsgtitle'] = new ResultDataField('string', 'lastmsgtitle', \trim(\html_entity_decode($matches[3][$key])), 'Наименование последнего сообщения', 'Наименование последнего сообщения');
                        $data['lastmsgurl'] = new ResultDataField('url', 'lastmsgurl', 'https://old.bankrot.fedresurs.ru'.\trim(\html_entity_decode($matches[2][$key])), 'Последнее сообщение', 'Последнее сообщение');
                    }
                    if ($key == \count($matches[1]) - 1) {
                        /*
                                                $data['firstmsgdate'] = new ResultDataField('string','firstmsgdate',trim(html_entity_decode($matches[1][$key])),'Дата и время первого сообщения','Дата и время первого сообщения');
                                                $data['firstmsgtitle'] = new ResultDataField('string','fistmsgtitle',trim(html_entity_decode($matches[3][$key])),'Наименование первого сообщения','Наименование первого сообщения');
                                                $data['firstmsgurl'] = new ResultDataField('url','firstmsgurl','https://old.bankrot.fedresurs.ru'.trim(html_entity_decode($matches[2][$key])),'Первое сообщение','Первое сообщение');
                        */
                        if (7 == \count($matches) || !\preg_match('/возврат/si', \trim(\html_entity_decode($matches[3][$key])))) {
                            $url = 'https://old.bankrot.fedresurs.ru'.\trim(\html_entity_decode($matches[2][$key]));
                            $data['publicationdate'] = new ResultDataField('string', 'publicationdate', \trim(\html_entity_decode($matches[1][$key])), 'Дата и время публикации', 'Дата и время публикации');
                            $data['publicationtitle'] = new ResultDataField('string', 'publicationtitle', \trim(\html_entity_decode($matches[3][$key])), 'Наименование решения', 'Наименование решения');
                            $data['publicationurl'] = new ResultDataField('url', 'publicationurl', $url, 'Сообщение о решении', 'Сообщение о решении');
                        } else {
                            $url = 'https://old.bankrot.fedresurs.ru'.\trim(\html_entity_decode($matches[2][$key]));
                            $data['refusaldate'] = new ResultDataField('string', 'refusaldate', \trim(\html_entity_decode($matches[1][$key])), 'Дата и время отказа', 'Дата и время отказа');
                            $data['refusaltitle'] = new ResultDataField('string', 'refusaltitle', \trim(\html_entity_decode($matches[3][$key])), 'Наименование отказа', 'Наименование отказа');
                            $data['refusalurl'] = new ResultDataField('url', 'refusalurl', $url, 'Сообщение об отказе', 'Сообщение об отказе');
                        }
                        if (7 == \count($matches) && \trim(\html_entity_decode($matches[5][$key]))) {
                            $url = 'https://old.bankrot.fedresurs.ru'.\trim(\html_entity_decode($matches[4][$key]));
                            $data['arbitrmanager'] = new ResultDataField('string', 'arbitrmanager', \trim(\html_entity_decode($matches[5][$key])), 'Арбитражный управляющий', 'Арбитражный управляющий');
                            $data['arbitrmanagercard'] = new ResultDataField('url', 'arbitrmanagercard', $url, 'Карточка арбитражного управляющего', 'Карточка арбитражного управляющего');
                        }
                        if (7 == \count($matches) && \trim(\html_entity_decode($matches[7][$key]))) {
                            $url = 'https://old.bankrot.fedresurs.ru'.\trim(\html_entity_decode($matches[6][$key]));
                            $data['sro'] = new ResultDataField('string', 'sro', \trim(\html_entity_decode($matches[7][$key])), 'СРО', 'СРО');
                            $data['srocard'] = new ResultDataField('url', 'srocard', $url, 'Карточка СРО', 'Карточка СРО');
                        }
                        if (\count($matches) < 7 && \trim(\html_entity_decode(\strip_tags($matches[4][$key])))) {
                            $data['mfc'] = new ResultDataField('string', 'mfc', \trim(\html_entity_decode(\strip_tags($matches[4][$key]))), 'МФЦ', 'МФЦ');
                        }
                        $data['simplified'] = new ResultDataField('string', 'simplified', \count($matches) < 7 ? 'Да' : 'Нет', 'Упрощенная процедура банкротства', 'Упрощенная процедура банкротства');
                    }
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('logs/fedresurs/messages.csv', '"'.\trim(\html_entity_decode($matches[3][$key]))."\"\n", \FILE_APPEND);
                }
            }
            if (\preg_match_all('/<tr[^<]+<td[^>]*>([^<]+)<\\/td[^<]+<td[^>]*>([^<]+)<\\/td[^<]+<td[^<]+<img[^<]+<a href="([^"]+)"[^>]*>([^<]+)<\\/a[^<]+<\\/td[^<]+<\\/tr>/si', $content, $matches)) {
                foreach ($matches[1] as $key => $val) {
                    $title = \trim(\html_entity_decode($matches[4][$key]));
                    if (1 == $swapData['docpage'] && 0 == $key) {
                        $docdate = ['lastdocdate', 'Дата последнего документа'];
                        $doctitle = ['lastdoctitle', 'Наименование последнего документа'];
                        $docurl = ['lastdocurl', 'Последний документ'];
                        $data[$docdate[0]] = new ResultDataField('string', $docdate[0], \trim(\html_entity_decode($matches[1][$key])), $docdate[1], $docdate[1]);
                        $data[$doctitle[0]] = new ResultDataField('string', $doctitle[0], $title, $doctitle[1], $doctitle[1]);
                        $data[$docurl[0]] = new ResultDataField('url', $docurl[0], \trim(\html_entity_decode($matches[3][$key])), $docurl[1], $docurl[1]);
                    }
                    $docdate = false;
                    $doctitle = false;
                    $docurl = false;
                    //                    if (preg_match('/резолютив/si',$matches[2][$key])) {
                    //                    } else
                    if (\preg_match('/[Пп]ринят.*?признан.*?(банкрот|несостоят)/si', $title)) {
                        $docdate = ['petitiondate', 'Дата принятия заявления о признании банкротом'];
                        $docurl = ['petitionurl', 'Определение о принятии заявления'];
                    } elseif (\preg_match('/[Пп]ризна.*?банкрот/si', $title) && \preg_match('/[Рр]ешени/si', $matches[2][$key])) {
                        $docdate = ['decisiondate', 'Дата решения о признании банкротом'];
                        $docurl = ['decisionurl', 'Решение о признании банкротом'];
                    } elseif (\preg_match('/[Зз]аверш.*?(реализац|конкурс)/si', $title) || \preg_match('/[Уу]тверд.*?мировое/si', $title)) {
                        $docdate = ['completiondate', 'Дата завершения процедуры банкротства'];
                        $docurl = ['completionurl', 'Определение о завершении'];
                    }
                    if ($docdate) {
                        $data[$docdate[0]] = new ResultDataField('string', $docdate[0], \trim(\html_entity_decode($matches[1][$key])), $docdate[1], $docdate[1]);
                    }
                    if ($doctitle) {
                        $data[$doctitle[0]] = new ResultDataField('string', $doctitle[0], $title, $doctitle[1], $doctitle[1]);
                    }
                    if ($docurl) {
                        $data[$docurl[0]] = new ResultDataField('url', $docurl[0], \trim(\html_entity_decode($matches[3][$key])), $docurl[1], $docurl[1]);
                    }
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('logs/fedresurs/docs.csv', '"'.\trim(\html_entity_decode($matches[2][$key]))."\";\"{$title}\"\n", \FILE_APPEND);
                }
            }
            if (!$error && (isset($initData['inn']) && isset($data['inn']) && $data['inn']->getValue() == $initData['inn'] || (!isset($initData['last_name']) || \strtr(\mb_strtoupper($data['lastname']->getValue()), ['Ё' => 'Е']) == \strtr(\mb_strtoupper($initData['last_name']), ['Ё' => 'Е'])) && (!isset($initData['first_name']) || \strtr(\mb_strtoupper($data['firstname']->getValue()), ['Ё' => 'Е']) == \strtr(\mb_strtoupper($initData['first_name']), ['Ё' => 'Е'])) && (!isset($initData['patronymic']) || !isset($data['middlename']) || \strtr(\mb_strtoupper($data['middlename']->getValue()), ['Ё' => 'Е']) == \strtr(\mb_strtoupper($initData['patronymic']), ['Ё' => 'Е'])) && (!isset($initData['date']) || isset($data['birthdate']) && $data['birthdate']->getValue() == \date('d.m.Y', \strtotime($initData['date']))))) {
                if (isset($swapData['msgpages']) && ++$swapData['msgpage'] <= $swapData['msgpages']) {
                    $swapData['pagetype'] = 'Messages';
                } elseif (isset($swapData['docpages']) && ++$swapData['docpage'] <= $swapData['docpages']) {
                    $swapData['pagetype'] = 'KadDocuments';
                } else {
                    unset($swapData['pagetype']);
                    unset($swapData['msgpages']);
                    unset($swapData['docpages']);
                    $resultData->addResult($data);
                }
            }
            if (isset($swapData['pagetype'])) {
                --$swapData['iteration'];
                $swapData['data'] = $data;
                $rContext->setSwapData($swapData);
            } elseif (++$swapData['urlnum'] < \count($swapData['url'])) {
                --$swapData['iteration'];
                unset($swapData['data']);
                $swapData['result'] = $resultData;
                $rContext->setSwapData($swapData);
            } else {
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                $connection->executeStatement("UPDATE session SET statuscode='success',success=ifnull(success,0)+1 WHERE id=".$swapData['session']->id);
                //                if ($swapData['session']->proxyid)
                //                    $mysqli->query("UPDATE proxy SET success=success+1,successtime=now() WHERE id=".$swapData['session']->proxyid);
                return true;
            }
        } else {
            // (isset($initData['inn'])) {
            //            \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/fedresurs/bankrot_'.$checktype.'_'.time().'.html',curl_getinfo($rContext->getCurlHandler(),CURLINFO_HEADER_OUT)."\r\n".$content);
            if (\preg_match('/<table class="bank"[^<]+<tr/', $content)) {
                $resultData = new ResultDataList();
                //                $data['Result'] = new ResultDataField('string','Result', (strpos($table[1],'не найдено')?'ОТСУТСТВУЕТ':'ЧИСЛИТСЯ').' в реестре банкротов', 'Результат', 'Результат');
                //                $data['ResultCode'] = new ResultDataField('string','ResultCode', strpos($table[1],'не найдено')?'NOT_FOUND':'FOUND', 'Код результата', 'Код результата');
                $swapData['url'] = [];
                if (\preg_match_all('/<tr[^<]+<td[^>]*>([^<]+)<\\/td[^<]+<td[^<]+<a href="([^"]+)"[^>]+>([^<]+)<\\/a[^<]+<\\/td[^<]+<td[^>]*>([^<]+)<\\/td[^<]+<td[^>]*>([^<]+)<\\/td[^<]+<td[^>]*>([^<]+)<\\/td[^<]+<td[^>]*>([^<]+)<\\/td[^<]+<\\/tr>/', $content, $matches)) {
                    foreach ($matches[1] as $key => $val) {
                        $data = [];
                        $data['category'] = new ResultDataField('string', 'category', \trim(\html_entity_decode($matches[1][$key])), 'Категория', 'Категория');
                        $data['name'] = new ResultDataField('string', 'name', \trim(\html_entity_decode($matches[3][$key])), 'Должник', 'Должник');
                        $data['inn'] = new ResultDataField('string', 'inn', \trim(\html_entity_decode($matches[4][$key])), 'ИНН', 'ИНН');
                        $data['ogrn'] = new ResultDataField('string', 'ogrn', \trim(\html_entity_decode($matches[5][$key])), 'ОГРН', 'ОГРН');
                        $data['region'] = new ResultDataField('string', 'region', \trim(\html_entity_decode($matches[6][$key])), 'Регион', 'Регион');
                        $data['address'] = new ResultDataField('string', 'address', \trim(\html_entity_decode($matches[7][$key])), 'Адрес', 'Адрес');
                        $data['url'] = new ResultDataField('url', 'url', 'https://old.bankrot.fedresurs.ru'.\trim(\html_entity_decode($matches[2][$key])), 'URL', 'URL');
                        $resultData->addResult($data);
                    }
                }
                if (\preg_match_all('/<tr[^<]+<td[^>]*>([^<]+)<\\/td[^<]+<td[^<]+<a href="([^"]+)"[^>]+>([^<]+)<\\/a[^<]+<\\/td[^<]+<td[^>]*>([^<]+)<\\/td[^<]+<td[^>]*>([^<]+)<\\/td[^<]+<td[^>]*>([^<]+)<\\/td[^<]+<td[^>]*>([^<]+)<\\/td[^<]+<td[^>]*>([^<]+)<\\/td[^<]+<\\/tr>/', $content, $matches)) {
                    foreach ($matches[1] as $key => $val) {
                        $data = [];
                        $data['category'] = new ResultDataField('string', 'category', \trim(\html_entity_decode($matches[1][$key])), 'Категория', 'Категория');
                        $data['name'] = new ResultDataField('string', 'name', \trim(\html_entity_decode($matches[3][$key])), 'Должник', 'Должник');
                        $data['inn'] = new ResultDataField('string', 'inn', \trim(\html_entity_decode($matches[4][$key])), 'ИНН', 'ИНН');
                        $data['ogrn'] = new ResultDataField('string', 'ogrn', \trim(\html_entity_decode($matches[5][$key])), 'ОГРН', 'ОГРН');
                        $data['snils'] = new ResultDataField('string', 'snils', \trim(\html_entity_decode($matches[6][$key])), 'СНИЛС', 'СНИЛС');
                        $data['region'] = new ResultDataField('string', 'region', \trim(\html_entity_decode($matches[7][$key])), 'Регион', 'Регион');
                        $data['address'] = new ResultDataField('string', 'address', \trim(\html_entity_decode($matches[8][$key])), 'Адрес', 'Адрес');
                        $url = 'https://old.bankrot.fedresurs.ru'.\trim(\html_entity_decode($matches[2][$key]));
                        $swapData['url'][] = $url;
                        $data['url'] = new ResultDataField('url', 'url', $url, 'URL', 'URL');
                        //                        $resultData->addResult($data);
                    }
                }
                if (\count($swapData['url'])) {
                    $swapData['result'] = $resultData;
                    $swapData['urlnum'] = 0;
                    $rContext->setSwapData($swapData);
                } else {
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    $connection->executeStatement("UPDATE session SET statuscode='success',success=ifnull(success,0)+1 WHERE id=".$swapData['session']->id);
                    //                    if ($swapData['session']->proxyid)
                    //                        $mysqli->query("UPDATE proxy SET success=success+1,successtime=now() WHERE id=".$swapData['session']->proxyid);
                }

                return true;
            } else {
                //                $content1251 = iconv('windows-1251','utf-8',$content);
                //                if (strpos($content1251,'временно') || strpos($content1251,'регламентные') || strpos($content,'<title>502')) {
                if (\strpos($content, 'временно') || \strpos($content, 'регламентные') || \strpos($content, '<title>502')) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');

                    return true;
                } elseif (\strpos($content, '<title>403')) {
                    $connection->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 1 hour),sessionstatusid=6,statuscode='forbidden' WHERE id=".$swapData['session']->id);
                    unset($swapData['session']);
                } elseif (\strpos($content, '<title>429')) {
                    $connection->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 15 minute),sessionstatusid=6,statuscode='exhausted' WHERE id=".$swapData['session']->id);
                    unset($swapData['session']);
                } elseif (\strpos($content, '<title>Ошибка выполнения') || \strpos($content, 'непредвиденная ошибка')) {
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/fedresurs/bankrot_err_'.\time().'.html', $content);
                    if ($swapData['iteration'] >= 5) {
                        $error = 'Внутренняя ошибка источника';
                    }
                    $connection->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 5 minute),sessionstatusid=6,statuscode='internal' WHERE id=".$swapData['session']->id);
                    unset($swapData['session']);
                } elseif ($content && $swapData['iteration'] >= 3) {
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/fedresurs/bankrot_err_'.\time().'.html', $content);
                    $error = 'Некорректный ответ сервиса';
                }
            }
            /*
                    } else {
                        $resultData = new ResultDataList();

                        $last_name = isset($initData['last_name'])?$initData['last_name']:'';
                        $first_name = isset($initData['first_name'])?$initData['first_name']:'';
                        $middle_name = isset($initData['patronymic'])?$initData['patronymic']:'';
                        $birth_date = isset($initData['date'])?date('d.m.Y',strtotime($initData['date'])):'';

                        $result = $mysqli->query("SELECT * FROM fedresurs.fedresurswhole WHERE ".(isset($initData['inn'])?"inn='".$initData['inn']."'".
                            ($last_name?" OR ":""):"").($last_name?"(lastname='$last_name' AND firstname='$first_name' AND middlename='$middle_name'".
                            ($birth_date?" and (birthdate='(Н/Д)' or birthdate='".$birth_date."')":"").")":""));
                        if ($result) {
                            while($row = $result->fetch_assoc()){
                                $data=array();
                                foreach($row as $key => $val){
                                    if ($val && $val!='(Н/Д)' && isset($this->titles[$key][0]))
                                        $data[$key] = new ResultDataField('string',$key,$val,$this->titles[$key][0],$this->titles[$key][1]);
                                }
                                $data['url'] = new ResultDataField('url','url','https://old.bankrot.fedresurs.ru/PrivatePersonCard.aspx?ID='.$row['code'],'URL','URL');
                                $resultData->addResult($data);
                            }
                            $result->close();
                        }

                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
                        return true;
            */
        }
        $rContext->setSwapData($swapData);
        $rContext->setSleep(1);
        if ($swapData['iteration'] > 30) {
            $rContext->setFinished();
            $rContext->setError('' == $error ? 'Превышено количество попыток получения ответа' : $error);

            return false;
        }

        return true;
    }
}

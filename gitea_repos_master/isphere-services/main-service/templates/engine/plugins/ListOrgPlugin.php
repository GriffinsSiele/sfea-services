<?php

class ListOrgPlugin implements PluginInterface
{
    private $names = ['телефон' => ['phone', 'Телефон', 'Телефон'], 'инн/кпп' => ['inn', 'ИНН', 'ИНН'], 'кпп' => ['kpp', 'КПП', 'КПП'], 'руководитель' => ['head', 'Руководитель', 'Руководитель'], 'юр.адрес' => ['address', 'Адрес', 'Адрес']];

    public function getName()
    {
        return 'egrul';
    }

    public function getTitle()
    {
        return 'Поиск в ЕГРЮЛ/ЕГРИП';
    }

    public function getSessionData(array $params)
    {
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $reqId = $params['_reqId'];
        $sessionData = null;
        $mysqli->executeStatement('UPDATE session s SET request_id='.$reqId.' WHERE request_id IS NULL AND sessionstatusid=2 AND sourceid=23 ORDER BY lasttime limit 1');
        $result = $mysqli->executeQuery("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM session s WHERE sourceid=23 AND request_id=".$reqId.' ORDER BY lasttime limit 1');
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
                $sessionData->proxy = $row['proxy'];
                $sessionData->proxy_auth = \strlen($row['proxy_auth']) > 1 ? $row['proxy_auth'] : false;
                $mysqli->executeStatement('UPDATE session SET lasttime=now(),used=ifnull(used,0)+1,request_id=NULL WHERE id='.$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        if (!isset($initData['phone']) && !isset($initData['inn']) && !isset($initData['ogrn'])) {
            $rContext->setFinished();
            $rContext->setError('Указаны не все обязательные параметры (телефон или ИНН или ОГРН)');

            return false;
        }
        if (isset($initData['phone'])) {
            //            if (strlen($initData['phone'])==10)
            //                $initData['phone']='7'.$initData['phone'];
            //            if ((strlen($initData['phone'])==11) && (substr($initData['phone'],0,1)=='8'))
            //                $initData['phone']='7'.substr($initData['phone'],1);
            if ('7' != \substr($initData['phone'], 0, 1)) {
                $rContext->setFinished();
                //                $rContext->setError('Поиск производится только по российским телефонам');
                return false;
            }
        }
        // //////////////////////////////////////////////////////////////////////////////////////////////////
        !isset($swapData['iteration']) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
        $swapData['session'] = $this->getSessionData($params);
        if (!$swapData['session']) {
            if ($swapData['iteration'] >= 10) {
                $rContext->setFinished();
                $rContext->setError('Сервис временно недоступен');
            } else {
                $rContext->setSwapData($swapData);
                $rContext->setSleep(1);
            }

            return false;
        }
        $rContext->setSwapData($swapData);
        // //////////////////////////////////////////////////////////////////////////////////////////////////
        $ch = $rContext->getCurlHandler();
        if (!isset($swapData['url'])) {
            $params = [];
            if (isset($initData['phone'])) {
                $params['val'] = \substr($initData['phone'], 1);
                $params['type'] = 'phone';
            } elseif (isset($initData['inn'])) {
                $params['val'] = $initData['inn'];
                $params['type'] = 'inn';
            } elseif (isset($initData['ogrn'])) {
                $params['val'] = $initData['ogrn'];
                $params['type'] = 'ogrn';
            }
            $url = 'https://www.list-org.com/search.php?'.\http_build_query($params);
        } else {
            $url = $swapData['url'];
        }
        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_REFERER, 'https://www.list-org.com/');
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
        /** @var \Doctrine\DBAL\Connection $mysqli */
        $mysqli = $params['_connection'];
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        $swapData['iteration'] = !isset($swapData['iteration']) ? 1 : $swapData['iteration'] + 1;
        $error = $swapData['iteration'] > 5 && \curl_error($rContext->getCurlHandler());
        if (!$error) {
            $content = \curl_multi_getcontent($rContext->getCurlHandler());
            if (\preg_match('/вы не робот/', $content, $matches)) {
                if (isset($swapData['session'])) {
                    $mysqli->executeStatement("UPDATE session SET endtime=now(),sessionstatusid=5,statuscode='invalidcaptcha' WHERE id=".$swapData['session']->id);
                }
                unset($swapData['session']);
                $rContext->setSleep(1);
            } elseif (!isset($swapData['url'])) {
                //                \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/listorg/search_'.time().'.html',$content);
                $resultData = new ResultDataList();
                if (\preg_match("/<div class=\\'org_list\\'>(.*?)<\\/div>/sim", $content, $matches)) {
                    $parts = \preg_split('/<\\/p>/', \strtr($matches[1], ['&nbsp;' => ' ']));
                    foreach ($parts as $i => $dataPart) {
                        $data = [];
                        if (\preg_match("/<a href=\\'([^\\']+)\\'>/", $dataPart, $matches)) {
                            //                            $swapData['url'] = 'https://list-org.com'.$matches[1];
                        }
                        if (\preg_match("/<a href=\\'[^\\']+\\'>([^<]+)<\\/a>/", $dataPart, $matches)) {
                            $data['orgname'] = new ResultDataField('string', 'OrgName', \trim($matches[1]), 'Организация', 'Организация');
                        }
                        if (\preg_match('/<span>([^<]+)<br>/', $dataPart, $matches)) {
                            $data['orgfullname'] = new ResultDataField('string', 'OrgFullName', \trim($matches[1]), 'Полное наименование', 'Полное наименование');
                        }
                        if (\preg_match("/<span class=\\'status[^>]+>([^<]+)<\\/span>/", $dataPart, $matches)) {
                            $data['orgstatus'] = new ResultDataField('string', 'OrgStatus', \trim($matches[1]), 'Статус', 'Статус');
                        }
                        $counter = 0;
                        if (\preg_match_all('/<i>([^<]+)<\\/i>: (.*?)<br>/sim', $dataPart.'<br>', $matches)) {
                            foreach ($matches[1] as $key => $val) {
                                $title = $val;
                                $text = \str_replace('&#039;', "'", \html_entity_decode(\strip_tags($matches[2][$key])));
                                if (isset($this->names[$title])) {
                                    $field = $this->names[$title];
                                    //                                    if ($field[0]=='phone') $text = normal_phone($text);
                                    if (\strpos($title, '/')) {
                                        $titles = \preg_split('/\\//', $title);
                                        $texts = \preg_split('/\\//', $text);
                                        $text = $texts[0];
                                        if ($text) {
                                            $data[$field[0]] = new ResultDataField(isset($field[3]) ? $field[3] : 'string', $field[0], $text, $field[1], $field[2]);
                                        }
                                        $title = $titles[1];
                                        if (isset($this->names[$title])) {
                                            $field = $this->names[$title];
                                            $text = $texts[1];
                                        }
                                    }
                                    if ($text) {
                                        $data[$field[0]] = new ResultDataField(isset($field[3]) ? $field[3] : 'string', $field[0], $text, $field[1], $field[2]);
                                    }
                                } else {
                                    ++$counter;
                                    if ($text) {
                                        $data['other'.$counter] = new ResultDataField('string', 'other'.$counter, $text, $title, $title);
                                    }
                                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/fields/listorg_'.\time().'_'.\strtr($title, ['/' => '_']), $title."\n".$text);
                                }
                            }
                        }
                        if (\count($data) && isset($data['phone'])) {
                            $resultData->addResult($data);
                        }
                    }
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                } elseif (\preg_match('/400 Bad Request/', $content)) {
                    if (isset($swapData['session'])) {
                        $mysqli->executeStatement("UPDATE session SET endtime=now(),sessionstatusid=5,statuscode='badanswer' WHERE id=".$swapData['session']->id);
                    }
                    unset($swapData['session']);
                } elseif ($content) {
                    \App\Utils\Legacy\LoggerUtilStatic::file_put_contents('./logs/listorg/search_err_'.\time().'.html', $content);
                    $error = 'Некорректный ответ сервиса';
                } else {
                    if (isset($swapData['session'])) {
                        $mysqli->executeStatement("UPDATE session SET unlocktime=date_add(now(),interval 10 minute),sessionstatusid=6,statuscode='empty' WHERE id=".$swapData['session']->id);
                    }
                    unset($swapData['session']);
                }
                //            }elseif(isset($swapData['url'])){
            }
            $rContext->setSwapData($swapData);
        }
        if (!$error && isset($swapData['iteration']) && $swapData['iteration'] > 10) {
            $error = 'Превышено количество попыток получения ответа';
        }
        if ($error) {
            $rContext->setError($error);
            $rContext->setFinished();

            return false;
        }
        $rContext->setSleep(1);

        return true;
    }
}

<?php

class GISGMPPlugin_new implements PluginInterface
{
    public function getName()
    {
        return 'gisgmp';
    }

    public function getTitle($checktype = '')
    {
        $title = [
            '' => 'Поиск задолженностей в ГИС ГМП',
            'gisgmp_taxes' => 'ГИС ГМП - задолженности по налогам',
            'gisgmp_fssp' => 'ГИС ГМП - задолженности по исполнительным производствам',
            'gisgmp_fines' => 'ГИС ГМП - неоплаченные штрафы',
            'gisgmp_ip' => 'ГИС ГМП - задолженность по исполнительному производству',
        ];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск задолженностей в ГИС ГМП';
    }

    public function getSessionData()
    {
        global $mysqli;
        $sessionData = null;

        $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sessionstatusid=2 AND (SELECT status FROM proxy WHERE id=s.proxyid)=1 AND sourceid=34 AND unix_timestamp(now())-unix_timestamp(lasttime)>1 ORDER BY lasttime limit 1");

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

                $mysqli->query('UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1 WHERE id='.$sessionData->id);
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        global $http_connecttimeout, $http_timeout;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        $checktype = \substr($initData['checktype'], 7);

        if (('taxes' == $checktype) && !isset($initData['inn'])) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (ИНН)');
            return false;
        }

        if (('fines' == $checktype) && !isset($initData['driver_number']) && !isset($initData['ctc'])) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (водительское удостоверение или св-во о регистрации ТС)');
            return false;
        }

        if (('fssp' == $checktype) && (!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date']) || !isset($initData['region_id']))) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (ФИО, дата рождения и регион)');
            return false;
        }

        if (('ip' == $checktype) && !isset($initData['fssp_ip'])) {
            $rContext->setFinished();
            //            $rContext->setError('Указаны не все обязательные параметры (паспорт или ИНН)');
            return false;
        }

        if (('ip' == $checktype) && !\preg_match("/([0-9]+[\-\/][0-9]+[\-\/][0-9]+)-ИП/", $initData['fssp_ip'], $matches)) {
            $rContext->setFinished();
            $rContext->setError('Некорректный номер исполнительного производства');

            return false;
        }

        if (isset($initData['last_name']) && isset($initData['first_name']) && \preg_match("/[^А-Яа-яЁё\s\-\.]/ui", $initData['last_name'].' '.$initData['first_name'].(isset($initData['patronymic']) ? ' '.$initData['patronymic'] : ''))) {
            $rContext->setFinished();
            $rContext->setError('Имя может содержать только русские буквы');

            return false;
        }

        // //////////////////////////////////////////////////////////////////////////////////////////////////

        if (!isset($swapData['session'])) {
            $swapData['session'] = $this->getSessionData();

            if (!$swapData['session']) {
                if (isset($swapData['iteration']) && ($swapData['iteration'] >= 10)) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');

                    return false;
                } else {
                    (!isset($swapData['iteration'])) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);

                    return false;
                }
            }
            $rContext->setSwapData($swapData);
        }

        // //////////////////////////////////////////////////////////////////////////////////////////////////

        $ch = $rContext->getCurlHandler();

        $articles = [
            'taxes' => 'ufns',
            'fines' => 'gibddfine',
            'fssp' => 'ufssp',
            'ip' => 'ufssp',
        ];
        $host = 'https://oplatagosuslug.ru';
        if (!isset($swapData['uid'])) {
            $url = $host.'/charges/find/';
            if ('fssp' == $checktype && isset($initData['last_name']) && isset($initData['first_name']) && isset($initData['date'])) {
                $data = 'FIO='.\urlencode($initData['last_name'].' '.$initData['first_name'].(isset($initData['patronymic']) ? ' '.$initData['patronymic'] : '')).'&birthday='.\date('d.m.Y', \strtotime($initData['date'])).'&region='.(isset($initData['region_id']) ? $initData['region_id'] : '0');
            } elseif ('fssp' == $checktype && isset($initData['passport_number'])) {
                $data = 'passport='.$initData['passport_series'].$initData['passport_number'];
            } elseif (('fssp' == $checktype || 'taxes' == $checktype) && isset($initData['inn'])) {
                $data = 'inn='.$initData['inn'];
            } elseif ('fines' == $checktype && isset($initData['driver_number'])) {
                $data = 'regnum=&license='.\urlencode($initData['driver_number']);
            } elseif ('fines' == $checktype && isset($initData['ctc'])) {
                $data = 'regnum='.\urlencode($initData['ctc']).'&license=';
            } elseif ('ip' == $checktype && isset($initData['fssp_ip'])) {
                $ippos = \strpos($initData['fssp_ip'], '-ИП');
                if ($ippos) {
                    $data = 'number_alt='.\urlencode(\substr($initData['fssp_ip'], 0, $ippos)).'&IPsuffix=ip';
                }
            } else {
                $rContext->setFinished();

                return false;
            }
            $params = [
                'data' => $data,
                'article' => $articles[$checktype],
                '_stoken' => $swapData['session']->token,
            ];
            $header[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
            $header[] = 'X-Requested-With: XMLHttpRequest';
            $header[] = 'Referer: '.$host.'/';
            \curl_setopt($ch, \CURLOPT_URL, $url);
            \curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);
            \curl_setopt($ch, \CURLOPT_POST, true);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, \http_build_query($params));
        } elseif (!isset($swapData['url'])) {
            $url = $host.'/charges/get/';
            $params = [
                'uid' => $swapData['uid'],
            ];
            $header[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
            $header[] = 'X-Requested-With: XMLHttpRequest';
            $header[] = 'Referer: '.$host.'/';
            \curl_setopt($ch, \CURLOPT_URL, $url);
            \curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);
            \curl_setopt($ch, \CURLOPT_POST, true);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, \http_build_query($params));
        } else {
            $url = $swapData['url'];
            \curl_setopt($ch, \CURLOPT_URL, $url);
        }
        //        print "URL: ".$url."\n";
        \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
        //        print "Cookie: ".$swapData['session']->cookies."\n";
        if ($swapData['session']->proxy) {
            \curl_setopt($ch, \CURLOPT_PROXY, $swapData['session']->proxy);
            //            print "Proxy: ".$swapData['session']->proxy."\n";
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
        $error = false;

        global $mysqli;
        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();
        (!isset($swapData['iteration'])) ? $swapData['iteration'] = 1 : $swapData['iteration']++;

        $rContext->setSwapData($swapData);
        $content = \curl_multi_getcontent($rContext->getCurlHandler());

        $res = false;

        if (!isset($swapData['uid'])) {
            if ($content) {
                \file_put_contents('./logs/gisgmp/oplatagosuslug_start_'.\time().'.html', $content);
            }
            $res = \json_decode($content, true);
            if (isset($res['uid'])) {
                $swapData['uid'] = $res['uid'];
                $rContext->setSleep(5);
            } elseif (isset($res['status']) && (403 == $res['status'] || !$res['status'])) {
                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,code='expired' WHERE id=".$swapData['session']->id);
                unset($swapData['session']);
            } elseif ($content) {
                \file_put_contents('./logs/gisgmp/oplatagosuslug_start_err_'.\time().'.html', $content);
                $error = 'Ошибка при отправке запроса';
            } else {
                $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,code='empty' WHERE id=".$swapData['session']->id);
                unset($swapData['session']);
            }
        } elseif (!isset($swapData['url'])) {
            if ($content) {
                \file_put_contents('./logs/gisgmp/oplatagosuslug_get_'.\time().'.html', $content);
            }
            $res = \json_decode($content, true);
            if (isset($res['status']) && $res['status']) {
                if ('success' == $res['status']) {
                    if (isset($res['url'])) {
                        $swapData['url'] = $res['url'];
                    } else {
                        $resultData = new ResultDataList();
                        $rContext->setResultData($resultData);
                        $rContext->setFinished();
                    }
                } elseif ('fail' == $res['status']) {
                    if ($swapData['iteration'] > 3) {
                        $swapData['iteration'] = 0;
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),sessionstatusid=3,code='fail' WHERE id=".$swapData['session']->id);
                        unset($swapData['session']);
                        unset($swapData['uid']);
                    } else {
                        $rContext->setSleep(1);
                    }
                } else {
                    \file_put_contents('./logs/gisgmp/oplatagosuslug_get_err_'.\time().'.html', $content);
                    $error = 'Ошибка при выполнении запроса';
                }
            }
        } else {
            if (!empty(\trim($content))) {
                \file_put_contents('./logs/gisgmp/oplatagosuslug_'.\time().'.html', $content);
            }

            if (\preg_match_all("/<div class=\"panel-heading\"[^>]+>.*?<\/article>/sim", $content, $matches)) {
                foreach ($matches[0] as $key => $part) {
                    if (\preg_match("/<pre>Индекс ([0-9]+),([^<]+)<\/pre>/sim", $part, $mmmatches)) {
                        $res[$key]['index'] = \trim($mmmatches[1]);
                        $res[$key]['debttype'] = \trim($mmmatches[2]);
                    }
                    if (\preg_match_all('/<input type="hidden" name="([^"]+)" value="([^"]+)"/sim', $part, $mmmatches) || \preg_match_all('/<input type="hidden" name="([^"]+)" class="[^"]+" value="([^"]+)"/sim', $part, $mmmatches)) {
                        foreach ($mmmatches[1] as $k => $val) {
                            $res[$key][$val] = $mmmatches[2][$k];
                        }
                    }
                    if (\preg_match_all("/<span class=\"dt\">(.*?)<\/span><span class=\"dd\"><pre>([^<]+)/sim", $part, $mmmatches)) {
                        foreach ($mmmatches[1] as $k => $val) {
                            $res[$key][\trim(\strip_tags($val))] = \trim(\strip_tags($mmmatches[2][$k]));
                        }
                    }
                    if (\preg_match_all("/<dt>(.*?)<\/dt><dd><pre>([^<>]+)/sim", $content, $mmmatches)) {
                        foreach ($mmmatches[1] as $k => $val) {
                            $res[$key][\trim(\strip_tags($val))] = \trim(\strip_tags($mmmatches[2][$k]));
                        }
                    }
                }
            } else {
                \file_put_contents('./logs/gisgmp/oplatagosuslug_err_'.\time().'.html', $content);
                $error = 'Ошибка при обработке ответа';
            }

            $resultData = new ResultDataList();

            if ($res && \is_array($res)) {
                if (!isset($res['error']) || !$res['error']) {
                    foreach ($res as $debt) {
                        $data = [];
                        //                        if (isset($debt['index']))
                        //                            $data['Index'] = new ResultDataField('string','Index', $debt['index'], 'Индекс начисления', 'Индекс начисления');
                        if (isset($debt['debttype'])) {
                            $data['DebtType'] = new ResultDataField('string', 'DebtType', $debt['debttype'], 'Тип задолженности', 'Тип задолженности');
                        }
                        if (isset($debt['purpose']) && $debt['purpose']) {
                            $data['Subject'] = new ResultDataField('string', 'Subject', $debt['purpose'], 'Предмет задолженности', 'Предмет задолженности');
                        }
                        if (isset($debt['amount'])) {
                            $data['Total'] = new ResultDataField('float', 'Total', $debt['amount'], 'Сумма задолженности', 'Сумма задолженности');
                        } elseif (isset($debt['origin_charge_amount'])) {
                            $data['Total'] = new ResultDataField('float', 'Total', $debt['origin_charge_amount'], 'Сумма задолженности', 'Сумма задолженности');
                        }
                        if (isset($debt['docno'])) {
                            $data['DocNumber'] = new ResultDataField('string', 'DocNumber', $debt['docno'], 'Номер документа', 'Номер документа');
                        }
                        if (isset($debt['docdate'])) {
                            $data['DocDate'] = new ResultDataField('string', 'DocDate', $debt['docdate'], 'Дата документа', 'Дата документа');
                        }
                        if (isset($debt['primalDocument']) && $debt['primalDocument']) {
                            //                            if (preg_match("/№ (.*?) от (.*?)/",$debt['primalDocument'],$matches)) {
                            //                                $data['PrimalDocNumber'] = new ResultDataField('string','PrimalDocNumber', trim($matches[1]), 'Номер первичного документа', 'Номер первичного документа');
                            //                                $data['PrimalDocDate'] = new ResultDataField('string','PrimalDocDate', date('d.m.Y',strtotime(trim($matches[2]))), 'Дата первичного документа', 'Дата первичного документа');
                            //                            } else {
                            $data['PrimalDoc'] = new ResultDataField('string', 'PrimalDoc', $debt['primalDocument'], 'Первичный документ', 'Первичный документ');
                            //                            }
                        }
                        if (isset($debt['payerName']) && $debt['payerName']) {
                            $data['Name'] = new ResultDataField('string', 'Name', $debt['payerName'], 'ФИО', 'ФИО');
                        } elseif (isset($debt['ФИО плательщика']) && $debt['ФИО плательщика']) {
                            $data['Name'] = new ResultDataField('string', 'Name', $debt['ФИО плательщика'], 'ФИО', 'ФИО');
                        } elseif (isset($debt['purpose']) && \preg_match("/в отношении ([А-Яа-яЁё\s\.\-\*]+)$/", $debt['purpose'], $matches) && $matches[1]) {
                            $data['Name'] = new ResultDataField('string', 'Name', $matches[1], 'ФИО', 'ФИО');
                        }
                        if (isset($debt['Документ'])) {
                            $debt['Документ'] = \strtr(\html_entity_decode($debt['Документ']), ["\u{a0}" => ' ']);
                            if (\preg_match("/ИНН ([\d\*]+)/", $debt['Документ'], $matches)) {
                                $data['INN'] = new ResultDataField('string', 'INN', $matches[1], 'ИНН', 'ИНН');
                            } elseif (\preg_match("/СНИЛС ([\d\*\-]+)/", $debt['Документ'], $matches)) {
                                $data['SNILS'] = new ResultDataField('string', 'SNILS', $matches[1], 'СНИЛС', 'СНИЛС');
                            } elseif (\preg_match("/паспорт РФ\, серия ([\d\*]+) номер ([\d\*]+)/", $debt['Документ'], $matches)) {
                                $data['Passport'] = new ResultDataField('string', 'Passport', $matches[1].$matches[2], 'Паспорт', 'Паспорт');
                            } elseif (\preg_match("/паспорт РФ\, серия ([\d\*А-Я]+) номер ([\d\*А-Я]+)/", $debt['Документ'], $matches) && (false !== \strpos(\mb_strtoupper($debt['purpose']), 'ГИБДД'))) {
                                $data['DriverLicense'] = new ResultDataField('string', 'DriverLicense', $matches[1].$matches[2], 'Водительское удостоверение', 'Водительское удостоверение');
                            } elseif (\preg_match("/Водительское удостоверение ([\s\d\*А-Я]+)/", $debt['Документ'], $matches)) {
                                $data['DriverLicense'] = new ResultDataField('string', 'DriverLicense', $matches[1], 'Водительское удостоверение', 'Водительское удостоверение');
                            } elseif (\preg_match("/Св-во о рег. ТС ([\s\d\*А-Я]+)/", $debt['Документ'], $matches)) {
                                $data['CTC'] = new ResultDataField('string', 'CTC', $matches[1], 'Свидетельство о регистрации ТС', 'Свидетельство о регистрации ТС');
                            } elseif (\preg_match("/ФИО: ([А-Я\-\*]+), Дата рождения: ([\d\.]+)/", $debt['Документ'], $matches)) {
                                if (!isset($data['Name'])) {
                                    $data['Name'] = new ResultDataField('string', 'Name', $matches[1], 'ФИО', 'ФИО');
                                }
                                $data['BirthDate'] = new ResultDataField('string', 'BirthDate', $matches[2], 'Дата рождения', 'Дата рождения');
                            } else {
                                $data['Document'] = new ResultDataField('string', 'Document', $debt['Документ'], 'Документ', 'Документ');
                            }
                        }
                        if (isset($debt['supplierbillid']) && $debt['supplierbillid']) {
                            $data['BillId'] = new ResultDataField('string', 'BillId', $debt['supplierbillid'], 'Идентификатор начисления', 'Идентификатор начисления');
                        }
                        if (isset($debt['payeeName']) && $debt['payeeName']) {
                            $data['PayeeName'] = new ResultDataField('string', 'PayeeName', $debt['payeeName'], 'Получатель', 'Получатель');
                        }
                        if (isset($debt['payeeInn']) && $debt['payeeInn']) {
                            $data['PayeeINN'] = new ResultDataField('string', 'PayeeINN', $debt['payeeInn'], 'ИНН получателя', 'ИНН получателя');
                        }
                        if (isset($debt['kbk']) && $debt['kbk']) {
                            $data['KBK'] = new ResultDataField('string', 'KBK', $debt['kbk'], 'КБК', 'Код бюджетной классификации');
                        }
                        if (isset($debt['okato']) && $debt['okato']) {
                            $data['OKATO'] = new ResultDataField('string', 'OKATO', $debt['okato'], 'ОКАТО', 'ОКАТО');
                        }
                        if (isset($debt['Основание платежа']) && $debt['Основание платежа']) {
                            $data['PaymentBasis'] = new ResultDataField('string', 'PaymentBasis', $debt['Основание платежа'], 'Основание платежа', 'Основание платежа');
                        }
                        //                        if (isset($debt['region']))
                        //                            $data['Region'] = new ResultDataField('string','Region', $debt['region'], 'Код региона', 'Код региона');
                        //                        if (isset($debt['bank_name']))
                        //                            $data['Bank'] = new ResultDataField('string','Bank', $debt['bank_name'], 'Банк', 'Банк');
                        $resultData->addResult($data);
                    }
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                } else {
                    $error = 'Ответ не получен';
                }
            }
        }
        $rContext->setSwapData($swapData);

        if ($swapData['iteration'] > 10) {
            $rContext->setFinished();
            $rContext->setError('' == $error ? 'Превышено количество попыток получения ответа' : $error);

            return false;
        }

        return true;
    }
}

<?php

class FNSPlugin_new implements PluginInterface
{
    private $captcha_service = [
//        array('host' => 'api.capmonster.cloud', 'key' => 'afb26cbb248d650ea8b8d88822984242'),
        ['host' => 'rucaptcha.com', 'key' => 'd167c71a9278312f184f17caa4e71050'],
        ['host' => 'api.anti-captcha.com', 'key' => '63def0a149a147e0d13e409cc8318fc3'],
    ];
    private $captcha_threads = 1;
    private $captcha_lifetime = 14;

    private $titles = [
       'inn' => ['ИНН', 'ИНН'],
       'orgInn' => ['ИНН организации', 'ИНН организации'],
       'ogrn' => ['ОГРН', 'ОГРН'],
       'title' => ['Название', 'Название'],
       'orgName' => ['Название', 'Название'],
       'kpp' => ['КПП', 'КПП'],
       'address' => ['Адрес', 'Адрес'],
       'fio' => ['ФИО', 'ФИО'],
       'birthDate' => ['Дата рождения', 'Дата рождения'],
       'birthPlace' => ['Место рождения', 'Место рождения'],
       'post' => ['Должность', 'Должность'],
       'term' => ['Срок дисквалификации', 'Срок дисквалификации'],
       'startDate' => ['Дата начала', 'Дата начала'],
       'endDate' => ['Дата окончания', 'Дата окончания'],
       'article' => ['Основание', 'Основание'],
       'authName' => ['Подразделение ФНС', 'Подразделение ФНС'],
       'judgePost' => ['Должность судьи', 'Должность судьи'],
       'judgeName' => ['ФИО судьи', 'ФИО судьи'],
       'lastname' => ['Фамилия', 'Фамилия'],
       'firstname' => ['Имя', 'Имя'],
       'patronymic' => ['Отчество', 'Отчество'],
       'quantity' => ['Количество организаций', 'Количество организаций'],
       'date' => ['Дата', 'Дата'],
       'usn' => ['УСН', 'УСН'],
       'envd' => ['ЕНВД', 'ЕНВД'],
       'eshn' => ['ЕСХН', 'ЕСХН'],
       'srp' => ['СРП', 'СРП'],
       'quant' => ['Среднесписочная численность сотрудников', 'Среднесписочная численность сотрудников'],
       'sumIncome' => ['Сумма доходов', 'Сумма доходов'],
       'sumExpense' => ['Сумма расходов', 'Сумма расходов'],
       'Penalty' => ['Сумма штрафа', 'Сумма штрафа'],
    ];

    private $nalog_fields = [
         'Налог, взимаемый в связи с  применением упрощенной  системы налогообложения' => ['usn', 'Налог УСН', 'Налог УСН'],
         'Транспортный налог' => ['transport_tax'],
         'Страховые взносы на обязательное медицинское страхование работающего населения, зачисляемые в бюджет Федерального фонда обязательного медицинского страхования' => ['oms', 'Страховые взносы на ОМС', 'Страховые взносы на ОМС'],
         'Страховые взносы на обязательное социальное страхование на случай временной нетрудоспособности и в связи с материнством' => ['fss', 'Страховые взносы ФСС', 'Страховые взносы ФСС'],
         'Земельный налог' => ['land_tax'],
         'Единый налог на вмененный доход для отдельных видов  деятельности' => ['envd', 'Единый налог на вмененный доход', 'Единый налог на вмененный доход'],
         'Страховые и другие взносы на обязательное пенсионное страхование, зачисляемые в Пенсионный фонд Российской Федерации' => ['pfr', 'Страховые взносы ПРФ', 'Страховые взносы ПРФ'],
         'Налог на прибыль' => ['profit_tax'],
         'Налог на добавленную стоимость' => ['nds'],
         'Налог на имущество организаций' => ['wealth_tax'],
         'Торговый сбор' => ['retail_fee'],
         'Налог на добычу полезных ископаемых' => ['ndpi'],
         'Сборы за пользование объектами животного мира  и за пользование объектами ВБР' => ['bio_tax', 'Сборы за пользование биоресурсами', 'Сборы за пользование биоресурсами'],
         'Единый сельскохозяйственный налог' => ['eshn'],
         'Водный налог' => ['water_tax'],
         'Акцизы, всего' => ['excises'],
         'НЕНАЛОГОВЫЕ ДОХОДЫ, администрируемые налоговыми органами' => ['nontax'],
         'Налог на доходы физических лиц' => ['ndfl'],
         'Задолженность и перерасчеты по ОТМЕНЕННЫМ НАЛОГАМ  и сборам и иным обязательным платежам  (кроме ЕСН, страх. Взносов)' => ['cancelled_taxes'],
         'Утилизационный сбор' => ['recycling_fee'],
         'Налог на игорный' => ['gambling_tax', 'Налог на игорный бизнес', 'Налог на игорный бизнес'],
         'Налог, взимаемый в связи с  применением патентной системы  налогообложения' => ['psn', 'Налог ПСН', 'Налог ПСН'],
         'Государственная пошлина' => ['state_duty'],
         'Регулярные платежи за добычу полезных ископаемых (роялти) при выполнении соглашений о разделе продукции' => ['srp', 'Платежи за пользование недрами по СРП', 'Платежи за пользование недрами по СРП'],
    ];

    public function str_uprus($text)
    {
        $up = [
                'а' => 'А',
                'б' => 'Б',
                'в' => 'В',
                'г' => 'Г',
                'д' => 'Д',
                'е' => 'Е',
                'ё' => 'Ё',
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
        ];
        if (\preg_match('/[а-я]/', $text)) {
            $text = \strtr($text, $up);
        }

        return $text;
    }

    public function getName()
    {
        return 'FNS';
    }

    public function getTitle($checktype = '')
    {
        $title = [
            '' => 'Поиск сведений в ФНС РФ',
            'fns_inn' => 'ФНС - определение ИНН',
            'fns_bi' => 'ФНС - решения о приостановлении операций по р/с',
            'fns_mru' => 'ФНС - массовые руководители и учредители',
            'fns_zd' => 'ФНС - задолженность по налогам и отчетности',
            'fns_disqualified' => 'ФНС - дисквалифицированные лица',
            'fns_disfind' => 'ФНС - организации, управляемые дисквалифицированными лицами',
            'fns_svl' => 'ФНС - невозможность участия или руководства организацией',
            'fns_sshr' => 'ФНС - среднесписочная численность сотрудников',
            'fns_snr' => 'ФНС - специальные налоговые режимы',
            'fns_revexp' => 'ФНС - доходы и расходы',
            'fns_paytax' => 'ФНС - уплаченные налоги и взносы',
            'fns_debtam' => 'ФНС - недоимки и задолженности',
            'fns_taxoffence' => 'ФНС - штрафы',
            'fns_npd' => 'ФНС - проверка статуса плательщика НПД (самозанятого)',
            'fns_invalid' => 'ФНС - проверка ИНН на недействительность',
        ];

        return isset($title[$checktype]) ? $title[$checktype] : $title[''];
        //        return 'Поиск сведений в ФНС РФ';
    }

    public function getSessionData($sourceid = 2)
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

        if ($sourceid) {
            $mysqli->query('UPDATE isphere.session s SET lasttime=now(),request_id='.$reqId." WHERE request_id IS NULL AND sessionstatusid=2 AND sourceid=$sourceid".(40 == $sourceid ? ' AND unix_timestamp(now())-unix_timestamp(lasttime)>30' : '').' ORDER BY lasttime limit 1');
            $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sourceid=$sourceid AND request_id=".$reqId.' ORDER BY lasttime limit 1');
        } else {
            $result = $mysqli->query("SELECT 0 id,'' cookies,now() starttime,now() lasttime,'' captcha,'' token,id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE status=1 AND proxygroup=1 ORDER BY lasttime limit 1");
        }

        if ($result) {
            $row = $result->fetch_object();

            if ($row) {
                $sessionData = new \stdClass();

                $sessionData->id = $row->id;
                $sessionData->code = $row->captcha;
                $sessionData->starttime = $row->starttime;
                $sessionData->lasttime = $row->lasttime;
                $sessionData->cookies = $row->cookies;
                $sessionData->token = $row->token;
                $sessionData->proxyid = $row->proxyid;
                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = \strlen($row->proxy_auth) > 1 ? $row->proxy_auth : false;

                if ($sourceid) {
                    $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1,statuscode='used',request_id=NULL".($row->captcha ? ',endtime=now(),sessionstatusid=3' : '').' WHERE id='.$sessionData->id);
                } else {
                    //                    $mysqli->query("UPDATE isphere.proxy SET lasttime=now() WHERE id=".$sessionData->proxyid);
                }
            }
        }

        return $sessionData;
    }

    public function prepareRequest(array $params, &$rContext)
    {
        global $http_connecttimeout, $http_timeout;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = \substr($initData['checktype'], 4);
        /*
                if(!isset($initData['inn']) && (!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date']) || !isset($initData['passport_series']) || !isset($initData['passport_number']))){
                    $rContext->setFinished();
                    $rContext->setError('Указаны не все обязательные параметры (ИНН или ФИО, дата рождения, серия и номер паспорта)');
                    return false;
                }
        */
        if ('inn' == $checktype) {
            if (!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date']) || !isset($initData['passport_series']) || !isset($initData['passport_number'])) {
                $rContext->setFinished();
                //                if(!isset($initData['inn'])) $rContext->setError('Указаны не все обязательные параметры (ФИО, дата рождения, серия и номер паспорта)');
                return false;
            }

            if (!\preg_match("/^\d{4}$/", $initData['passport_series']) || !\preg_match("/^\d{6}$/", $initData['passport_number'])/* || !intval($initData['passport_series']) */) {
                $rContext->setFinished();
                $rContext->setError('Некорректные значения серии или номера паспорта');

                return false;
            }

            if ('ТЕСТ' == \mb_strtoupper(\mb_substr($initData['last_name'], 0, 4)) && 'ТЕСТ' == \mb_strtoupper(\mb_substr($initData['first_name'], 0, 4)) && '1234' == $initData['passport_series'] && '123456' == $initData['passport_number']) {
                $data['Result'] = new ResultDataField('string', 'Result', 'ИНН найден, номер паспорта соответствует ФИО и дате рождения', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string', 'ResultCode', 'FOUND', 'Код результата', 'Код результата');
                $data['INN'] = new ResultDataField('string', 'INN', '123456789012', 'ИНН', 'ИНН');
                $data['Type'] = new ResultDataField('string', 'Type', 'inn', 'Тип записи', 'Тип записи');
                //                $rContext->setSwapData($swapData);
                $resultData = new ResultDataList();
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                return false;
            }
            /*
                    } elseif ($checktype=='egrip') {
                        if(!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['region_id'])){
                            $rContext->setFinished();
                            $rContext->setError('Указаны не все обязательные параметры (ФИО, регион)');
                            return false;
                        }
            */
        } elseif ('disqualified' == $checktype) {
            if (!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date'])) {
                $rContext->setFinished();
                //                $rContext->setError('Указаны не все обязательные параметры (ФИО, дата рождения)');
                return false;
            }
        } elseif ('mru' == $checktype) {
            if (!isset($initData['inn'])/* && (!isset($initData['last_name']) || !isset($initData['first_name'])) */) {
                $rContext->setFinished();
                //                $rContext->setError('Указаны не все обязательные параметры (ФИО или ИНН)');
                return false;
            }
        } elseif ('bi' == $checktype) {
            if (!isset($initData['inn'])) {
                $rContext->setFinished();
                //                $rContext->setError('Указаны не все обязательные параметры (ИНН)');
                return false;
            }
        } elseif ('disfind' == $checktype) {
            if (!isset($initData['inn'])) {
                $rContext->setFinished();
                $rContext->setError('Указаны не все обязательные параметры (ИНН)');

                return false;
            }
        } elseif ('svl' == $checktype) {
            if (!isset($initData['inn'])) {
                $rContext->setFinished();
                $rContext->setError('Указаны не все обязательные параметры (ИНН)');

                return false;
            }
        } elseif ('zd' == $checktype) {
            if (!isset($initData['inn'])) {
                $rContext->setFinished();
                $rContext->setError('Указаны не все обязательные параметры (ИНН)');

                return false;
            }
        } elseif ('snr' == $checktype || 'sshr' == $checktype || 'revexp' == $checktype || 'paytax' == $checktype || 'debtam' == $checktype || 'taxoffence' == $checktype) {
            if (!isset($initData['inn'])) {
                $rContext->setFinished();
                $rContext->setError('Указаны не все обязательные параметры (ИНН)');

                return false;
            }
        } elseif ('npd' == $checktype) {
            if (!isset($initData['inn'])) {
                $rContext->setFinished();
                //                $rContext->setError('Указаны не все обязательные параметры (ИНН)');
                return false;
            }
        } elseif ('invalid' == $checktype) {
            if (!isset($initData['inn'])) {
                $rContext->setFinished();
                //                $rContext->setError('Указаны не все обязательные параметры (ИНН)');
                return false;
            }
            /*
                    } elseif ($checktype=='ofd') {
                        if(!isset($initData['inn'])){
                            $rContext->setFinished();
                            $rContext->setError('Указаны не все обязательные параметры (ИНН)');
                            return false;
                        }
                    } elseif ($checktype=='uwsfind') {
                        if(!isset($initData['ogrn'])){
                            $rContext->setFinished();
            //                $rContext->setError('Указаны не все обязательные параметры (ИНН)');
                            return false;
                        }
            */
        } else {
            $rContext->setFinished();
            $rContext->setError('Неверный тип проверки: '.$checktype);

            return false;
        }

        if (('inn' == $checktype || 'mru' == $checktype || 'disqualified' == $checktype) && isset($initData['last_name']) && isset($initData['first_name']) && \preg_match("/[^А-Яа-яЁё\s\-\.]/ui", $initData['last_name'].' '.$initData['first_name'].(isset($initData['patronymic']) ? ' '.$initData['patronymic'] : ''))) {
            $rContext->setFinished();
            $rContext->setError('Имя может содержать только русские буквы');

            return false;
        }

        $ch = $rContext->getCurlHandler();

        if (('inn' == $checktype || 'bi' == $checktype || 'svl' == $checktype || 'npd' == $checktype) && !isset($swapData['url']) && !isset($swapData['session'])) {
            $id = ['inn' => 2, 'bi' => 20, 'svl' => 37, 'npd' => 40];
            $swapData['session'] = $this->getSessionData($id[$checktype]);
            if (!$swapData['session']) {
                if (isset($swapData['iteration']) && ($swapData['iteration'] >= 60)) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                } else {
                    (!isset($swapData['iteration'])) ? $swapData['iteration'] = 1 : $swapData['iteration']++;
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);
                }

                return false;
            } elseif ('npd' == $checktype) {
                if (isset($swapData['iteration']) && $swapData['iteration'] > 10 && \rand(0, 2)) {
                    $astro = ['213.108.196.179:10687'];
                    $swapData['session']->proxyid = 2;
                    $swapData['session']->proxy = $astro[\rand(0, \count($astro) - 1)];
                    $swapData['session']->proxy_auth = 'isphere:e6eac1';
                }
            }
        }
        $rContext->setSwapData($swapData);

        if (isset($swapData['captcha_image']) && !isset($swapData['captcha_value'])) {
            if (!isset($swapData['captcha_id'])) {
                $swapData['captcha_service'] = (int) (($swapData['iteration'] - 1) / 3) % \count($this->captcha_service);
                $rContext->setSwapData($swapData);
                //                echo date('H:i:s')." ".$swapData['iteration'].": Sending captcha to ".$this->captcha_service[$swapData['captcha_service']]['host']."\n";
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service']]['host']) {
                    $params = [
                        'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                        'method' => 'base64',
                        'body' => $swapData['captcha_image'],
//                        'regsense' => 1,
                        'min_len' => 6,
                        'max_len' => 6,
//                        'language' => 1,
//                        'lang' => 'ru',
                    ];
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service']]['host'].'/in.php';
                    \curl_setopt($ch, \CURLOPT_POST, true);
                    \curl_setopt($ch, \CURLOPT_POSTFIELDS, \http_build_query($params));
                } else {
                    $params = [
                        'clientKey' => $this->captcha_service[$swapData['captcha_service']]['key'],
                        'task' => [
                            'type' => 'ImageToTextTask',
                            'body' => $swapData['captcha_image'],
//                            "case" => true,
                            'minLength' => 6,
                            'maxLength' => 6,
                        ],
//                        "languagePool" => "ru",
                    ];
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service']]['host'].'/createTask';
                    \curl_setopt($ch, \CURLOPT_POST, true);
                    \curl_setopt($ch, \CURLOPT_POSTFIELDS, \json_encode($params, \JSON_UNESCAPED_UNICODE));
                }
            } else {
                //                echo date('H:i:s')." ".$swapData['iteration'].": Getting captcha value from ".$this->captcha_service[$swapData['captcha_service']]['host']."\n";
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service']]['host']) {
                    $params = [
                        'key' => $this->captcha_service[$swapData['captcha_service']]['key'],
                        'action' => 'get',
                        'id' => $swapData['captcha_id'],
                    ];
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service']]['host'].'/res.php?'.\http_build_query($params);
                } else {
                    $params = [
                        'clientKey' => $this->captcha_service[$swapData['captcha_service']]['key'],
                        'taskId' => $swapData['captcha_id'],
                    ];
                    $url = 'https://'.$this->captcha_service[$swapData['captcha_service']]['host'].'/getTaskResult';
                    \curl_setopt($ch, \CURLOPT_POST, true);
                    \curl_setopt($ch, \CURLOPT_POSTFIELDS, \json_encode($params, \JSON_UNESCAPED_UNICODE));
                }
            }
            \curl_setopt($ch, \CURLOPT_URL, $url);
            \curl_setopt($ch, \CURLOPT_TIMEOUT, 3);
            \curl_setopt($ch, \CURLOPT_PROXY, false);
        //            echo date('H:i:s')." ".$swapData['iteration'].": $url\n";
        //            var_dump($params);
        //            echo "\n";
        } elseif ('inn' == $checktype) {
            //        if (!isset($initData['inn'])) {
            if (!isset($swapData['requestId'])) {
                $post = [
                    'c' => 'find',
                    'fam' => $initData['last_name'],
                    'nam' => $initData['first_name'],
                    'otch' => isset($initData['patronymic']) ? $initData['patronymic'] : '',
                    'bdate' => isset($initData['date']) ? \date('d.m.Y', \strtotime($initData['date'])) : '',
                    'doctype' => 21,
                    'docno' => $initData['passport_series'][0].$initData['passport_series'][1].' '.$initData['passport_series'][2].$initData['passport_series'][3].' '.$initData['passport_number'],
                    'docdt' => isset($initData['issueDate']) ? \date('d.m.Y', \strtotime($initData['issueDate'])) : '',
                    'captcha' => '', // $swapData['session']->code,
                    'captchaToken' => '', // $swapData['session']->token,
                ];
                if (!isset($initData['patronymic']) || '' == \trim($initData['patronymic'])) {
                    $post['opt_otch'] = 1;
                }

                $url = 'https://service.nalog.ru/inn-new-proc.do';
            } else {
                $post = [
                    'c' => 'get',
                    'requestId' => $swapData['requestId'],
                ];

                $url = 'https://service.nalog.ru/inn-new-proc.json';
            }
            $ref = 'https://service.nalog.ru/inn.do';
            $header = [
               'X-Requested-With: XMLHttpRequest',
            ];

            \curl_setopt($ch, \CURLOPT_TIMEOUT, 5);
            \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
            \curl_setopt($ch, \CURLOPT_REFERER, $ref);
            \curl_setopt($ch, \CURLOPT_POST, true);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, $post);
            \curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);
        } elseif ('bi' == $checktype && !isset($swapData['url'])) {
            //        } else {
            $post = [
//                'c' => 'search',
                'requestType' => 'FINDPRS',
                'innPRS' => $initData['inn'],
                'bikPRS' => isset($initData['bik']) ? $initData['bik'] : '000000000',
                'fileName' => '',
                'bik' => '',
                'kodTU' => '',
                'dateSAFN' => '',
                'bikAFN' => '',
                'dateAFN' => '',
                'fileNameED' => '',
                'captcha' => '', // $swapData['session']->code,
                'captchaToken' => $swapData['session']->token,
            ];

            $url = 'https://service.nalog.ru/bi2-proc.json';
            $ref = $url;

            \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
            \curl_setopt($ch, \CURLOPT_REFERER, $ref);
            \curl_setopt($ch, \CURLOPT_POST, true);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, $post);
        //            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        } elseif ('bi' == $checktype && isset($swapData['url'])) {
            $url = $swapData['url'];
            \curl_setopt($ch, \CURLOPT_COOKIE, $swapData['session']->cookies);
        } elseif ('svl' == $checktype) {
            $post = [
                'isForm' => 'true',
                'ogrn' => '',
                'inn' => $initData['inn'],
                'captcha' => $swapData['session']->code,
                'captchaToken' => $swapData['session']->token,
            ];

            $url = 'https://service.nalog.ru/svl.do';
            $ref = $url;

            //            curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
            \curl_setopt($ch, \CURLOPT_REFERER, $ref);
            \curl_setopt($ch, \CURLOPT_POST, true);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, $post);
        //            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        } elseif ('npd' == $checktype) {
            $post = [
                'inn' => $initData['inn'],
                'requestDate' => \date('Y-m-d', isset($initData['reqdate']) ? \strtotime($initData['reqdate']) : \time()),
            ];

            $url = 'https://statusnpd.nalog.ru/api/v1/tracker/taxpayer_status';
            $ref = $url;

            \curl_setopt($ch, \CURLOPT_TIMEOUT, 10);
            \curl_setopt($ch, \CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            \curl_setopt($ch, \CURLOPT_POST, true);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, \json_encode($post));
        } elseif ('invalid' == $checktype) {
            $post = [
                'k' => 'fl',
                'inn' => $initData['inn'],
            ];

            $url = 'https://service.nalog.ru/invalid-inn-proc.json';
            $ref = 'https://service.nalog.ru/invalid-inn-fl.html';

            \curl_setopt($ch, \CURLOPT_TIMEOUT, 10);
            //            curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
            \curl_setopt($ch, \CURLOPT_REFERER, $ref);
            \curl_setopt($ch, \CURLOPT_POST, true);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, $post);
        //            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        } else {
            $url = 'https://i-sphere.ru';
        }

        \curl_setopt($ch, \CURLOPT_URL, $url);

        if (!(isset($swapData['captcha_image']) && !isset($swapData['captcha_value'])) && isset($swapData['session']) && $swapData['session']->proxy) {
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
        $error = false;

        global $mysqli;
        $initData = $rContext->getInitData();
        $checktype = \substr($initData['checktype'], 4);
        $last_name = isset($initData['last_name']) ? $initData['last_name'] : '';
        $first_name = isset($initData['first_name']) ? $initData['first_name'] : '';
        $middle_name = isset($initData['patronymic']) ? $initData['patronymic'] : '';
        $fio = \trim($last_name.' '.$first_name.' '.$middle_name);
        $birth_date = isset($initData['date']) ? \date('d.m.Y', \strtotime($initData['date'])) : '';

        $swapData = $rContext->getSwapData();
        (!isset($swapData['iteration'])) ? $swapData['iteration'] = 1 : $swapData['iteration']++;

        $rContext->setSwapData($swapData);
        $content = \curl_multi_getcontent($rContext->getCurlHandler());

        if (isset($swapData['captcha_image']) && !isset($swapData['captcha_value'])) {
            $content = $raw_content;
            //            echo "$content\n";
            $res = \json_decode($content, true);
            if (!isset($swapData['captcha_id'])) {
                //                echo "Thread "."  Getting new captcha\n";
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service']]['host']) {
                    if (false !== \strpos($content, 'OK|')) {
                        $swapData['captcha_id'] = \substr($content, 3);
                    } elseif ($swapData['iteration'] > 20) {
                        $rContext->setFinished();
                        $rContext->setError('Ошибка получения капчи');
                        \file_put_contents('./logs/fns/'.$initData['checktype'].'_captcha_err_'.$swapData['iteration'].'_'.\time().'.txt', $content."\r\n".$this->captcha_service[$swapData['captcha_service']]['host']);
                    }
                } else {
                    if (isset($res['taskId'])) {
                        $swapData['captcha_id'] = $res['taskId'];
                    } elseif ($swapData['iteration'] > 20) {
                        $rContext->setFinished();
                        $rContext->setError('Ошибка получения капчи');
                        \file_put_contents('./logs/fns/'.$initData['checktype'].'_captcha_err_'.$swapData['iteration'].'_'.\time().'.txt', $content."\r\n".$this->captcha_service[$swapData['captcha_service']]['host']);
                    }
                }
                $rContext->setSleep(5);
            } else {
                if ('rucaptcha.com' == $this->captcha_service[$swapData['captcha_service']]['host']) {
                    if ('CAPCHA_NOT_READY' == $content) {
                    } else {
                        if (false !== \strpos($content, 'OK|')) {
                            $swapData['captcha_value'] = \substr($content, 3);
                        //                            echo "Thread "."  Received captcha ID ".$swapData['captcha_id']."\n";
                        } else {
                            unset($swapData['captcha_id']);
                            unset($swapData['captcha_image']);
                            //                        } elseif ($swapData['iteration']>20) {
                            //                            $rContext->setFinished();
                            //                            $rContext->setError('Ошибка распознавания капчи');
                        }
                        //                        unset($swapData['captcha_id']);
                    }
                } else {
                    if (!$content) {
                    } elseif (isset($res['status']) && 'ready' !== $res['status']) {
                    } else {
                        if (isset($res['solution']['text'])) {
                            $swapData['captcha_value'] = $res['solution']['text'];
                        //                            echo "Thread "."  Received captcha ID ".$swapData['captcha_id']."\n";
                        } else {
                            unset($swapData['captcha_id']);
                            unset($swapData['captcha_image']);
                            //                        } elseif ($swapData['iteration']>20) {
                            //                            $rContext->setFinished();
                            //                            $rContext->setError('Ошибка распознавания капчи');
                        }
                        //                        unset($swapData['captcha_id']);
                    }
                }
                --$swapData['iteration'];
                $rContext->setSleep(1);
            }
            $rContext->setSwapData($swapData);

            return true;
        }

        if ('inn' == $checktype) {
            //        if (!isset($initData['inn'])) {
            if (!$content) {
                if ($swapData['iteration'] >= 5) {
                    $error = \curl_error($rContext->getCurlHandler());
                    //                    if (!$error) $error = "Сервис не отвечает";
                }
            }
            \file_put_contents('./logs/fns_inn/'.$initData['checktype'].(isset($swapData['requestId']) ? '_get' : '').'_'.\time().'.html', $content);
            $start = \strpos($content, '{');
            $finish = \strrpos($content, '}');
            if (false !== $start && false !== $finish) {
                $content = \substr($content, $start, $finish - $start + 1);
            }
            $res = \json_decode($content, true);
            if (!isset($swapData['requestId']) && isset($res['requestId'])) {
                $swapData['requestId'] = $res['requestId'];
                --$swapData['iteration'];
                $rContext->setSwapData($swapData);
            } elseif (isset($res['inn'])) {
                if (isset($swapData['session'])) {
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                }
                //                    $mysqli->query("UPDATE isphere.session SET endtime=now(),statuscode='success',sessionstatusid=3 WHERE id=" . $swapData['session']->id);

                $swapData['inn'] = \trim($res['inn']);
                $data['Result'] = new ResultDataField('string', 'Result', 'ИНН найден, номер паспорта соответствует ФИО и дате рождения', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string', 'ResultCode', 'FOUND', 'Код результата', 'Код результата');
                $data['INN'] = new ResultDataField('string', 'INN', $swapData['inn'], 'ИНН', 'ИНН');
                $data['Type'] = new ResultDataField('string', 'Type', 'inn', 'Тип записи', 'Тип записи');
                $swapData['data'] = $data;
                //                $rContext->setSwapData($swapData);
                $resultData = new ResultDataList();
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                return true;
            } elseif (isset($res['state'])) {
                if ((int) $res['state'] < 0) {
                    --$swapData['iteration'];
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);
                } else {
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);
                    }
                    //                        $mysqli->query("UPDATE isphere.session SET endtime=now(),statuscode='success',sessionstatusid=3 WHERE id=" . $swapData['session']->id);
                }

                if (0 == (int) $res['state']) {
                    $data['Result'] = new ResultDataField('string', 'Result', 'ИНН не найден или номер паспорта не соответствует ФИО и дате рождения', 'Результат', 'Результат');
                    $data['ResultCode'] = new ResultDataField('string', 'ResultCode', 'NOT_FOUND', 'Код результата', 'Код результата');
                    $data['Type'] = new ResultDataField('string', 'Type', 'inn', 'Тип записи', 'Тип записи');
                    $resultData = new ResultDataList();
                    $resultData->addResult($data);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();

                    return true;
                } elseif (3 == (int) $res['state']) {
                    $data['Result'] = new ResultDataField('string', 'Result', 'Указанные сведения не прошли однозначной идентификации по Единому государственному реестру налогоплательщиков', 'Результат', 'Результат');
                    $data['ResultCode'] = new ResultDataField('string', 'ResultCode', 'FOUND_SEVERAL', 'Код результата', 'Код результата');
                    $data['Type'] = new ResultDataField('string', 'Type', 'inn', 'Тип записи', 'Тип записи');
                    $resultData = new ResultDataList();
                    $resultData->addResult($data);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();

                    return true;
                } elseif ((int) $res['state'] > 0) {
                    \file_put_contents('./logs/fns_inn/'.$initData['checktype'].(isset($swapData['requestId']) ? '_state' : '').'_err_'.\time().'.txt', $content);
                    if ($swapData['iteration'] >= 3) {
                        $error = 'Не удалось выполнить поиск';
                    }
                }
            } else {
                if (isset($res['ERRORS'])) {
                    $content = '';
                    if (isset($res['ERRORS']['captcha']) || isset($res['ERRORS']['captchaToken'])) {
                        if (isset($swapData['session'])) {
                            $mysqli->query("UPDATE isphere.session SET endtime=now(),statuscode='invalidcaptcha',sessionstatusid=4 WHERE id=".$swapData['session']->id);
                        }
                        unset($swapData['session']);
                        unset($swapData['requestId']);
                        $rContext->setSwapData($swapData);
                        $rContext->setSleep(3);

                        return true;
                    }
                    foreach ($res['ERRORS'] as $field => $err) {
                        if ($content) {
                            $content .= '; ';
                        }
                        $content .= \trim(\stripslashes($err[0]));
                    }
                    $error = $content;
                } elseif (isset($res['ERROR'])) {
                    $content = \trim(\stripslashes($res['ERROR']));
                    if ('Произошла внутренняя ошибка' == $content && $swapData['iteration'] < 5) {
                        if (isset($swapData['session'])) {
                            $mysqli->query("UPDATE isphere.session SET endtime=now(),statuscode='internal',sessionstatusid=3 WHERE id=".$swapData['session']->id);
                        }
                        unset($swapData['session']);
                        unset($swapData['requestId']);
                        $rContext->setSwapData($swapData);
                    } else {
                        $error = $content;
                    }
                } elseif (0 == \strlen($content)) {
                    if (!isset($swapData['requestId'])) {
                        if (isset($swapData['session'])) {
                            $mysqli->query('UPDATE isphere.session SET unlocktime=date_add(now(),interval '.($swapData['session']->proxyid < 100 ? '30 second' : '1 minute')."),sessionstatusid=6,statuscode='empty' WHERE id=".$swapData['session']->id);
                        }
                        unset($swapData['session']);
                        //                        unset($swapData['requestId']);
                        $rContext->setSwapData($swapData);
                    }
                //                    return true;
                } elseif (\strpos($content, '405 Not Allowed')) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис выключен на стороне ФНС');

                    return false;
                } else {
                    \file_put_contents('./logs/fns_inn/'.$initData['checktype'].(isset($swapData['requestId']) ? '_get' : '').'_err_'.\time().'.txt', $content);
                    if ($swapData['iteration'] >= 3) {
                        $error = 'Некорректный ответ ФНС';
                    }
                }
            }
        } elseif ('bi' == $checktype && !isset($swapData['url'])) {
            if (!$content && $swapData['iteration'] >= 5) {
                $error = \curl_error($rContext->getCurlHandler());
            }
            \file_put_contents('./logs/fns/'.$initData['checktype'].'_'.\time().'.html', $content);
            $start = \strpos($content, '{');
            $finish = \strrpos($content, '}');
            if (false !== $start && false !== $finish) {
                $content = \substr($content, $start, $finish - $start + 1);
            }
            $res = \json_decode($content, true);

            if (isset($res['datePRS'])) {
                if (isset($swapData['session'])) {
                    $mysqli->query("UPDATE isphere.session SET endtime=now(),statuscode='success',sessionstatusid=3 WHERE id=".$swapData['session']->id);
                }

                $resultData = new ResultDataList();
                $found = isset($res['rows']) && \count($res['rows']);

                if ($found) {
                    foreach ($res['rows'] as $row) {
                        $data = [];
                        $data['DecisionNumber'] = new ResultDataField('string', 'DecisionNumber', $row['NOMER'], 'Номер решения', 'Номер решения о приостановлении');
                        $data['DecisionDate'] = new ResultDataField('string', 'DecisionDate', $row['DATA'], 'Дата решения', 'Дата решения о приостановлении');
                        $data['DepartmentCode'] = new ResultDataField('string', 'DepartmentCode', $row['IFNS'], 'Код налогового органа', 'Код налогового органа');
                        $data['BIK'] = new ResultDataField('string', 'BIK', $bik = $row['BIK'], 'БИК', 'БИК банка');
                        $result = $mysqli->query("SELECT * FROM fns.bik WHERE bik='$bik'");
                        if ($result) {
                            if ($bank = $result->fetch_assoc()) {
                                $data['Bank'] = new ResultDataField('string', 'Bank', $bank['name'], 'Банк', 'Банк');
                                $data['City'] = new ResultDataField('string', 'City', $bank['city'], 'Город', 'Город');
                            }
                            $result->close();
                        }
                        $data['DateTime'] = new ResultDataField('string', 'DateTime', $row['DATABI'], 'Дата и время', 'Дата и время размещения информации');
                        $data['Type'] = new ResultDataField('string', 'Type', 'decision', 'Тип записи', 'Тип записи');
                        $resultData->addResult($data);
                    }
                }

                $data = [];
                $data['Result'] = new ResultDataField('string', 'Result', 'Действующие решения о приостановлении по указанному налогоплательщику '.($found ? 'ИМЕЮТСЯ' : 'ОТСУТСТВУЮТ'), 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string', 'ResultCode', $found ? 'FOUND' : 'NOT_FOUND', 'Код результата', 'Код результата');
                if ($found && isset($res['rows'][0]['INN'])) {
                    $data['INN'] = new ResultDataField('string', 'INN', $res['rows'][0]['INN'], 'ИНН налогоплательщика', 'ИНН налогоплательщика');
                }
                if ($found && isset($res['rows'][0]['NAIM'])) {
                    $data['Name'] = new ResultDataField('string', 'Name', $res['rows'][0]['NAIM'], 'Наименование налогоплательщика', 'Наименование налогоплательщика');
                }
                $data['Type'] = new ResultDataField('string', 'Type', 'bi', 'Тип записи', 'Тип записи');

                if (isset($res['formToken']) && $res['formToken']) {
                    $swapData['url'] = 'https://service.nalog.ru/bi-pdf.do?token='.$res['formToken'];
                    $swapData['result'] = $resultData;
                    $swapData['data'] = $data;
                    $rContext->setSwapData($swapData);
                } else {
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                }

                return true;
            } else {
                if (isset($res['ERRORS'])) {
                    $content = '';
                    if (isset($res['ERRORS']['captcha']) || isset($res['ERRORS']['captchaToken'])) {
                        if (isset($swapData['session'])) {
                            $mysqli->query("UPDATE isphere.session SET endtime=now(),statuscode='invalidcaptcha',sessionstatusid=4 WHERE id=".$swapData['session']->id);
                        }
                        unset($swapData['session']);
                        $rContext->setSwapData($swapData);
                        $rContext->setSleep(3);

                        return true;
                    }
                    foreach ($res['ERRORS'] as $field => $err) {
                        if ($content) {
                            $content .= '; ';
                        }
                        $content .= \stripslashes($err[0]);
                    }
                    $rContext->setFinished();
                    $rContext->setError(\trim($content));
                } elseif (isset($res['ERROR'])) {
                    $error = \stripslashes($res['ERROR']);
                } elseif (0 == \strlen($content)) {
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),statuscode='empty',sessionstatusid=3 WHERE id=".$swapData['session']->id);
                    }
                    unset($swapData['session']);
                    $rContext->setSwapData($swapData);
                //                    return true;
                } elseif (\strpos($content, '405 Not Allowed')) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис выключен на стороне ФНС');

                    return false;
                } else {
                    \file_put_contents('./logs/fns/'.$initData['checktype'].'_err_'.\time().'.txt', $content);
                    if ($swapData['iteration'] >= 3) {
                        $error = 'Некорректный ответ ФНС';
                    }
                }
            }
        } elseif ('bi' == $checktype && isset($swapData['url'])) {
            if (!$content) {
                $error = ($swapData['iteration'] >= 5) && \curl_error($rContext->getCurlHandler());
            } elseif (\strlen($content) < 30000 || \preg_match('/<html>/', $content)) {
                if ($swapData['iteration'] >= 5) {
                    $resultData = $swapData['result'];
                    $data = $swapData['data'];
                    $resultData->addResult($data);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();

                    return true;
                }
            } else {
                global $serviceurl;
                $file = 'bi_'.$initData['inn'].'_'.\time().'.zip';

                \file_put_contents('./logs/fns/'.$file, $content);
                $resultData = $swapData['result'];
                $data = $swapData['data'];
                $data['pdf'] = new ResultDataField('url', 'PDF', $serviceurl.'logs/fns/'.$file, 'PDF', 'PDF');

                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                return true;
            }
        } elseif ('svl' == $checktype) {
            if (!$content) {
                $error = ($swapData['iteration'] >= 5) && \curl_error($rContext->getCurlHandler());
            }
            \file_put_contents('./logs/fns/'.$initData['checktype'].'_'.\time().'.txt', $content);
            if (\preg_match("/<h2>Результаты поиска<[^<]+<div [^>]+>([^<]+)<\/div>/sim", $content, $matches)) {
                if (isset($swapData['session'])) {
                    $mysqli->query("UPDATE isphere.session SET endtime=now(),statuscode='success',sessionstatusid=3 WHERE id=".$swapData['session']->id);
                }

                $resultData = new ResultDataList();
                $data = [];
                $data['Result'] = new ResultDataField('string', 'Result', $matches[1], 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string', 'ResultCode', \strpos($matches[1], 'не найдена') ? 'NOT_FOUND' : 'ERROR', 'Код результата', 'Код результата');
                $data['Type'] = new ResultDataField('string', 'Type', 'svl', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);

                $rContext->setResultData($resultData);
                $rContext->setFinished();

                return true;
            } elseif (\preg_match("/<h2>Результаты поиска<[^<]+<table [^>]+>(.*?)<\/table>/sim", $content, $dataPart)) {
                if (isset($swapData['session'])) {
                    $mysqli->query("UPDATE isphere.session SET endtime=now(),statuscode='success',sessionstatusid=3 WHERE id=".$swapData['session']->id);
                }

                $resultData = new ResultDataList();
                $data = [];
                $data['Result'] = new ResultDataField('string', 'Result', 'По данному юридическому лицу имеются факты невозможности руководства или участия', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string', 'ResultCode', 'FOUND', 'Код результата', 'Код результата');
                $data['Type'] = new ResultDataField('string', 'Type', 'svl', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);

                if (\preg_match_all("/<tr>[^<]*<td>([^<]+)<\/td>[^<]*<td>([^<]+)<\/td>[^<]*<td>([^<]+)<\/td>[^<]*<td>([^<]+)<\/td>[^<]*<td>([^<]+)<\/td>[^<]*<td>([^<]+)<\/td>[^<]*<td>([^<]+)<\/td>[^<]*<\/tr>/sim", $dataPart[1], $matches)) {
                    foreach ($matches[1] as $key => $match) {
                        $data = [];
                        $data['OGRN'] = new ResultDataField('string', 'OGRN', \trim(\html_entity_decode($matches[1][$key])), 'ОГРН организации', 'ОГРН организации');
                        $data['INN'] = new ResultDataField('string', 'INN', \trim(\html_entity_decode($matches[2][$key])), 'ИНН организации', 'ИНН организации');
                        $data['OrgName'] = new ResultDataField('string', 'OrgName', \trim(\html_entity_decode($matches[3][$key])), 'Наименование организации', 'Наименование организации');
                        $data['Name'] = new ResultDataField('string', 'Name', \trim(\html_entity_decode($matches[4][$key])), 'ФИО лица', 'ФИО лица');
                        $data['Status'] = new ResultDataField('string', 'Status', \trim(\html_entity_decode($matches[5][$key])), 'Правовое положение лица', 'Правовое положение лица');
                        $data['Reason'] = new ResultDataField('string', 'Reason', \trim(\html_entity_decode($matches[6][$key])), 'Причина невозможности руководства или участия', 'Причина невозможности руководства или участия');
                        $data['DocNumber'] = new ResultDataField('string', 'DocNumber', \trim(\html_entity_decode($matches[7][$key])), 'Судебный акт (номер дела)', 'Судебный акт (номер дела)');
                        $data['Type'] = new ResultDataField('string', 'Type', 'impossibility', 'Тип записи', 'Тип записи');
                        $resultData->addResult($data);
                    }
                }

                $rContext->setResultData($resultData);
                $rContext->setFinished();

                return true;
            } else {
                if (\strpos($content, 'введены неверно')) {
                    if (isset($swapData['session'])) {
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),statuscode='invalidcaptcha',sessionstatusid=4 WHERE id=".$swapData['session']->id);
                    }
                    unset($swapData['session']);
                    $rContext->setSwapData($swapData);
                    //                    $rContext->setSleep(3);
                    return true;
                } elseif (\preg_match('/class="err-panel"[^<]+<li><span>([^<]+)/', $content, $matches)) {
                    $rContext->setFinished();
                    $rContext->setError(\trim(\html_entity_decode($matches[1])));

                    return false;
                } elseif (\strpos($content, '405 Not Allowed')) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис выключен на стороне ФНС');

                    return false;
                } else {
                    \file_put_contents('./logs/fns/'.$initData['checktype'].'_err_'.\time().'.txt', $content);
                    if ($swapData['iteration'] >= 3) {
                        $error = 'Некорректный ответ ФНС';
                    }
                }
            }
        } elseif ('zd' == $checktype) {
            $resultData = new ResultDataList();

            $result = $mysqli->query("SELECT inn,ogrn,title FROM fns.zd_debt WHERE inn='".$initData['inn']."'");
            if ($result) {
                $data = [];
                $data['Result'] = new ResultDataField('string', 'Result', (0 == $result->num_rows ? 'ОТСУТСТВУЕТ' : 'ЧИСЛИТСЯ').' в реестре организаций, имеющих судебную задолженность по налогам свыше 1000 рублей', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string', 'ResultCode', 0 == $result->num_rows ? 'NOT_FOUND' : 'FOUND', 'Код результата', 'Код результата');
                if ($row = $result->fetch_assoc()) {
                    foreach ($row as $key => $val) {
                        $type = isset($this->titles[$key][2]) ? $this->titles[$key][2] : 'string';
                        if ($val) {
                            $data[$key] = new ResultDataField($type, $key, $val, $this->titles[$key][0], $this->titles[$key][1]);
                        }
                    }
                }
                $data['Type'] = new ResultDataField('string', 'Type', 'zd_debt', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);
            }
            $result->close();

            $result = $mysqli->query("SELECT inn,ogrn,title FROM fns.zd_report WHERE inn='".$initData['inn']."'");
            if ($result) {
                $data = [];
                $data['Result'] = new ResultDataField('string', 'Result', (0 == $result->num_rows ? 'ОТСУТСТВУЕТ' : 'ЧИСЛИТСЯ').' в реестре организаций, не предоставляющих отчетность более года', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string', 'ResultCode', 0 == $result->num_rows ? 'NOT_FOUND' : 'FOUND', 'Код результата', 'Код результата');
                if ($row = $result->fetch_assoc()) {
                    foreach ($row as $key => $val) {
                        $type = isset($this->titles[$key][2]) ? $this->titles[$key][2] : 'string';
                        if ($val) {
                            $data[$key] = new ResultDataField($type, $key, $val, $this->titles[$key][0], $this->titles[$key][1]);
                        }
                    }
                }
                $data['Type'] = new ResultDataField('string', 'Type', 'zd_report', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);
                $result->close();
            }

            $rContext->setResultData($resultData);
            $rContext->setFinished();

            return true;
        } elseif ('snr' == $checktype) {
            $resultData = new ResultDataList();

            $result = $mysqli->query("SELECT inn,date,usn,eshn,envd,srp FROM fns.snr WHERE inn='".$initData['inn']."'");
            if ($result) {
                $data = [];
                if ($row = $result->fetch_assoc()) {
                    foreach ($row as $key => $val) {
                        $type = isset($this->titles[$key][2]) ? $this->titles[$key][2] : 'string';
                        //                        if ($val)
                        $data[$key] = new ResultDataField($type, $key, $val, $this->titles[$key][0], $this->titles[$key][1]);
                    }
                    $data['Type'] = new ResultDataField('string', 'Type', 'snr', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                }
                $result->close();
            }

            $rContext->setResultData($resultData);
            $rContext->setFinished();

            return true;
        } elseif ('sshr' == $checktype) {
            $resultData = new ResultDataList();

            $result = $mysqli->query("SELECT inn,date,quant FROM fns.sshr WHERE inn='".$initData['inn']."'");
            if ($result) {
                $data = [];
                if ($row = $result->fetch_assoc()) {
                    foreach ($row as $key => $val) {
                        $type = isset($this->titles[$key][2]) ? $this->titles[$key][2] : 'string';
                        //                        if ($val)
                        $data[$key] = new ResultDataField($type, $key, $val, $this->titles[$key][0], $this->titles[$key][1]);
                    }
                    $data['Type'] = new ResultDataField('string', 'Type', 'sshr', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                }
            }
            $result->close();

            $rContext->setResultData($resultData);
            $rContext->setFinished();

            return true;
        } elseif ('revexp' == $checktype) {
            $resultData = new ResultDataList();

            $result = $mysqli->query("SELECT inn,dataState date,sumIncome,sumExpense FROM fns.revexp WHERE inn='".$initData['inn']."'");
            if ($result) {
                $data = [];
                if ($row = $result->fetch_assoc()) {
                    foreach ($row as $key => $val) {
                        $type = isset($this->titles[$key][2]) ? $this->titles[$key][2] : 'string';
                        //                        if ($val)
                        $data[$key] = new ResultDataField($type, $key, $val, $this->titles[$key][0], $this->titles[$key][1]);
                    }
                    $data['Type'] = new ResultDataField('string', 'Type', 'revexp', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                }
                $result->close();
            }

            $rContext->setResultData($resultData);
            $rContext->setFinished();

            return true;
        } elseif ('paytax' == $checktype) {
            $resultData = new ResultDataList();

            $result = $mysqli->query("SELECT inn,dataState date,Nalog json FROM fns.paytax WHERE inn='".$initData['inn']."'");
            if ($result) {
                $data = [];
                if ($row = $result->fetch_assoc()) {
                    foreach ($row as $key => $val) {
                        if ('json' == $key) {
                            $res = \json_decode($val, true);
                            foreach ($res as $nalog => $sum) {
                                //                                echo $nalog.": ".$sum."\n";
                                if (isset($this->nalog_fields[$nalog])) {
                                    $field = $this->nalog_fields[$nalog];
                                    $data[$field[0]] = new ResultDataField('string', $field[0], $sum, isset($field[1]) ? $field[1] : $nalog, isset($field[2]) ? $field[2] : $nalog);
                                }
                            }
                        } else {
                            $type = isset($this->titles[$key][2]) ? $this->titles[$key][2] : 'string';
                            //                            if ($val)
                            $data[$key] = new ResultDataField($type, $key, $val, $this->titles[$key][0], $this->titles[$key][1]);
                        }
                    }
                    $data['Type'] = new ResultDataField('string', 'Type', 'paytax', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                }
                $result->close();
            }

            $rContext->setResultData($resultData);
            $rContext->setFinished();

            return true;
        } elseif ('debtam' == $checktype) {
            $resultData = new ResultDataList();

            $result = $mysqli->query("SELECT inn,dataState date,Nalog json FROM fns.debtam WHERE inn='".$initData['inn']."'");
            if ($result) {
                $data = [];
                if ($row = $result->fetch_assoc()) {
                    foreach ($row as $key => $val) {
                        if ('json' == $key) {
                            $res = \json_decode($val, true);
                            foreach ($res as $debt) {
                                $nalog = $debt['НаимНалог'];
                                $sum = $debt['СумНедНалог'];
                                $penalty = $debt['СумПени'];
                                $fine = $debt['СумШтраф'];
                                $total = $debt['ОбщСумНедоим'];
                                //                                echo $nalog.": ".$sum."\n";
                                if (isset($this->nalog_fields[$nalog])) {
                                    $field = $this->nalog_fields[$nalog];
                                    $data[$field[0]] = new ResultDataField('string', $field[0], $sum, isset($field[1]) ? $field[1] : $nalog, isset($field[2]) ? $field[2] : $nalog);
                                    $data[$field[0].'_penalty'] = new ResultDataField('float', $field[0].'_penalty', $penalty, (isset($field[1]) ? $field[1] : $nalog).' (пени)', (isset($field[2]) ? $field[2] : $nalog).' (пени)');
                                    $data[$field[0].'_fine'] = new ResultDataField('float', $field[0].'_fine', $fine, (isset($field[1]) ? $field[1] : $nalog).' (штраф)', (isset($field[2]) ? $field[2] : $nalog).' (штраф)');
                                    $data[$field[0].'_total'] = new ResultDataField('float', $field[0].'_total', $total, (isset($field[1]) ? $field[1] : $nalog).' (итого)', (isset($field[2]) ? $field[2] : $nalog).' (итого)');
                                }
                            }
                        } else {
                            $type = isset($this->titles[$key][2]) ? $this->titles[$key][2] : 'string';
                            //                            if ($val)
                            $data[$key] = new ResultDataField($type, $key, $val, $this->titles[$key][0], $this->titles[$key][1]);
                        }
                    }
                    $data['Type'] = new ResultDataField('string', 'Type', 'debtam', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                }
                $result->close();
            }

            $rContext->setResultData($resultData);
            $rContext->setFinished();

            return true;
        } elseif ('taxoffence' == $checktype) {
            $resultData = new ResultDataList();

            $result = $mysqli->query("SELECT inn,dataState date,Penalty FROM fns.taxoffence WHERE inn='".$initData['inn']."'");
            if ($result) {
                $data = [];
                if ($row = $result->fetch_assoc()) {
                    foreach ($row as $key => $val) {
                        $type = isset($this->titles[$key][2]) ? $this->titles[$key][2] : 'string';
                        //                        if ($val)
                        $data[$key] = new ResultDataField($type, $key, $val, $this->titles[$key][0], $this->titles[$key][1]);
                    }
                    $data['Type'] = new ResultDataField('string', 'Type', 'taxoffence', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                }
                $result->close();
            }

            $rContext->setResultData($resultData);
            $rContext->setFinished();

            return true;
        } elseif ('disfind' == $checktype) {
            $resultData = new ResultDataList();

            $result = $mysqli->query("SELECT inn,kpp,ogrn,orgName,address FROM fns.disfind WHERE inn='".$initData['inn']."'");
            if ($result) {
                $data = [];
                $data['Result'] = new ResultDataField('string', 'Result', (0 == $result->num_rows ? 'ОТСУТСТВУЕТ' : 'ЧИСЛИТСЯ').' в реестре организаций с дисквалифицированными лицами в составе исполнительного органа', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string', 'ResultCode', 0 == $result->num_rows ? 'NOT_FOUND' : 'FOUND', 'Код результата', 'Код результата');
                if ($row = $result->fetch_assoc()) {
                    foreach ($row as $key => $val) {
                        $type = isset($this->titles[$key][2]) ? $this->titles[$key][2] : 'string';
                        if ($val) {
                            $data[$key] = new ResultDataField($type, $key, $val, $this->titles[$key][0], $this->titles[$key][1]);
                        }
                    }
                }
                $data['Type'] = new ResultDataField('string', 'Type', 'disfind', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);
                $result->close();
            }

            $result = $mysqli->query("SELECT fio,birthDate,birthPlace,orgName,orgInn,post,term,startDate,endDate,article,authName,judgePost,judgeName FROM fns.disqualified WHERE orgInn='".$initData['inn']."'");
            if ($result) {
                $data = [];
                $data['Result'] = new ResultDataField('string', 'Result', (0 == $result->num_rows ? 'ОТСУТСТВУЕТ' : 'ЧИСЛИТСЯ').' в реестре дисквалифицированных лиц', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string', 'ResultCode', 0 == $result->num_rows ? 'NOT_FOUND' : 'FOUND', 'Код результата', 'Код результата');
                if ($row = $result->fetch_assoc()) {
                    foreach ($row as $key => $val) {
                        $type = isset($this->titles[$key][2]) ? $this->titles[$key][2] : 'string';
                        if ($val) {
                            $data[$key] = new ResultDataField($type, $key, $val, $this->titles[$key][0], $this->titles[$key][1]);
                        }
                    }
                }
                $data['Type'] = new ResultDataField('string', 'Type', 'disqualified', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);
            }
            $result->close();

            $rContext->setResultData($resultData);
            $rContext->setFinished();

            return true;
        } elseif ('disqualified' == $checktype) {
            $resultData = new ResultDataList();

            $result = $mysqli->query("SELECT fio,birthDate,birthPlace,orgName,orgInn,post,term,startDate,endDate,article,authName,judgePost,judgeName FROM fns.disqualified WHERE fio='$fio'".($birth_date ? " AND birthDate='$birth_date'" : ''));
            if ($result) {
                $data = [];
                $data['Result'] = new ResultDataField('string', 'Result', (0 == $result->num_rows ? 'ОТСУТСТВУЕТ' : 'ЧИСЛИТСЯ').' в реестре дисквалифицированных лиц', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string', 'ResultCode', 0 == $result->num_rows ? 'NOT_FOUND' : 'FOUND', 'Код результата', 'Код результата');
                if ($row = $result->fetch_assoc()) {
                    foreach ($row as $key => $val) {
                        $type = isset($this->titles[$key][2]) ? $this->titles[$key][2] : 'string';
                        if ($val) {
                            $data[$key] = new ResultDataField($type, $key, $val, $this->titles[$key][0], $this->titles[$key][1]);
                        }
                    }
                }
                $data['Type'] = new ResultDataField('string', 'Type', 'disqualified', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);
                $result->close();
            }

            $rContext->setResultData($resultData);
            $rContext->setFinished();

            return true;
        } elseif ('mru' == $checktype) {
            $resultData = new ResultDataList();

            $result = $mysqli->query("SELECT inn,lastname,firstname,patronymic,quantity FROM fns.mru_ruk WHERE inn='".$initData['inn']."'");
            //            $result = $mysqli->query("SELECT inn,lastname,firstname,patronymic,quantity FROM fns.mru_ruk WHERE ".(isset($initData['inn'])?"inn='".$initData['inn']."'".($last_name?" OR ":""):"").($last_name?"(lastname='$last_name' AND firstname='$first_name' AND patronymic='$middle_name')":""));
            if ($result) {
                $data = [];
                $data['Result'] = new ResultDataField('string', 'Result', (0 == $result->num_rows ? 'ОТСУТСТВУЕТ' : 'ЧИСЛИТСЯ').' в реестре массовых руководителей', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string', 'ResultCode', 0 == $result->num_rows ? 'NOT_FOUND' : 'FOUND', 'Код результата', 'Код результата');
                if ($row = $result->fetch_assoc()) {
                    foreach ($row as $key => $val) {
                        $type = isset($this->titles[$key][2]) ? $this->titles[$key][2] : 'string';
                        if ($val) {
                            $data[$key] = new ResultDataField($type, $key, $val, $this->titles[$key][0], $this->titles[$key][1]);
                        }
                    }
                }
                $data['Type'] = new ResultDataField('string', 'Type', 'mru_ruk', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);
                $result->close();
            }

            //            $result = $mysqli->query("SELECT inn,lastname,firstname,patronymic,quantity FROM fns.mru_uchr WHERE ".(isset($initData['inn'])?"inn='".$initData['inn']."'".($last_name?" OR ":""):"").($last_name?"(lastname='$last_name' AND firstname='$first_name' AND patronymic='$middle_name')":""));
            $result = $mysqli->query("SELECT inn,lastname,firstname,patronymic,quantity FROM fns.mru_uchr WHERE inn='".$initData['inn']."'");
            if ($result) {
                $data = [];
                $data['Result'] = new ResultDataField('string', 'Result', (0 == $result->num_rows ? 'ОТСУТСТВУЕТ' : 'ЧИСЛИТСЯ').' в реестре массовых учредителей', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string', 'ResultCode', 0 == $result->num_rows ? 'NOT_FOUND' : 'FOUND', 'Код результата', 'Код результата');
                if ($row = $result->fetch_assoc()) {
                    foreach ($row as $key => $val) {
                        $type = isset($this->titles[$key][2]) ? $this->titles[$key][2] : 'string';
                        if ($val) {
                            $data[$key] = new ResultDataField($type, $key, $val, $this->titles[$key][0], $this->titles[$key][1]);
                        }
                    }
                }
                $data['Type'] = new ResultDataField('string', 'Type', 'mru_uchr', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);
                $result->close();
            }

            $rContext->setResultData($resultData);
            $rContext->setFinished();

            return true;
        } elseif ('npd' == $checktype) {
            if (!$content) {
                if ($swapData['iteration'] >= 5) {
                    $error = \curl_error($rContext->getCurlHandler());
                }
            }
            \file_put_contents('./logs/fns/'.$initData['checktype'].'_'.\time().'.txt', $content);
            $start = \strpos($content, '{');
            $finish = \strrpos($content, '}');
            if (false !== $start && false !== $finish) {
                $content = \substr($content, $start, $finish - $start + 1);
            }
            $res = \json_decode($content, true);

            if (isset($res['status'])) {
                $resultData = new ResultDataList();

                $data = [];
                $data['Result'] = new ResultDataField('string', 'Result', $res['message'], 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string', 'ResultCode', $res['status'] ? 'FOUND' : 'NOT_FOUND', 'Код результата', 'Код результата');
                $data['Type'] = new ResultDataField('string', 'Type', 'npd', 'Тип записи', 'Тип записи');

                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=".$swapData['session']->id);

                return true;
            } else {
                if ($res && isset($res['message']) && false !== \strpos($res['message'], 'Временно')) {
                    $error = 'Сервис временно недоступен';
                } elseif ($res && isset($res['message']) && false !== \strpos($res['message'], 'Превышено')) {
                    --$swapData['iteration'];
                    //                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='exhausted' WHERE id=" . $swapData['session']->id);
                    $mysqli->query('UPDATE isphere.session SET unlocktime=date_add(now(),interval '.($swapData['session']->proxyid < 100 ? '30 second' : '10 minute')."),sessionstatusid=6,statuscode='limit' WHERE id=".$swapData['session']->id);
                    unset($swapData['session']);
                } elseif (!$content) {
                    \file_put_contents('./logs/fns/'.$initData['checktype'].'_empty_'.\time().'.txt', $content);
                    //                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval ".($swapData['session']->proxyid<100?"15 second":"10 minute")."),sessionstatusid=6,statuscode='empty' WHERE sourceid=40 AND proxyid=" . $swapData['session']->proxyid . " ORDER BY lasttime DESC LIMIT 10");
                    $mysqli->query('UPDATE isphere.session SET unlocktime=date_add(now(),interval '.($swapData['session']->proxyid < 100 ? '30 second' : '10 minute')."),sessionstatusid=6,statuscode='empty' WHERE id=".$swapData['session']->id);
                    unset($swapData['session']);
                } else {
                    \file_put_contents('./logs/fns/'.$initData['checktype'].'_err_'.\time().'.txt', $content);
                    if ($swapData['iteration'] >= 3) {
                        $error = ($res && isset($res['message'])) ? $res['message'] : 'Некорректный ответ ФНС';
                    }
                    unset($swapData['session']);
                }
            }
        } elseif ('invalid' == $checktype) {
            if (!$content) {
                if ($swapData['iteration'] >= 5) {
                    $error = \curl_error($rContext->getCurlHandler());
                }
            }
            \file_put_contents('./logs/fns/'.$initData['checktype'].'_'.\time().'.txt', $content);

            $start = \strpos($content, '{');
            $finish = \strrpos($content, '}');
            if (false !== $start && false !== $finish) {
                $content = \substr($content, $start, $finish - $start + 1);
            }
            $res = \json_decode($content, true);

            if (isset($res['inn'])) {
                $resultData = new ResultDataList();

                if (isset($res['date'])) {
                    $data = [];
                    $data['INN'] = new ResultDataField('string', 'INN', $res['inn'], 'ИНН', 'ИНН');
                    $data['Result'] = new ResultDataField('string', 'Result', 'ИНН недействителен', 'Результат', 'Результат');
                    $data['ResultCode'] = new ResultDataField('string', 'ResultCode', 'NOT_VALID', 'Код результата', 'Код результата');
                    $data['DateTime'] = new ResultDataField('string', 'DateTime', \substr($res['date'], 0, 10), 'Дата признания недействительным', 'Дата признания недействительным');
                    $data['Type'] = new ResultDataField('string', 'Type', 'invalid', 'Тип записи', 'Тип записи');

                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                return true;
            } elseif (isset($res['ERROR'])) {
                if ('Произошла внутренняя ошибка' != $res['ERROR'] || $swapData['iteration'] >= 5) {
                    $error = $res['ERROR'];
                } else {
                    $rContext->setSleep(3);
                }
            } elseif ($content) {
                \file_put_contents('./logs/fns/'.$initData['checktype'].'_err_'.\time().'.txt', $content);
                if ($swapData['iteration'] >= 3) {
                    $error = 'Некорректный ответ ФНС';
                }
            }
        } else {
            $error = 'Неизвестный метод проверки';
        }

        if ($error || $swapData['iteration'] >= /* $checktype!='npd'?5: */ 20) {
            $rContext->setFinished();
            $rContext->setError(!$error ? 'Превышено количество попыток получения ответа' : $error);

            return false;
        }

        $rContext->setSwapData($swapData);
        $rContext->setSleep(1);

        return true;
    }
}

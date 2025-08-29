<?php

class FNSPlugin implements PluginInterface
{
     private $titles = array (
	   'inn' => array('ИНН','ИНН'),
	   'orgInn' => array('ИНН организации','ИНН организации'),
	   'ogrn' => array('ОГРН','ОГРН'),
	   'title' => array('Название','Название'),
	   'orgName' => array('Название','Название'),
	   'kpp' => array('КПП','КПП'),
	   'address' => array('Адрес','Адрес'),
	   'fio' => array('ФИО','ФИО'),
	   'birthDate' => array('Дата рождения','Дата рождения'),
	   'birthPlace' => array('Место рождения','Место рождения'),
	   'post' => array('Должность','Должность'),
	   'term' => array('Срок дисквалификации','Срок дисквалификации'),
	   'startDate' => array('Дата начала','Дата начала'),
	   'endDate' => array('Дата окончания','Дата окончания'),
	   'article' => array('Основание','Основание'),
	   'authName' => array('Подразделение ФНС','Подразделение ФНС'),
	   'judgePost' => array('Должность судьи','Должность судьи'),
	   'judgeName' => array('ФИО судьи','ФИО судьи'),
	   'lastname' => array('Фамилия','Фамилия'),
	   'firstname' => array('Имя','Имя'),
	   'patronymic' => array('Отчество','Отчество'),
	   'quantity' => array('Количество организаций','Количество организаций'),
	   'date' => array('Дата','Дата'),
	   'usn' => array('УСН','УСН'),
	   'envd' => array('ЕНВД','ЕНВД'),
	   'eshn' => array('ЕСХН','ЕСХН'),
	   'srp' => array('СРП','СРП'),
	   'quant' => array('Среднесписочная численность сотрудников','Среднесписочная численность сотрудников'),
	   'sumIncome' => array('Сумма доходов','Сумма доходов'),
	   'sumExpense' => array('Сумма расходов','Сумма расходов'),
	   'Penalty' => array('Сумма штрафа','Сумма штрафа'),
     );

     private $nalog_fields = array(
         "Налог, взимаемый в связи с  применением упрощенной  системы налогообложения" => array('usn', 'Налог УСН', 'Налог УСН'),
         "Транспортный налог" => array('transport_tax'),
         "Страховые взносы на обязательное медицинское страхование работающего населения, зачисляемые в бюджет Федерального фонда обязательного медицинского страхования" => array('oms', 'Страховые взносы на ОМС', 'Страховые взносы на ОМС'),
         "Страховые взносы на обязательное социальное страхование на случай временной нетрудоспособности и в связи с материнством" => array('fss', 'Страховые взносы ФСС', 'Страховые взносы ФСС'),
         "Земельный налог" => array('land_tax'),
         "Единый налог на вмененный доход для отдельных видов  деятельности" => array('envd', 'Единый налог на вмененный доход', 'Единый налог на вмененный доход'),
         "Страховые и другие взносы на обязательное пенсионное страхование, зачисляемые в Пенсионный фонд Российской Федерации" => array('pfr', 'Страховые взносы ПРФ', 'Страховые взносы ПРФ'),
         "Налог на прибыль" => array('profit_tax'),
         "Налог на добавленную стоимость" => array('nds'),
         "Налог на имущество организаций" => array('wealth_tax'),
         "Торговый сбор" => array('retail_fee'),
         "Налог на добычу полезных ископаемых" => array('ndpi'),
         "Сборы за пользование объектами животного мира  и за пользование объектами ВБР" => array('bio_tax', 'Сборы за пользование биоресурсами', 'Сборы за пользование биоресурсами'),
         "Единый сельскохозяйственный налог" => array('eshn'),
         "Водный налог" => array('water_tax'),
         "Акцизы, всего" => array('excises'),
         "НЕНАЛОГОВЫЕ ДОХОДЫ, администрируемые налоговыми органами" => array('nontax'),
         "Налог на доходы физических лиц" => array('ndfl'),
         "Задолженность и перерасчеты по ОТМЕНЕННЫМ НАЛОГАМ  и сборам и иным обязательным платежам  (кроме ЕСН, страх. Взносов)" => array('cancelled_taxes'),
         "Утилизационный сбор" => array('recycling_fee'),
         "Налог на игорный" => array('gambling_tax', 'Налог на игорный бизнес', 'Налог на игорный бизнес'),
         "Налог, взимаемый в связи с  применением патентной системы  налогообложения" => array('psn', 'Налог ПСН', 'Налог ПСН'),
         "Государственная пошлина" => array('state_duty'),
         "Регулярные платежи за добычу полезных ископаемых (роялти) при выполнении соглашений о разделе продукции" => array('srp', 'Платежи за пользование недрами по СРП', 'Платежи за пользование недрами по СРП'),
     );

     public function str_uprus($text) {
        $up = array(
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
        );
        if (preg_match("/[а-я]/", $text))
                $text = strtr($text, $up);
        return $text;
    }

    public function getName()
    {
        return 'FNS';
    }

    public function getTitle($checktype = '')
    {
        $title = array(
            '' => 'Поиск сведений в ФНС РФ',
            'fns_inn' => 'ФНС - определение ИНН',
            'fns_bi' => 'ФНС - решения о приостановлении операций по р/с',
            'fns_mru' => 'ФНС - массовые руководители и учредители',
            'fns_zd' => 'ФНС - задолженность по налогам и отчетности',
            'fns_disqualified' => 'ФНС - дисквалифицированные лица',
            'fns_disfind' => 'ФНС - организации, управляемые дисквалифицированными лицами',
            'fns_rmsp' => 'ФНС - реестр субъектов малого и среднего предпринимательства',
            'fns_sshr' => 'ФНС - среднесписочная численность сотрудников',
            'fns_snr' => 'ФНС - специальные налоговые режимы',
            'fns_revexp' => 'ФНС - доходы и расходы',
            'fns_paytax' => 'ФНС - уплаченные налоги и взносы',
            'fns_debtam' => 'ФНС - недоимки и задолженности',
            'fns_taxoffence' => 'ФНС - штрафы',
            'fns_npd' => 'ФНС - проверка статуса плательщика НПД (самозанятого)',
            'fns_invalid' => 'ФНС - проверка ИНН на недействительность',
        );
        return isset($title[$checktype])?$title[$checktype]:$title[''];
//        return 'Поиск сведений в ФНС РФ';
    }

    public function getSessionData($sourceid = 2)
    {
        global $mysqli;
        global $reqId;
        $sessionData = null;

        if ($sourceid) {
            $mysqli->query("UPDATE isphere.session s SET lasttime=now(),request_id=".$reqId." WHERE request_id IS NULL AND sessionstatusid=2 AND sourceid=$sourceid".($sourceid==40?" AND lasttime<DATE_SUB(now(), INTERVAL 30 SECOND)":"")." ORDER BY lasttime limit 1");
            $result = $mysqli->query("SELECT id,cookies,starttime,lasttime,captcha,token,proxyid,(SELECT concat(server,':',port) FROM proxy WHERE id=s.proxyid) proxy,(SELECT concat(login,':',password) FROM proxy WHERE id=s.proxyid) proxy_auth FROM isphere.session s WHERE sourceid=$sourceid AND request_id=".$reqId." ORDER BY lasttime limit 1");
        } else
            $result = $mysqli->query("SELECT 0 id,'' cookies,now() starttime,now() lasttime,'' captcha,'' token,id proxyid, concat(server,':',port) proxy, concat(login,':',password) proxy_auth FROM isphere.proxy WHERE status=1 AND proxygroup=1 ORDER BY lasttime limit 1");

        if($result)
        {
            $row = $result->fetch_object();

            if ($row)
            {
                $sessionData = new \StdClass;

                $sessionData->id = $row->id;
                $sessionData->code = $row->captcha;
                $sessionData->starttime = $row->starttime;
                $sessionData->lasttime = $row->lasttime;
                $sessionData->cookies = $row->cookies;
                $sessionData->token = $row->token;
                $sessionData->proxyid = $row->proxyid;
                $sessionData->proxy = $row->proxy;
                $sessionData->proxy_auth = strlen($row->proxy_auth)>1?$row->proxy_auth:false;

                if ($sourceid) { 
                    $mysqli->query("UPDATE isphere.session SET lasttime=now(),used=ifnull(used,0)+1,statuscode='used',request_id=NULL".($row->captcha?",endtime=now(),sessionstatusid=3":"")." WHERE id=".$sessionData->id);
                } else {
//                    $mysqli->query("UPDATE isphere.proxy SET lasttime=now() WHERE id=".$sessionData->proxyid);
                }
            }
        }

        return $sessionData;
    }

    public function prepareRequest(&$rContext)
    {
        global $http_connecttimeout, $http_timeout;

        $initData = $rContext->getInitData();
        $swapData = $rContext->getSwapData();

        $checktype = substr($initData['checktype'],4);
/*
        if(!isset($initData['inn']) && (!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date']) || !isset($initData['passport_series']) || !isset($initData['passport_number']))){
            $rContext->setFinished();
            $rContext->setError('Указаны не все обязательные параметры (ИНН или ФИО, дата рождения, серия и номер паспорта)');
            return false;
        }
*/
        if ($checktype=='inn') {
/*
            global $userId;
            if ($userId==975) {
                if (!isset($initData['last_name'])) $initData['last_name']='';
                if (!isset($initData['first_name'])) $initData['middle_name']='';
                if (!isset($initData['patronymic'])) $initData['patronymic']='';
                $last_name = $initData['last_name'];
                $initData['last_name'] = $initData['patronymic'];
                $initData['patronymic'] = $last_name;
            }
*/
            if(!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date']) || !isset($initData['passport_series']) || !isset($initData['passport_number'])){
                $rContext->setFinished();
//                if(!isset($initData['inn'])) $rContext->setError('Указаны не все обязательные параметры (ФИО, дата рождения, серия и номер паспорта)');
                return false;
            }

            if (!preg_match("/^\d{4}$/", $initData['passport_series']) || !preg_match("/^\d{6}$/", $initData['passport_number'])/* || !intval($initData['passport_series'])*/){
                $rContext->setFinished();
                $rContext->setError('Некорректные значения серии или номера паспорта');
                return false;
            }

            if(mb_strtoupper(mb_substr($initData['last_name'],0,4))=='ТЕСТ' && mb_strtoupper(mb_substr($initData['first_name'],0,4))=='ТЕСТ' && $initData['passport_series']=='1234' && $initData['passport_number']=='123456'){
                $data['Result'] = new ResultDataField('string','Result', 'ИНН найден, номер паспорта соответствует ФИО и дате рождения', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string','ResultCode', 'FOUND', 'Код результата', 'Код результата');
                $data['INN'] = new ResultDataField('string','INN', '123456789012', 'ИНН', 'ИНН');
                $data['Type'] = new ResultDataField('string','Type', 'inn', 'Тип записи', 'Тип записи');
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
        } elseif ($checktype=='disqualified') {
            if((!isset($initData['last_name']) || !isset($initData['first_name']) || !isset($initData['date']))){
                $rContext->setFinished();
//                $rContext->setError('Указаны не все обязательные параметры (ФИО, дата рождения)');
                return false;
            }
        } elseif ($checktype=='mru') {
            if(!isset($initData['inn'])/* && (!isset($initData['last_name']) || !isset($initData['first_name']))*/){
                $rContext->setFinished();
//                $rContext->setError('Указаны не все обязательные параметры (ФИО или ИНН)');
                return false;
            }
        } elseif ($checktype=='bi') {
            if(!isset($initData['inn'])){
                $rContext->setFinished();
//                $rContext->setError('Указаны не все обязательные параметры (ИНН)');
                return false;
            }
        } elseif ($checktype=='disfind') {
            if(!isset($initData['inn'])){
                $rContext->setFinished();
//                $rContext->setError('Указаны не все обязательные параметры (ИНН)');
                return false;
            }
        } elseif ($checktype=='rmsp') {
            if(!isset($initData['inn'])){
                $rContext->setFinished();
//                $rContext->setError('Указаны не все обязательные параметры (ИНН)');
                return false;
            }
        } elseif ($checktype=='zd') {
            if(!isset($initData['inn'])){
                $rContext->setFinished();
//                $rContext->setError('Указаны не все обязательные параметры (ИНН)');
                return false;
            }
        } elseif ($checktype=='snr' || $checktype=='sshr' || $checktype=='revexp' || $checktype=='paytax' || $checktype=='debtam' || $checktype=='taxoffence') {
            if(!isset($initData['inn'])){
                $rContext->setFinished();
//                $rContext->setError('Указаны не все обязательные параметры (ИНН)');
                return false;
            }
        } elseif ($checktype=='npd') {
            if(!isset($initData['inn'])){
                $rContext->setFinished();
//                $rContext->setError('Указаны не все обязательные параметры (ИНН)');
                return false;
            }
        } elseif ($checktype=='invalid') {
            if(!isset($initData['inn'])){
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

        if(($checktype=='inn' || $checktype=='mru' || $checktype=='disqualified') && isset($initData['last_name']) && isset($initData['first_name']) && preg_match("/[^А-Яа-яЁё\s\-\.]/ui", $initData['last_name'].' '.$initData['first_name'].(isset($initData['patronymic'])?' '.$initData['patronymic']:''))){
            $rContext->setFinished();
            $rContext->setError('Имя может содержать только русские буквы');
            return false;
        }
/*
        if($checktype=='inn'){
            $rContext->setFinished();
            $rContext->setError('Сервис временно недоступен');
        }
*/
        $ch = $rContext->getCurlHandler();

        if (($checktype=='inn' || $checktype=='bi' || $checktype=='rmsp' || $checktype=='npd') && !isset($swapData['url']) && !isset($swapData['session'])) {
            $id = array('inn'=>2,'bi'=>20,'rmsp'=>37,'npd'=>40);
            $swapData['session'] = $this->getSessionData($id[$checktype]);
            if(!$swapData['session']) {
                if (isset($swapData['iteration']) && ($swapData['iteration']>=30)) {
                    $rContext->setFinished();
                    $rContext->setError('Сервис временно недоступен');
                } else {
                    (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);
                }
                return false;
            } elseif ($checktype=='npd') {
                if (isset($swapData['iteration']) && $swapData['iteration']>10 && rand(0,2)) {
                $astro = array('213.108.196.179:10687');
//                    $swapData['session']->proxyid = 2;
//                    $swapData['session']->proxy = $astro[rand(0,sizeof($astro)-1)];
//                    $swapData['session']->proxy_auth = 'isphere:e6eac1'; 
                }
            }
        }
        $rContext->setSwapData($swapData);

        if ($checktype=='inn') {
//        if (!isset($initData['inn'])) {
            if (!isset($swapData['requestId'])) {
                $post = array(
                    'c' => 'find',
                    'fam' => $initData['last_name'],
                    'nam' => $initData['first_name'],
                    'otch' => isset($initData['patronymic'])?$initData['patronymic']:'',
                    'bdate' => isset($initData['date']) ? date('d.m.Y',strtotime($initData['date'])):'',
                    'doctype' => 21,
                    'docno' => $initData['passport_series'][0].$initData['passport_series'][1].' '.$initData['passport_series'][2].$initData['passport_series'][3]. ' ' . $initData['passport_number'],
                    'docdt' => isset($initData['issueDate']) ? date('d.m.Y',strtotime($initData['issueDate'])):'',
                    'captcha' => '', //$swapData['session']->code,
                    'captchaToken' => '', //$swapData['session']->token,
                );
                if (!isset($initData['patronymic']) || trim($initData['patronymic'])=='') {
                    $post['opt_otch'] = 1;
                }

                $url = 'https://service.nalog.ru/inn-new-proc.do';
            } else {
                $post = array(
                    'c' => 'get',
                    'requestId' => $swapData['requestId'],
                );

                $url = 'https://service.nalog.ru/inn-new-proc.json';
            }
            $ref = 'https://service.nalog.ru/inn.do';
            $header = array(
               'X-Requested-With: XMLHttpRequest',
            );

            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
            curl_setopt($ch, CURLOPT_REFERER, $ref);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        } elseif ($checktype=='bi' && !isset($swapData['url'])) {
//        } else {
            $post = array(
//                'c' => 'search',
                'requestType' => 'FINDPRS',
                'innPRS' => $initData['inn'],
                'bikPRS' => isset($initData['bik'])?$initData['bik']:'000000000',
                'fileName' => '',
                'bik' => '',
                'kodTU' => '',
                'dateSAFN' => '',
                'bikAFN' => '',
                'dateAFN' => '',
                'fileNameED' => '',
                'captcha' => '', //$swapData['session']->code,
                'captchaToken' => $swapData['session']->token,
            );

            $url = 'https://service.nalog.ru/bi2-proc.json';
            $ref = $url;

            curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
            curl_setopt($ch, CURLOPT_REFERER, $ref);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
//            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        } elseif ($checktype=='bi' && isset($swapData['url'])) {
            $url = $swapData['url'];
            curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
        } elseif ($checktype=='rmsp') {
            $post = array(
                'query' => $initData['inn'],
            );

            $url = 'https://rmsp.nalog.ru/search-proc.json';
            $ref = 'https://rmsp.nalog.ru/search.html';

//            curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
            curl_setopt($ch, CURLOPT_REFERER, $ref);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
//            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        } elseif ($checktype=='npd') {
            $post = array(
                'inn' => $initData['inn'],
                'requestDate' => date('Y-m-d',isset($initData['reqdate']) ? strtotime($initData['reqdate']) : time()),
            );

            $url = 'https://statusnpd.nalog.ru/api/v1/tracker/taxpayer_status';
            $ref = $url;

            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
        } elseif ($checktype=='invalid') {
            $post = array(
                'k' => 'fl',
                'inn' => $initData['inn'],
            );

            $url = 'https://service.nalog.ru/invalid-inn-proc.json';
            $ref = 'https://service.nalog.ru/invalid-inn-fl.html';

            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
//            curl_setopt($ch, CURLOPT_COOKIE, $swapData['session']->cookies);
            curl_setopt($ch, CURLOPT_REFERER, $ref);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
//            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        } else {
            $url = 'https://i-sphere.ru';
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        if (isset($swapData['session']) && $swapData['session']->proxy) {
            curl_setopt($ch,CURLOPT_PROXY,$swapData['session']->proxy);
            if ($swapData['session']->proxy_auth) {
                curl_setopt($ch,CURLOPT_PROXYUSERPWD,$swapData['session']->proxy_auth); 
                curl_setopt($ch,CURLOPT_PROXYAUTH,CURLAUTH_ANY); 
            }
        }
        $rContext->setCurlHandler($ch);

        return true;
    }

    public function computeRequest(&$rContext)
    {
        $error = false;

        global $mysqli;
        $initData = $rContext->getInitData();
        $checktype = substr($initData['checktype'],4);
        $last_name = isset($initData['last_name'])?$initData['last_name']:'';
        $first_name = isset($initData['first_name'])?$initData['first_name']:'';
        $middle_name = isset($initData['patronymic'])?$initData['patronymic']:'';
        $fio = trim($last_name.' '.$first_name.' '.$middle_name);
        $birth_date = isset($initData['date'])?date('d.m.Y',strtotime($initData['date'])):'';

        $swapData = $rContext->getSwapData();
        (!isset($swapData['iteration']))?$swapData['iteration']=1:$swapData['iteration']++;

        $rContext->setSwapData($swapData);
        $content = curl_multi_getcontent($rContext->getCurlHandler());

        if ($checktype=='inn') {
//        if (!isset($initData['inn'])) {
            if (!$content) {
                if ($swapData['iteration']>=5) {
                    $error = false; //curl_error($rContext->getCurlHandler());
//                    if (!$error) $error = "Сервис не отвечает";
                }
            }
//            file_put_contents('./logs/fns/'.$initData['checktype'].(isset($swapData['requestId'])?'_get':'').'_'.time().'.html',$content);
            $start = strpos($content,'{');
            $finish = strrpos($content,'}');
            if ($start!==false && $finish!==false) {
                $content = substr($content,$start,$finish-$start+1);
            }
            $res = json_decode($content,true);
            if (!isset($swapData['requestId']) && isset($res['requestId'])) {
                $swapData['requestId']=$res['requestId'];
                $swapData['iteration']--;
                $rContext->setSwapData($swapData);
            } elseif (isset($res['inn'])) {
                if (isset($swapData['session']))
                    $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=" . $swapData['session']->id);
//                    $mysqli->query("UPDATE isphere.session SET endtime=now(),statuscode='success',sessionstatusid=3 WHERE id=" . $swapData['session']->id);

                $swapData['inn'] = trim($res['inn']);
                $data['Result'] = new ResultDataField('string','Result', 'ИНН найден, номер паспорта соответствует ФИО и дате рождения', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string','ResultCode', 'FOUND', 'Код результата', 'Код результата');
                $data['INN'] = new ResultDataField('string','INN', $swapData['inn'], 'ИНН', 'ИНН');
                $data['Type'] = new ResultDataField('string','Type', 'inn', 'Тип записи', 'Тип записи');
                $swapData['data'] = $data;
//                $rContext->setSwapData($swapData);
                $resultData = new ResultDataList();
                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                return true;
            } elseif(isset($res['state'])){
                if(intval($res['state'])<0){
                    $swapData['iteration']--;
                    $rContext->setSwapData($swapData);
                    $rContext->setSleep(1);
                } else {
                    if (isset($swapData['session']))
                        $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=" . $swapData['session']->id);
//                        $mysqli->query("UPDATE isphere.session SET endtime=now(),statuscode='success',sessionstatusid=3 WHERE id=" . $swapData['session']->id);
                }

                if(intval($res['state'])==0){
                    $data['Result'] = new ResultDataField('string','Result', 'ИНН не найден или номер паспорта не соответствует ФИО и дате рождения', 'Результат', 'Результат');
                    $data['ResultCode'] = new ResultDataField('string','ResultCode', 'NOT_FOUND', 'Код результата', 'Код результата');
                    $data['Type'] = new ResultDataField('string','Type', 'inn', 'Тип записи', 'Тип записи');
                    $resultData = new ResultDataList();
                    $resultData->addResult($data);
                    $rContext->setResultData($resultData);
//                    $rContext->setError('Сервис временно недоступен');
                    $rContext->setFinished();
                    return true;
                } elseif(intval($res['state'])==3){
                    $data['Result'] = new ResultDataField('string','Result', 'Указанные сведения не прошли однозначной идентификации по Единому государственному реестру налогоплательщиков', 'Результат', 'Результат');
                    $data['ResultCode'] = new ResultDataField('string','ResultCode', 'FOUND_SEVERAL', 'Код результата', 'Код результата');
                    $data['Type'] = new ResultDataField('string','Type', 'inn', 'Тип записи', 'Тип записи');
                    $resultData = new ResultDataList();
                    $resultData->addResult($data);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    return true;
                } elseif (intval($res['state'])>0) {
                    file_put_contents('./logs/fns/'.$initData['checktype'].(isset($swapData['requestId'])?'_state':'').'_err_'.time().'.txt',$content);
                    if ($swapData['iteration']>=3) $error = "Не удалось выполнить поиск";
                }
            } else {
                if (isset($res['ERRORS'])) {
                    $content = '';
                    if (isset($res['ERRORS']['captcha']) || isset($res['ERRORS']['captchaToken']) ){
                        if (isset($swapData['session']))
                            $mysqli->query("UPDATE isphere.session SET endtime=now(),statuscode='invalidcaptcha',sessionstatusid=4 WHERE id=" . $swapData['session']->id);
                        unset($swapData['session']);
                        unset($swapData['requestId']);
                        $rContext->setSwapData($swapData);
                        $rContext->setSleep(3);
                        return true;
                    }
                    foreach($res['ERRORS'] as $field => $err ){
                        if ($content) $content .= '; ';
                        $content .= trim(stripslashes($err[0]));
                    }
                    $error = $content;
                } elseif (isset($res['ERROR'])) {
                    $content = trim(stripslashes($res['ERROR']));
                    if ($content=='Произошла внутренняя ошибка' || $content=='Недопустимый идентификатор запроса') {
                        if ($swapData['iteration']>5) $error = 'Внутренняя ошибка ФНС';
                        if (isset($swapData['session']))
                            $mysqli->query("UPDATE isphere.session SET endtime=now(),statuscode='internal',sessionstatusid=3 WHERE id=" . $swapData['session']->id);
                        unset($swapData['session']);
                        unset($swapData['requestId']);
                        $rContext->setSwapData($swapData);
                    } else {
                        $error = $content;
                    }
                } elseif (strlen($content)==0) {
                    if (!isset($swapData['requestId'])) {
                        if (isset($swapData['session']))
                            $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval ".($swapData['session']->proxyid<100?"30 second":"1 minute")."),sessionstatusid=6,statuscode='empty' WHERE id=".$swapData['session']->id);
                        unset($swapData['session']);
//                        unset($swapData['requestId']);
                        $rContext->setSwapData($swapData);
                    }
//                    return true;
                } elseif (strpos($content,'405 Not Allowed')) {
                    $rContext->setFinished();
                    $rContext->setError("Сервис выключен на стороне ФНС");
                    return false;
                } else {
                    file_put_contents('./logs/fns/'.$initData['checktype'].(isset($swapData['requestId'])?'_get':'').'_err_'.time().'.txt',$content);
                    if ($swapData['iteration']>=3) $error = "Некорректный ответ ФНС";
                }
            }
        } elseif ($checktype=='bi' && !isset($swapData['url'])) {
            if (!$content && $swapData['iteration']>=5) {
                $error = false; //curl_error($rContext->getCurlHandler());
            }
//            file_put_contents('./logs/fns/'.$initData['checktype'].'_'.time().'.html',$content);
            $start = strpos($content,'{');
            $finish = strrpos($content,'}');
            if ($start!==false && $finish!==false) {
                $content = substr($content,$start,$finish-$start+1);
            }
            $res = json_decode($content,true);

            if(isset($res['datePRS'])) {
                if (isset($swapData['session']))
                    $mysqli->query("UPDATE isphere.session SET endtime=now(),statuscode='success',sessionstatusid=3 WHERE id=" . $swapData['session']->id);

                $resultData = new ResultDataList();
                $found = isset($res['rows']) && sizeof($res['rows']);

                if ($found) {
                    foreach($res['rows'] as $row) {
                        $data = array();
                        $data['DecisionNumber'] = new ResultDataField('string','DecisionNumber', $row['NOMER'], 'Номер решения', 'Номер решения о приостановлении');
                        $data['DecisionDate'] = new ResultDataField('string','DecisionDate', $row['DATA'], 'Дата решения', 'Дата решения о приостановлении');
                        $data['DepartmentCode'] = new ResultDataField('string','DepartmentCode', $row['IFNS'], 'Код налогового органа', 'Код налогового органа');
                        $data['BIK'] = new ResultDataField('string','BIK', $bik=$row['BIK'], 'БИК', 'БИК банка');
                        $result = $mysqli->query("SELECT * FROM fns.bik WHERE bik='$bik'");
                        if ($result) {
                            if ($bank = $result->fetch_assoc()) {
                                $data['Bank'] = new ResultDataField('string','Bank', $bank['name'], 'Банк', 'Банк');
                                $data['City'] = new ResultDataField('string','City', $bank['city'], 'Город', 'Город');
                            }
                            $result->close();
                        }
                        $data['DateTime'] = new ResultDataField('string','DateTime', $row['DATABI'], 'Дата и время', 'Дата и время размещения информации');
                        $data['Type'] = new ResultDataField('string','Type', 'decision', 'Тип записи', 'Тип записи');
                        $resultData->addResult($data);
                    }
                }

                $data = array();
                $data['Result'] = new ResultDataField('string','Result', 'Действующие решения о приостановлении по указанному налогоплательщику '.($found?'ИМЕЮТСЯ':'ОТСУТСТВУЮТ'), 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string','ResultCode', $found?'FOUND':'NOT_FOUND', 'Код результата', 'Код результата');
                if ($found && isset($res['rows'][0]['INN'])) {
                    $data['INN'] = new ResultDataField('string','INN', $res['rows'][0]['INN'], 'ИНН налогоплательщика', 'ИНН налогоплательщика');
                }
                if($found && isset($res['rows'][0]['NAIM'])) {
                    $data['Name'] = new ResultDataField('string','Name', $res['rows'][0]['NAIM'], 'Наименование налогоплательщика', 'Наименование налогоплательщика');
                }
                $data['Type'] = new ResultDataField('string','Type', 'bi', 'Тип записи', 'Тип записи');

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
                    if (isset($res['ERRORS']['captcha']) || isset($res['ERRORS']['captchaToken']) ){
                        if (isset($swapData['session']))
                            $mysqli->query("UPDATE isphere.session SET endtime=now(),statuscode='invalidcaptcha',sessionstatusid=4 WHERE id=" . $swapData['session']->id);
                        unset($swapData['session']);
                        $rContext->setSwapData($swapData);
                        $rContext->setSleep(3);
                        return true;
                    }
                    foreach($res['ERRORS'] as $field => $err ){
                        if ($content) $content .= '; ';
                        $content .= stripslashes($err[0]);
                    }
                    $rContext->setFinished();
                    $rContext->setError(trim($content));
                } elseif (isset($res['ERROR'])) {
                    $error = stripslashes($res['ERROR']);
                } elseif (strlen($content)==0) {
                    if (isset($swapData['session']))
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),statuscode='empty',sessionstatusid=3 WHERE id=" . $swapData['session']->id);
                    unset($swapData['session']);
                    $rContext->setSwapData($swapData);
//                    return true;
                } elseif (strpos($content,'405 Not Allowed')) {
                    $rContext->setFinished();
                    $rContext->setError("Сервис выключен на стороне ФНС");
                    return false;
                } else {
                    file_put_contents('./logs/fns/'.$initData['checktype'].'_err_'.time().'.txt',$content);
                    if ($swapData['iteration']>=3) $error = "Некорректный ответ ФНС";
                }
            }
        } elseif ($checktype=='bi' && isset($swapData['url'])) {
            if (!$content) {
                $error = ($swapData['iteration']>=5) && false; //curl_error($rContext->getCurlHandler());
            } elseif (strlen($content)<30000 || preg_match("/<html>/",$content)) {
                if ($swapData['iteration']>=5) {
                    $resultData = $swapData['result'];
                    $data = $swapData['data'];
                    $resultData->addResult($data);
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
                    return true;
                }
            } else {
                global $serviceurl;
                global $reqId;

                $name = 'bi_'.$initData['inn'].'_'.time().'.zip';
                $file = './logs/files/'.$reqId.'_bi_'.$initData['inn'].'_'.time().'.zip';
                file_put_contents($file,$content);
//                $url = $serviceurl.'logs/files/'.$file;
                $url = $serviceurl.'getfile.php?id='.$reqId.'&name='.$name;
                $resultData = $swapData['result'];
                $data = $swapData['data'];
                $data['pdf'] = new ResultDataField('url', 'PDF', $url, 'PDF', 'PDF');

                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                return true;
            }
        } elseif ($checktype=='rmsp'/* && !isset($swapData['url'])*/) {
            if (!$content && $swapData['iteration']>=5) {
                $error = false; //curl_error($rContext->getCurlHandler());
            }
            file_put_contents('./logs/fns/'.$initData['checktype'].'_'.time().'.html',$content);
            $start = strpos($content,'{');
            $finish = strrpos($content,'}');
            if ($start!==false && $finish!==false) {
                $content = substr($content,$start,$finish-$start+1);
            }
            $res = json_decode($content,true);

            if(isset($res['data'])) {
                if (isset($swapData['session']))
                    $mysqli->query("UPDATE isphere.session SET statuscode='success' WHERE id=" . $swapData['session']->id);

                $resultData = new ResultDataList();
                $found = sizeof($res['data']);
/*
<script type="text/javascript">
    var RSMP_REGION = {"77":"г.Москва","78":"г.Санкт-Петербург","01":"Республика Адыгея (Адыгея)","02":"Республика Башкортостан","03":"Республика Бурятия","04":"Республика Алтай","05":"Республика Дагестан","06":"Республика Ингушетия","07":"Кабардино-Балкарская Республика","08":"Республика Калмыкия","09":"Карачаево-Черкесская Республика","10":"Республика Карелия","11":"Республика Коми","12":"Республика Марий Эл","13":"Республика Мордовия","14":"Республика Саха (Якутия)","15":"Республика Северная Осетия - Алания","16":"Республика Татарстан (Татарстан)","17":"Республика Тыва","18":"Удмуртская Республика","19":"Республика Хакасия","20":"Чеченская Республика","21":"Чувашская Республика - Чувашия","22":"Алтайский край","23":"Краснодарский край","24":"Красноярский край","25":"Приморский край","26":"Ставропольский край","27":"Хабаровский край","28":"Амурская область","29":"Архангельская область","30":"Астраханская область","31":"Белгородская область","32":"Брянская область","33":"Владимирская область","34":"Волгоградская область","35":"Вологодская область","36":"Воронежская область","37":"Ивановская область","38":"Иркутская область","39":"Калининградская область","40":"Калужская область","41":"Камчатский край","42":"Кемеровская область","43":"Кировская область","44":"Костромская область","45":"Курганская область","46":"Курская область","47":"Ленинградская область","48":"Липецкая область","49":"Магаданская область","50":"Московская область","51":"Мурманская область","52":"Нижегородская область","53":"Новгородская область","54":"Новосибирская область","55":"Омская область","56":"Оренбургская область","57":"Орловская область","58":"Пензенская область","59":"Пермский край","60":"Псковская область","61":"Ростовская область","62":"Рязанская область","63":"Самарская область","64":"Саратовская область","65":"Сахалинская область","66":"Свердловская область","67":"Смоленская область","68":"Тамбовская область","69":"Тверская область","70":"Томская область","71":"Тульская область","72":"Тюменская область","73":"Ульяновская область","74":"Челябинская область","75":"Забайкальский край","76":"Ярославская область","79":"Еврейская автономная область","83":"Ненецкий автономный округ","86":"Ханты-Мансийский автономный округ - Югра","87":"Чукотский автономный округ","89":"Ямало-Ненецкий автономный округ","90":"Запорожская область","91":"Республика Крым","92":"г.Севастополь","93":"Донецкая Народная Республика","94":"Луганская Народная Республика","95":"Херсонская область","99":"Иные территории, включая город и космодром Байконур"};
        if (row['is_pp']) html.push('<div class="result-support"><a class="lnk lnk-support" target="_blank" href="https://rmsp-pp.nalog.ru/subject.html?id=' + row['inn'] + '&id2=' + (row['ogrn'] || '') + '" title="Просмотреть сведения о предоставленной поддержке">Получатель поддержки</a></div>');
        html.push('<div class="result-periods"><a class="lnk lnk-calendar" target="_blank" href="periods.pdf?token=' + row['token'] + '" title="Выписка по категориям и периодам нахождения в реестре">Периоды нахождения в реестре</a></div>');
</script>
*/
                if ($found) {
                    $category = ["Не является субъектом МСП","Микропредприятие","Малое предприятие","Среднее предприятие"];
                    $bool = ["Нет","Да"];
                    $nptype = ["UL"=>"Юридическое лицо","IP"=>"Индивидуальный предприниматель"];
                    foreach($res['data'] as $row) {
                        $data = array();
                        if (isset($row['inn']))
                            $data['inn'] = new ResultDataField('string','INN', $row['inn'], 'ИНН', 'ИНН');
                        if (isset($row['ogrn']))
                            $data['ogrn'] = new ResultDataField('string','OGRN', $row['ogrn'], 'ОГРН', 'ОГРН');
                        if (isset($row['name_ex']))
                            $data['name_ex'] = new ResultDataField('string','Name', $row['name_ex'], 'Наименование', 'Наименование');
                        if (isset($row['is_active']) && isset($bool[$row['is_active']]))
                            $data['is_active'] = new ResultDataField('string','Active', $bool[$row['is_active']], 'Числится в реестре', 'Числится в реестре');
                        if (isset($row['nptype']) && isset($nptype[$row['nptype']]))
                            $data['nptype'] = new ResultDataField('string','NPType', $nptype[$row['nptype']], 'Тип налогоплательщика', 'Тип налогоплательщика');
                        if (isset($row['category']) && isset($category[$row['category']]))
                            $data['category'] = new ResultDataField('string','Category', $category[$row['category']], 'Категория', 'Категория');
                        if (isset($row['regioncode']))
                            $data['regioncode'] = new ResultDataField('string','RegionCode', $row['regioncode'], 'Код региона', 'Код региона');
                        if (isset($row['cityname']))
                            $data['cityname'] = new ResultDataField('string','City', $row['cityname'], 'Город', 'Город');
                        if (isset($row['areaname']))
                            $data['areaname'] = new ResultDataField('string','Area', $row['areaname'], 'Район', 'Район');
                        if (isset($row['localityname']))
                            $data['localityname'] = new ResultDataField('string','Locality', $row['localityname'], 'Населенный пункт', 'Населенный пункт');
                        if (isset($row['dtregistry']))
                            $data['dtregistry'] = new ResultDataField('string','StartDate', substr($row['dtregistry'],0,10), 'Дата включения в реестр', 'Дата включения в реестр');
                        if (isset($row['dtregistryout']))
                            $data['dtregistryout'] = new ResultDataField('string','EndDate', substr($row['dtregistryout'],0,10), 'Дата исключения из реестра', 'Дата исключения из реестра');
                        if (isset($row['okved1']))
                            $data['okved1'] = new ResultDataField('string','OKVED', $row['okved1'], 'Основной код ОКВЭД', 'Основной код ОКВЭД');
                        if (isset($row['okved1name']))
                            $data['okved1name'] = new ResultDataField('string','OKVEDName', $row['okved1name'], 'Основной вид деятельности', 'Основной вид деятельности');
                        if (isset($row['od2_sschr']))
                            $data['od2_sschr'] = new ResultDataField('string','Employees', $row['od2_sschr'], 'Среднесписочная численность работников', 'Среднесписочная численность работников');
                        if (isset($row['phone']))
                            $data['phone'] = new ResultDataField('phone','Phone', $row['phone'], 'Телефон', 'Телефон');
                        if (isset($row['email']))
                            $data['email'] = new ResultDataField('email','Email', $row['email'], 'E-mail', 'E-mail');
                        if (isset($row['www']))
                            $data['www'] = new ResultDataField('url','Site', $row['www'], 'Сайт', 'Сайт');
                        if (isset($row['isnew']) && isset($bool[$row['isnew']]))
                            $data['isnew'] = new ResultDataField('string','New', $bool[$row['isnew']], 'Вновь созданный', 'Вновь созданный');
                        if (isset($row['has_licenses']) && isset($bool[$row['has_licenses']]))
                            $data['has_licenses'] = new ResultDataField('string','HasLicenses', $bool[$row['has_licenses']], 'Наличие лицензий', 'Наличие лицензий');
                        if (isset($row['has_contracts']) && isset($bool[$row['has_contracts']]))
                            $data['has_contracts'] = new ResultDataField('string','HasContracts', $bool[$row['has_contracts']], 'Наличие заключенных договоров', 'Наличие заключенных договоров');
                        if (isset($row['is_hitech']) && isset($bool[$row['is_hitech']]))
                            $data['is_hitech'] = new ResultDataField('string','HiTech', $bool[$row['is_hitech']], 'Высокотехнологичная продукция', 'Высокотехнологичная продукция');
                        if (isset($row['is_partnership']) && isset($bool[$row['is_partnership']]))
                            $data['is_partnership'] = new ResultDataField('string','Partnership', $bool[$row['is_partnership']], 'Участие в программах партнерства', 'Участие в программах партнерства');
                        if (isset($row['pr_soc']) && isset($bool[$row['pr_soc']]))
                            $data['pr_soc'] = new ResultDataField('string','Socail', $bool[$row['pr_soc']], 'Социальное предприятие', 'Социальное предприятие');
                        if (isset($row['is_pp']) && isset($bool[$row['is_pp']]))
                            $data['is_pp'] = new ResultDataField('string','Support', $bool[$row['is_pp']], 'Получатель поддержки', 'Получатель поддержки');
                        if (isset($row['token']))
                            $data['pdf'] = new ResultDataField('url','PDF', 'https://rmsp.nalog.ru/excerpt.pdf?token='.$row['token'], 'Выписка PDF', 'Выписка PDF');
                        if (sizeof($data)) {
                            $data['Type'] = new ResultDataField('string','Type', 'rmsp', 'Тип записи', 'Тип записи');
                            $resultData->addResult($data);
                        }
                    }
                }

//                if (isset($res['data'][0]['token'])) {
//                    $swapData['url'] = 'https://rmsp.nalog.ru/excerpt.pdf?token='.$res['data'][0]['token'];
//                    $swapData['result'] = $resultData;
//                    $swapData['data'] = $data;
//                    $rContext->setSwapData($swapData);
//                } else {
                    $rContext->setResultData($resultData);
                    $rContext->setFinished();
//                }
                return true;
            } else {
                if (isset($res['ERRORS'])) {
                    $content = '';
                    foreach($res['ERRORS'] as $field => $err ){
                        if ($content) $content .= '; ';
                        $content .= stripslashes($err[0]);
                    }
                    $rContext->setFinished();
                    $rContext->setError(trim($content));
                } elseif (isset($res['ERROR'])) {
                    $error = stripslashes($res['ERROR']);
                } elseif (strlen($content)==0) {
                    if (isset($swapData['session']))
                        $mysqli->query("UPDATE isphere.session SET endtime=now(),statuscode='empty',sessionstatusid=3 WHERE id=" . $swapData['session']->id);
                    unset($swapData['session']);
                    $rContext->setSwapData($swapData);
//                    return true;
                } elseif (strpos($content,'405 Not Allowed')) {
                    $rContext->setFinished();
                    $rContext->setError("Сервис выключен на стороне ФНС");
                    return false;
                } else {
                    file_put_contents('./logs/fns/'.$initData['checktype'].'_err_'.time().'.txt',$content);
                    if ($swapData['iteration']>=3) $error = "Некорректный ответ ФНС";
                }
            }
        } elseif ($checktype=='zd') {
            $resultData = new ResultDataList();

            $result = $mysqli->query("SELECT inn,ogrn,title FROM fns.zd_debt WHERE inn='".$initData['inn']."'");
            if ($result) {
                $data = array();
                $data['Result'] = new ResultDataField('string','Result', ($result->num_rows==0?'ОТСУТСТВУЕТ':'ЧИСЛИТСЯ').' в реестре организаций, имеющих судебную задолженность по налогам свыше 1000 рублей', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string','ResultCode', $result->num_rows==0?'NOT_FOUND':'FOUND', 'Код результата', 'Код результата');
                if( $row = $result->fetch_assoc()){
                    foreach( $row as $key => $val ){
                        $type = isset($this->titles[$key][2])?$this->titles[$key][2]:'string';
                        if ($val)
                            $data[$key] = new ResultDataField($type,$key,$val,$this->titles[$key][0],$this->titles[$key][1]);
                    }
                }
                $data['Type'] = new ResultDataField('string','Type', 'zd_debt', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);
            }
            $result->close();

            $result = $mysqli->query("SELECT inn,ogrn,title FROM fns.zd_report WHERE inn='".$initData['inn']."'");
            if ($result) {
                $data = array();
                $data['Result'] = new ResultDataField('string','Result', ($result->num_rows==0?'ОТСУТСТВУЕТ':'ЧИСЛИТСЯ').' в реестре организаций, не предоставляющих отчетность более года', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string','ResultCode', $result->num_rows==0?'NOT_FOUND':'FOUND', 'Код результата', 'Код результата');
                if( $row = $result->fetch_assoc()){
                    foreach( $row as $key => $val ){
                        $type = isset($this->titles[$key][2])?$this->titles[$key][2]:'string';
                        if ($val)
                            $data[$key] = new ResultDataField($type,$key,$val,$this->titles[$key][0],$this->titles[$key][1]);
                    }
                }
                $data['Type'] = new ResultDataField('string','Type', 'zd_report', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);
                $result->close();
            }

            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return true;
        } elseif ($checktype=='snr') {
            $resultData = new ResultDataList();

            $result = $mysqli->query("SELECT inn,date,usn,eshn,envd,srp FROM fns.snr WHERE inn='".$initData['inn']."'");
            if ($result) {
                $data = array();
                if( $row = $result->fetch_assoc()){
                    foreach( $row as $key => $val ){
                        $type = isset($this->titles[$key][2])?$this->titles[$key][2]:'string';
//                        if ($val)
                            $data[$key] = new ResultDataField($type,$key,$val,$this->titles[$key][0],$this->titles[$key][1]);
                    }
                    $data['Type'] = new ResultDataField('string','Type', 'snr', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                }
                $result->close();
            }

            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return true;
        } elseif ($checktype=='sshr') {
            $resultData = new ResultDataList();

            $result = $mysqli->query("SELECT inn,date,quant FROM fns.sshr WHERE inn='".$initData['inn']."'");
            if ($result) {
                $data = array();
                if( $row = $result->fetch_assoc()){
                    foreach( $row as $key => $val ){
                        $type = isset($this->titles[$key][2])?$this->titles[$key][2]:'string';
//                        if ($val)
                            $data[$key] = new ResultDataField($type,$key,$val,$this->titles[$key][0],$this->titles[$key][1]);
                    }
                    $data['Type'] = new ResultDataField('string','Type', 'sshr', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                }
            }
            $result->close();

            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return true;
        } elseif ($checktype=='revexp') {
            $resultData = new ResultDataList();

            $result = $mysqli->query("SELECT inn,dataState date,sumIncome,sumExpense FROM fns.revexp WHERE inn='".$initData['inn']."'");
            if ($result) {
                $data = array();
                if( $row = $result->fetch_assoc()){
                    foreach( $row as $key => $val ){
                        $type = isset($this->titles[$key][2])?$this->titles[$key][2]:'string';
//                        if ($val)
                            $data[$key] = new ResultDataField($type,$key,$val,$this->titles[$key][0],$this->titles[$key][1]);
                    }
                    $data['Type'] = new ResultDataField('string','Type', 'revexp', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                }
                $result->close();
            }

            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return true;
        } elseif ($checktype=='paytax') {
            $resultData = new ResultDataList();

            $result = $mysqli->query("SELECT inn,dataState date,Nalog json FROM fns.paytax WHERE inn='".$initData['inn']."'");
            if ($result) {
                $data = array();
                if( $row = $result->fetch_assoc()){
                    foreach( $row as $key => $val ){
                        if ($key=='json') {
                            $res = json_decode($val, true);
                            foreach( $res as $nalog => $sum ){
//                                echo $nalog.": ".$sum."\n";
                                if (isset($this->nalog_fields[$nalog])) {
                                    $field = $this->nalog_fields[$nalog];
                                    $data[$field[0]] = new ResultDataField('string',$field[0],$sum,isset($field[1])?$field[1]:$nalog,isset($field[2])?$field[2]:$nalog);
                                }
                            }
                        } else {
                            $type = isset($this->titles[$key][2])?$this->titles[$key][2]:'string';
//                            if ($val)
                                $data[$key] = new ResultDataField($type,$key,$val,$this->titles[$key][0],$this->titles[$key][1]);
                        }
                    }
                    $data['Type'] = new ResultDataField('string','Type', 'paytax', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                }
                $result->close();
            }

            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return true;
        } elseif ($checktype=='debtam') {
            $resultData = new ResultDataList();

            $result = $mysqli->query("SELECT inn,dataState date,Nalog json FROM fns.debtam WHERE inn='".$initData['inn']."'");
            if ($result) {
                $data = array();
                if( $row = $result->fetch_assoc()){
                    foreach( $row as $key => $val ){
                        if ($key=='json') {
                            $res = json_decode($val, true);
                            foreach( $res as $debt ){
                                $nalog = $debt['НаимНалог'];
                                $sum = $debt['СумНедНалог'];
                                $penalty = $debt['СумПени'];
                                $fine = $debt['СумШтраф'];
                                $total = $debt['ОбщСумНедоим'];
//                                echo $nalog.": ".$sum."\n";
                                if (isset($this->nalog_fields[$nalog])) {
                                    $field = $this->nalog_fields[$nalog];
                                    $data[$field[0]] = new ResultDataField('string',$field[0],$sum,isset($field[1])?$field[1]:$nalog,isset($field[2])?$field[2]:$nalog);
                                    $data[$field[0].'_penalty'] = new ResultDataField('float',$field[0].'_penalty',$penalty,(isset($field[1])?$field[1]:$nalog).' (пени)',(isset($field[2])?$field[2]:$nalog).' (пени)');
                                    $data[$field[0].'_fine'] = new ResultDataField('float',$field[0].'_fine',$fine,(isset($field[1])?$field[1]:$nalog).' (штраф)',(isset($field[2])?$field[2]:$nalog).' (штраф)');
                                    $data[$field[0].'_total'] = new ResultDataField('float',$field[0].'_total',$total,(isset($field[1])?$field[1]:$nalog).' (итого)',(isset($field[2])?$field[2]:$nalog).' (итого)');
                                }
                            }
                        } else {
                            $type = isset($this->titles[$key][2])?$this->titles[$key][2]:'string';
//                            if ($val)
                                $data[$key] = new ResultDataField($type,$key,$val,$this->titles[$key][0],$this->titles[$key][1]);
                        }
                    }
                    $data['Type'] = new ResultDataField('string','Type', 'debtam', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                }
                $result->close();
            }

            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return true;
        } elseif ($checktype=='taxoffence') {
            $resultData = new ResultDataList();

            $result = $mysqli->query("SELECT inn,dataState date,Penalty FROM fns.taxoffence WHERE inn='".$initData['inn']."'");
            if ($result) {
                $data = array();
                if( $row = $result->fetch_assoc()){
                    foreach( $row as $key => $val ){
                        $type = isset($this->titles[$key][2])?$this->titles[$key][2]:'string';
//                        if ($val)
                            $data[$key] = new ResultDataField($type,$key,$val,$this->titles[$key][0],$this->titles[$key][1]);
                    }
                    $data['Type'] = new ResultDataField('string','Type', 'taxoffence', 'Тип записи', 'Тип записи');
                    $resultData->addResult($data);
                }
                $result->close();
            }

            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return true;
        } elseif ($checktype=='disfind') {
            $resultData = new ResultDataList();

            $result = $mysqli->query("SELECT inn,kpp,ogrn,orgName,address FROM fns.disfind WHERE inn='".$initData['inn']."'");
            if ($result) {
                $data = array();
                $data['Result'] = new ResultDataField('string','Result', ($result->num_rows==0?'ОТСУТСТВУЕТ':'ЧИСЛИТСЯ').' в реестре организаций с дисквалифицированными лицами в составе исполнительного органа', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string','ResultCode', $result->num_rows==0?'NOT_FOUND':'FOUND', 'Код результата', 'Код результата');
                if( $row = $result->fetch_assoc()){
                    foreach( $row as $key => $val ){
                        $type = isset($this->titles[$key][2])?$this->titles[$key][2]:'string';
                        if ($val)
                            $data[$key] = new ResultDataField($type,$key,$val,$this->titles[$key][0],$this->titles[$key][1]);
                    }
                }
                $data['Type'] = new ResultDataField('string','Type', 'disfind', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);
                $result->close();
            }

            $result = $mysqli->query("SELECT fio,birthDate,birthPlace,orgName,orgInn,post,term,startDate,endDate,article,authName,judgePost,judgeName FROM fns.disqualified WHERE orgInn='".$initData['inn']."'");
            if ($result) {
                $data = array();
                $data['Result'] = new ResultDataField('string','Result', ($result->num_rows==0?'ОТСУТСТВУЕТ':'ЧИСЛИТСЯ').' в реестре дисквалифицированных лиц', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string','ResultCode', $result->num_rows==0?'NOT_FOUND':'FOUND', 'Код результата', 'Код результата');
                if( $row = $result->fetch_assoc()){
                    foreach( $row as $key => $val ){
                        $type = isset($this->titles[$key][2])?$this->titles[$key][2]:'string';
                        if ($val)
                            $data[$key] = new ResultDataField($type,$key,$val,$this->titles[$key][0],$this->titles[$key][1]);
                    }
                }
                $data['Type'] = new ResultDataField('string','Type', 'disqualified', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);
            }
            $result->close();

            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return true;
        } elseif ($checktype=='disqualified') {
            $resultData = new ResultDataList();

            $result = $mysqli->query("SELECT fio,birthDate,birthPlace,orgName,orgInn,post,term,startDate,endDate,article,authName,judgePost,judgeName FROM fns.disqualified WHERE fio='$fio'".($birth_date?" AND birthDate='$birth_date'":""));
            if ($result) {
                $data = array();
                $data['Result'] = new ResultDataField('string','Result', ($result->num_rows==0?'ОТСУТСТВУЕТ':'ЧИСЛИТСЯ').' в реестре дисквалифицированных лиц', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string','ResultCode', $result->num_rows==0?'NOT_FOUND':'FOUND', 'Код результата', 'Код результата');
                if( $row = $result->fetch_assoc()){
                    foreach( $row as $key => $val ){
                        $type = isset($this->titles[$key][2])?$this->titles[$key][2]:'string';
                        if ($val)
                            $data[$key] = new ResultDataField($type,$key,$val,$this->titles[$key][0],$this->titles[$key][1]);
                    }
                }
                $data['Type'] = new ResultDataField('string','Type', 'disqualified', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);
                $result->close();
            }

            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return true;
        } elseif ($checktype=='mru') {
            $resultData = new ResultDataList();

            $result = $mysqli->query("SELECT inn,lastname,firstname,patronymic,quantity FROM fns.mru_ruk WHERE inn='".$initData['inn']."'");
//            $result = $mysqli->query("SELECT inn,lastname,firstname,patronymic,quantity FROM fns.mru_ruk WHERE ".(isset($initData['inn'])?"inn='".$initData['inn']."'".($last_name?" OR ":""):"").($last_name?"(lastname='$last_name' AND firstname='$first_name' AND patronymic='$middle_name')":""));
            if ($result) {
                $data = array();
                $data['Result'] = new ResultDataField('string','Result', ($result->num_rows==0?'ОТСУТСТВУЕТ':'ЧИСЛИТСЯ').' в реестре массовых руководителей', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string','ResultCode', $result->num_rows==0?'NOT_FOUND':'FOUND', 'Код результата', 'Код результата');
                if( $row = $result->fetch_assoc()){
                    foreach( $row as $key => $val ){
                        $type = isset($this->titles[$key][2])?$this->titles[$key][2]:'string';
                        if ($val)
                            $data[$key] = new ResultDataField($type,$key,$val,$this->titles[$key][0],$this->titles[$key][1]);
                    }
                }
                $data['Type'] = new ResultDataField('string','Type', 'mru_ruk', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);
                $result->close();
            }

//            $result = $mysqli->query("SELECT inn,lastname,firstname,patronymic,quantity FROM fns.mru_uchr WHERE ".(isset($initData['inn'])?"inn='".$initData['inn']."'".($last_name?" OR ":""):"").($last_name?"(lastname='$last_name' AND firstname='$first_name' AND patronymic='$middle_name')":""));
            $result = $mysqli->query("SELECT inn,lastname,firstname,patronymic,quantity FROM fns.mru_uchr WHERE inn='".$initData['inn']."'");
            if ($result) {
                $data = array();
                $data['Result'] = new ResultDataField('string','Result', ($result->num_rows==0?'ОТСУТСТВУЕТ':'ЧИСЛИТСЯ').' в реестре массовых учредителей', 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string','ResultCode', $result->num_rows==0?'NOT_FOUND':'FOUND', 'Код результата', 'Код результата');
                if( $row = $result->fetch_assoc()){
                    foreach( $row as $key => $val ){
                        $type = isset($this->titles[$key][2])?$this->titles[$key][2]:'string';
                        if ($val)
                            $data[$key] = new ResultDataField($type,$key,$val,$this->titles[$key][0],$this->titles[$key][1]);
                    }
                }
                $data['Type'] = new ResultDataField('string','Type', 'mru_uchr', 'Тип записи', 'Тип записи');
                $resultData->addResult($data);
                $result->close();
            }

            $rContext->setResultData($resultData);
            $rContext->setFinished();
            return true;
        } elseif ($checktype=='npd') {
            if (!$content) {
                if ($swapData['iteration']>=5)
                    $error = false; //curl_error($rContext->getCurlHandler());
            }
//            file_put_contents('./logs/fns/'.$initData['checktype'].'_'.time().'.txt',$content);
            $start = strpos($content,'{');
            $finish = strrpos($content,'}');
            if ($start!==false && $finish!==false) {
                $content = substr($content,$start,$finish-$start+1);
            }
            $res = json_decode($content,true);

            if(isset($res['status'])) {
                $resultData = new ResultDataList();

                $data = array();
                $data['Result'] = new ResultDataField('string','Result', $res['message'], 'Результат', 'Результат');
                $data['ResultCode'] = new ResultDataField('string','ResultCode', $res['status']?'FOUND':'NOT_FOUND', 'Код результата', 'Код результата');
                $data['Type'] = new ResultDataField('string','Type', 'npd', 'Тип записи', 'Тип записи');

                $resultData->addResult($data);
                $rContext->setResultData($resultData);
                $rContext->setFinished();

                $mysqli->query("UPDATE isphere.session SET success=ifnull(success,0)+1,statuscode='success' WHERE id=" . $swapData['session']->id);
                return true;
            } else {
                if ($res && isset($res['message']) && strpos($res['message'],'Временно')!==false) {
                    $error = 'Сервис временно недоступен';
                } elseif ($res && isset($res['message']) && strpos($res['message'],'Превышено')!==false) {
                    $swapData['iteration']--;
//                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval 1 minute),sessionstatusid=6,statuscode='exhausted' WHERE id=" . $swapData['session']->id);
                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval ".($swapData['session']->proxyid<100?"30 second":"10 minute")."),sessionstatusid=6,statuscode='limit' WHERE id=" . $swapData['session']->id);
                    unset($swapData['session']);
                } elseif (!$content) {
                    file_put_contents('./logs/fns/'.$initData['checktype'].'_empty_'.time().'.txt',$content);
//                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval ".($swapData['session']->proxyid<100?"15 second":"10 minute")."),sessionstatusid=6,statuscode='empty' WHERE sourceid=40 AND proxyid=" . $swapData['session']->proxyid . " ORDER BY lasttime DESC LIMIT 10");
                    $mysqli->query("UPDATE isphere.session SET unlocktime=date_add(now(),interval ".($swapData['session']->proxyid<100?"30 second":"10 minute")."),sessionstatusid=6,statuscode='empty' WHERE id=" . $swapData['session']->id);
                    unset($swapData['session']);
                } else {
                    file_put_contents('./logs/fns/'.$initData['checktype'].'_err_'.time().'.txt',$content);
                    if ($swapData['iteration']>=3) $error = ($res && isset($res['message']))?preg_replace("/:\s[\d]+$/","",$res['message']):"Некорректный ответ ФНС";
                    unset($swapData['session']);
                }
            }
        } elseif ($checktype=='invalid') {
            if (!$content) {
                if ($swapData['iteration']>=5)
                    $error = false; //curl_error($rContext->getCurlHandler());
            }
//            file_put_contents('./logs/fns/'.$initData['checktype'].'_'.time().'.txt',$content);

            $start = strpos($content,'{');
            $finish = strrpos($content,'}');
            if ($start!==false && $finish!==false) {
                $content = substr($content,$start,$finish-$start+1);
            }
            $res = json_decode($content,true);

            if(isset($res['inn'])) {
                $resultData = new ResultDataList();

                if (isset($res['date'])) {
                    $data = array();
                    $data['INN'] = new ResultDataField('string','INN', $res['inn'], 'ИНН', 'ИНН');
                    $data['Result'] = new ResultDataField('string','Result', 'ИНН недействителен', 'Результат', 'Результат');
                    $data['ResultCode'] = new ResultDataField('string','ResultCode', 'NOT_VALID', 'Код результата', 'Код результата');
                    $data['DateTime'] = new ResultDataField('string','DateTime', substr($res['date'],0,10), 'Дата признания недействительным', 'Дата признания недействительным');
                    $data['Type'] = new ResultDataField('string','Type', 'invalid', 'Тип записи', 'Тип записи');

                    $resultData->addResult($data);
                }
                $rContext->setResultData($resultData);
                $rContext->setFinished();
                return true;
            } elseif (isset($res['ERROR'])) {
                if ($res['ERROR']=='Произошла внутренняя ошибка') {
                    $error = 'Внутренняя ошибка ФНС';
                } elseif ($swapData['iteration']>=5) {
                    $error = $res['ERROR'];
                } else
                    $rContext->setSleep(3);
            } elseif ($content) {
                file_put_contents('./logs/fns/'.$initData['checktype'].'_err_'.time().'.txt',$content);
                if (strpos($content,'405 Not Allowed')) $error = "Сервис выключен на стороне ФНС";
                if ($swapData['iteration']>=3) $error = "Некорректный ответ ФНС";
            }
        } else {
            $error = 'Неизвестный метод проверки';
        }

        if($error || $swapData['iteration']>=(/*$checktype!='npd'?5:*/20)) {
            $rContext->setFinished();
            $rContext->setError(!$error?'Превышено количество попыток получения ответа':$error);
            return false;
        }

        $rContext->setSwapData($swapData);
        $rContext->setSleep(1);
        return true;
    }
}

?>
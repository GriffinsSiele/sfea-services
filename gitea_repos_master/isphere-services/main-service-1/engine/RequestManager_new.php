<?php

function normal_phone($phone)
{
    $plus = substr(trim($phone),0,1)=='+';
    $phone = preg_replace("/\D/","",trim($phone));
    if (!$plus && (strlen($phone)==11) && (substr($phone,0,1)=='8')) $phone = '7'.substr($phone,1);
    if (!$plus && (strlen($phone)==10)) $phone = '7'.$phone;
    return strlen($phone)>=11 && strlen($phone)<=14? $phone : '';  
}

function normal_email($email)
{
    $email = trim(strtr(mb_strtolower($email),array('~'=>'','№'=>'','!'=>'','#'=>'','$'=>'','%'=>'','^'=>'','&'=>'','*'=>'','('=>'',')'=>'','['=>'',']'=>'','{'=>'','}'=>'','+'=>'','='=>'','"'=>'',"'"=>'','`'=>'','<'=>'','>'=>'','/'=>'','|'=>'','\\'=>'',','=>'',';'=>'',':'=>'','?'=>'',' '=>'','​'=>'','а'=>'a','в'=>'b','с'=>'c','е'=>'e','н'=>'h','к'=>'k','м'=>'m','о'=>'o','п'=>'n','р'=>'p','т'=>'t','у'=>'y','х'=>'x')));
    return preg_match("/^[0-9a-z][0-9a-z\-\._]*\@[0-9a-z][0-9a-z\-\._]+\.[a-z]{2,20}$/",$email) ? $email : '';  
}

function normal_login($login)
{
    $login = trim($login);
    return preg_match("/^[A-Za-z0-9\-\.\_\@\$\:]+$/",$login) ? $login : '';  
}

function normal_ip($ip)
{
    $ip = preg_replace("/[0-9\.]/","",trim($ip));
    return preg_match("/^(25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[0-9]{2}|[0-9])(\.(25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[0-9]{2}|[0-9])){3}$/",$ip) ? $ip : '';  
}

function normal_inn($inn)
{
    $inn = preg_replace("/\D/","",trim($inn));
    if (strlen($inn)==9 || strlen($inn)==11) $inn = '0'.$inn;
    return intval($inn) && (strlen($inn)==10 || strlen($inn)==12) ? $inn : '';  
}

function normal_snils($snils)
{
    $snils = preg_replace("/\D/","",trim($snils));
    return intval($snils) && (strlen($snils)==11) ? $snils : '';  
}

class RequestManager
{
    public $queryid;

    var $mh;
    var $requestContextPool;
    var $finishedResults;

    var $totalTimeout;

    public function __construct($totalTimeout = 60)
    {
        $this->mh = curl_multi_init();
        $this->totalTimeout = $totalTimeout;

        $this->finishedResults = array();
        $this->requestContextPool = array();
    }
    function __destruct()
    {
        curl_multi_close($this->mh);
    }

    public function initRequestContexts($params,$plugins,$level,$path=false,$start=false)
    {
        global $http_connecttimeout, $http_timeout, $http_agent;
        global $user_sources, $checktypes;

        $ContextPool = array();
        $rContextPool = array();

        $name = array();
        if (isset($params['person']['last_name'])) $name['last_name'] = $params['person']['last_name'];
        if (isset($params['person']['first_name'])) $name['first_name'] = $params['person']['first_name'];
        if (isset($params['person']['date'])) $name['date'] = $params['person']['date'];

        if(sizeof($params['person']))
            foreach($plugins['person'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                    if((in_array($source,$params['sources']) || in_array($source_check,$params['sources'])) && (isset($user_sources[$source]) || isset($user_sources[$source_check])) && (!isset($checktypes[$source_check]) || $checktypes[$source_check]['status']>=0))
                        $ContextPool[] = new RequestContext($source, $source_check, $params['request_id'], $path, $start, 'person', $level, $plugin, $params['person']);

        if(sizeof($params['org']))
            foreach($plugins['org'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                    if((in_array($source,$params['sources']) || in_array($source_check,$params['sources'])) && (isset($user_sources[$source]) || isset($user_sources[$source_check])) && (!isset($checktypes[$source_check]) || $checktypes[$source_check]['status']>=0))
                        $ContextPool[] = new RequestContext($source, $source_check, $params['request_id'], $path, $start, 'org', $level, $plugin, $params['org']);

        if(sizeof($params['car']))
            foreach($plugins['car'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                    if((in_array($source,$params['sources']) || in_array($source_check,$params['sources'])) && (isset($user_sources[$source]) || isset($user_sources[$source_check])) && (!isset($checktypes[$source_check]) || $checktypes[$source_check]['status']>=0))
                        $ContextPool[] = new RequestContext($source, $source_check, $params['request_id'], $path, $start, 'car', $level, $plugin, $params['car']);

        foreach($params['phone'] as $index => &$phone)
            foreach($plugins['phone'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                    if((in_array($source,$params['sources']) || in_array($source_check,$params['sources'])) && (isset($user_sources[$source]) || isset($user_sources[$source_check])) && (!isset($checktypes[$source_check]) || $checktypes[$source_check]['status']>=0))
                        $ContextPool[] = new RequestContext($source, $source_check, $params['request_id'], $path, $start, 'phone['.$index.']', $level, $plugin, array_merge(array('phone'=>$phone),isset($checktypes[$source_check]['person'])&&$checktypes[$source_check]['person']?$name:array()));

        foreach($params['email'] as $index => &$email)
            foreach($plugins['email'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                    if((in_array($source,$params['sources']) || in_array($source_check,$params['sources'])) && (isset($user_sources[$source]) || isset($user_sources[$source_check])) && (!isset($checktypes[$source_check]) || $checktypes[$source_check]['status']>=0))
                        $ContextPool[] = new RequestContext($source, $source_check, $params['request_id'], $path, $start, 'email['.$index.']', $level, $plugin, array_merge(array('email'=>$email),isset($checktypes[$source_check]['person'])&&$checktypes[$source_check]['person']?$name:array()));

        if (isset($params['skype']) && isset($plugins['skype'])) foreach($params['skype'] as $index => $skype)
            foreach($plugins['skype'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                    if((in_array($source,$params['sources']) || in_array($source_check,$params['sources'])) && (isset($user_sources[$source]) || isset($user_sources[$source_check])) && (!isset($checktypes[$source_check]) || $checktypes[$source_check]['status']>=0))
                        $ContextPool[] = new RequestContext($source, $source_check, $params['request_id'], $path, $start, 'skype['.$index.']', $level, $plugin, array('skype'=>$skype));

        if (isset($params['nick']) && isset($plugins['nick'])) foreach($params['nick'] as $index => $nick)
            foreach($plugins['nick'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                    if((in_array($source,$params['sources']) || in_array($source_check,$params['sources'])) && (isset($user_sources[$source]) || isset($user_sources[$source_check])) && (!isset($checktypes[$source_check]) || $checktypes[$source_check]['status']>=0))
                        $ContextPool[] = new RequestContext($source, $source_check, $params['request_id'], $path, $start, 'nick['.$index.']', $level, $plugin, array('nick'=>$nick));

        foreach($params['url'] as $index => $url)
            foreach($plugins['url'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                    if((in_array($source,$params['sources']) || in_array($source_check,$params['sources'])) && (isset($user_sources[$source]) || isset($user_sources[$source_check])) && (!isset($checktypes[$source_check]) || $checktypes[$source_check]['status']>=0))
                        $ContextPool[] = new RequestContext($source, $source_check, $params['request_id'], $path, $start, 'url['.$index.']', $level, $plugin, array('url'=>$url));

        foreach($params['ip'] as $index => $ip)
            foreach($plugins['ip'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                    if((in_array($source,$params['sources']) || in_array($source_check,$params['sources'])) && (isset($user_sources[$source]) || isset($user_sources[$source_check])) && (!isset($checktypes[$source_check]) || $checktypes[$source_check]['status']>=0))
                        $ContextPool[] = new RequestContext($source, $source_check, $params['request_id'], $path, $start, 'ip['.$index.']', $level, $plugin, array('ip'=>$ip));

        foreach($params['card'] as $index => $card)
            foreach($plugins['card'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                    if((in_array($source,$params['sources']) || in_array($source_check,$params['sources'])) && (isset($user_sources[$source]) || isset($user_sources[$source_check])) && (!isset($checktypes[$source_check]) || $checktypes[$source_check]['status']>=0))
                        $ContextPool[] = new RequestContext($source, $source_check, $params['request_id'], $path, $start, 'card['.$index.']', $level, $plugin, array('card'=>$card));

        foreach($params['fssp_ip'] as $index => $fssp_ip)
            foreach($plugins['fssp_ip'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                    if((in_array($source,$params['sources']) || in_array($source_check,$params['sources'])) && (isset($user_sources[$source]) || isset($user_sources[$source_check])) && (!isset($checktypes[$source_check]) || $checktypes[$source_check]['status']>=0))
                        $ContextPool[] = new RequestContext($source, $source_check, $params['request_id'], $path, $start, 'fssp_ip['.$index.']', $level, $plugin, array('fssp_ip'=>$fssp_ip));

        foreach($params['osago'] as $index => $osago)
            foreach($plugins['osago'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                    if((in_array($source,$params['sources']) || in_array($source_check,$params['sources'])) && (isset($user_sources[$source]) || isset($user_sources[$source_check])) && (!isset($checktypes[$source_check]) || $checktypes[$source_check]['status']>=0))
                        $ContextPool[] = new RequestContext($source, $source_check, $params['request_id'], $path, $start, 'osago['.$index.']', $level, $plugin, array('osago'=>$osago));

        foreach($params['text'] as $index => $text)
            foreach($plugins['text'] as $source => $contexts)
                foreach ($contexts as $source_check => $plugin)
                    if((in_array($source,$params['sources']) || in_array($source_check,$params['sources'])) && (isset($user_sources[$source]) || isset($user_sources[$source_check])) && (!isset($checktypes[$source_check]) || $checktypes[$source_check]['status']>=0))
                        $ContextPool[] = new RequestContext($source, $source_check, $params['request_id'], $path, $start, 'text['.$index.']', $level, $plugin, array('text'=>$text));

        foreach($ContextPool as &$Context) {
            $ch = $Context->initCurlHandler();
            $this->requestContextPool[(int)$ch]=$Context;
            $rContextPool[(int)$ch]=$Context;
        }

        return $rContextPool;
    }

    public function performRequests($params,$plugins)
    {
        global $contact_types,$contact_urls;
        global $checktypes;

        $sTime = time();
        $timeoutReach = false;
        $level = 0;
        $h_params = array();

        if (isset($params['person']['last_name']) && isset($params['person']['first_name'])) {
            if (isset($params['person']['patronymic']) && $params['person']['patronymic'])
                $h_params[]=mb_strtoupper($params['person']['last_name'].' '.$params['person']['first_name'].' '.$params['person']['patronymic']).(isset($params['person']['date'])?' '.$params['person']['date']:'');
            $h_params[]=mb_strtoupper($params['person']['last_name'].' '.$params['person']['first_name']).(isset($params['person']['date'])?' '.$params['person']['date']:'');
            $h_params[]=mb_strtoupper($params['person']['first_name'].' '.$params['person']['last_name']).(isset($params['person']['date'])?' '.$params['person']['date']:'');

            if (isset($params['person']['patronymic']) && $params['person']['patronymic'])
               $h_params[]=str_translit($params['person']['last_name'].' '.$params['person']['first_name'].' '.$params['person']['patronymic']).(isset($params['person']['date'])?' '.$params['person']['date']:'');
            $h_params[]=str_translit($params['person']['last_name'].' '.$params['person']['first_name']).(isset($params['person']['date'])?' '.$params['person']['date']:'');
            $h_params[]=str_translit($params['person']['first_name'].' '.$params['person']['last_name']).(isset($params['person']['date'])?' '.$params['person']['date']:'');
        }
        if (isset($params['person']['date'])) $h_params[]=$params['person']['date'];
        if (isset($params['person']['inn'])) $h_params[]=$params['person']['inn'];
        if (isset($params['person']['ogrn'])) $h_params[]=$params['person']['ogrn'];
        if (isset($params['person']['passport_series']) && isset($params['person']['passport_number'])) $h_params[]=$params['person']['passport_series'].$params['person']['passport_number'];
        if (isset($params['person']['driver_number'])) $h_params[]=$params['person']['driver_number'];
        if (isset($params['org']['inn'])) $h_params[]=$params['org']['inn'];
        if (isset($params['org']['ogrn'])) $h_params[]=$params['org']['ogrn'];
        if (isset($params['car']['vin'])) $h_params[]=$params['car']['vin'];
        if (isset($params['car']['bodynum'])) $h_params[]=$params['car']['bodynum'];
        if (isset($params['car']['regnum'])) $h_params[]=$params['car']['regnum'];
        if (isset($params['car']['ctc'])) $h_params[]=$params['car']['ctc'];
        if (isset($params['car']['pts'])) $h_params[]=$params['car']['pts'];

        foreach ($params['text'] as $param) $h_params[]=$param;
        foreach ($params['phone'] as $param) $h_params[]=$param;
        foreach ($params['email'] as $param) $h_params[]=$param;

        $workingPool = $this->initRequestContexts($params,$plugins,$level); // формируем пул контекстов по запросу
        $working = sizeof($workingPool);

        do { // цикл выполнения запроса
            $finishedPool = array();

            if(time() - $sTime > $this->totalTimeout) { // Общий таймаут выполнения запроса
                $timeoutReach = true;
            }

            $running = $working;
//            print date('Y-m-d H:i:s')." running = $running\n";

            while (!$timeoutReach && ($running > 0) && ($status = curl_multi_exec($this->mh, $running)) == CURLM_CALL_MULTI_PERFORM); //Запускаем соединения
//            usleep (100000); // 100мс
//            $status = curl_multi_exec($this->mh, $running);
//            print date('Y-m-d H:i:s')." running = $running, status = $status\n";

            while (!$timeoutReach && ($running > 0) && ($status == CURLM_OK)) { //Пока есть незавершенные соединения и нет ошибок мульти-cURL
                $sel = curl_multi_select($this->mh,1); //ждем активность на файловых дескрипторах. Таймаут 1 сек
                usleep (10000); // 10мс
//                usleep (500000); // 500мс
                while (($status = curl_multi_exec($this->mh, $running)) == CURLM_CALL_MULTI_PERFORM);
//                print date('Y-m-d H:i:s')." status = $status\n";

                while (($info = curl_multi_info_read($this->mh)) != false) { //Если есть завершенные соединения
//                    $status = -1;
                    $ind = (int)$info['handle'];
                    $rContext = $this->requestContextPool[$ind];
                    $rContext->getPlugin()->computeRequest($rContext);

                    curl_multi_remove_handle($this->mh, $info['handle']);
                    curl_close($info['handle']);

                    if($rContext->isFinished()) {
                        $finishedPool[] = $rContext;
//                        print date('Y-m-d H:i:s')." [$ind] ".$rContext->getId()." finished\n";
                        --$working;
                    } else {
                        logResponse(array_merge($this->finishedResults,$this->requestContextPool),0);
                        unset($this->requestContextPool[$ind]);

                        $ch = $rContext->initCurlHandler();
                        $ind = (int)$ch;
                        $this->requestContextPool[$ind]=$rContext;
                        $workingPool[$ind] = $rContext;
//                        print date('Y-m-d H:i:s')." [$ind] ".$rContext->getId()." repeated\n";
                    }
                }

                if(time() - $sTime > $this->totalTimeout) { // Общий таймаут выполнения запроса
                    $timeoutReach = true;
                }
            }

            foreach($workingPool as $ind => $rContext) if (isset($rContext)) {
                if($rContext->isReady()) {
                    $checktype = $rContext->getCheckType();
                    if (isset($checktypes[$checktype]) && $checktypes[$checktype]['status']<=-10) { // Проверка отключена
                        if ($checktypes[$checktype]['status']==0)
                            $rContext->setError('Запросы в источник временно выключены');
                        $rContext->setFinished();
                    }
                    if (is_null($rContext->getPlugin())) {
//                        $rContext->setError('Ошибка использования источника');
                        $rContext->setFinished();
                    }
                    if(!$rContext->isFinished() && $rContext->getPlugin()->prepareRequest($rContext) && !$rContext->isFinished()) { // плагин готов выполнить запрос в этом контексте
                        curl_multi_add_handle($this->mh, $rContext->getCurlHandler()); // добавляем дескриптор curl
                        unset($workingPool[$ind]); // удаляем элемент из пула контекстов
//                        print date('Y-m-d H:i:s')." [$ind] ".$rContext->getId()." $checktype started \n";
                    }
                    if ($rContext->isFinished()) { // контекст завершен
                        $finishedPool[] = $rContext;
                        curl_close($rContext->getCurlHandler());
//                        print date('Y-m-d H:i:s')." [$ind] ".$rContext->getId()." finished\n";
                        unset($workingPool[$ind]); // удаляем элемент из пула контекстов
                        --$working;
                    }
                } else {
//                    print date('Y-m-d H:i:s')." [$ind] ".$rContext->getId()." not ready\n";
                }
            }

            foreach($finishedPool as $ind => $rContext) if (isset($rContext) && $rContext->getPlugin()) {
                        $source = $rContext->getSource();
                        $source_name = $rContext->getPlugin()->getName();
                        $data = $rContext->getResultData();
                        if($data instanceof ResultDataList) {
/*
                            $new_params = array('request_id'=>$params['request_id'],'sources'=>$params['sources'],'recursive'=>$params['recursive'],'person'=>array(),'phone'=>array(),'email'=>array(),'nick'=>array(),'url'=>array(),'car'=>array(),'ip'=>array(),'org'=>array(),'fssp_ip'=>array(),'osago'=>array(),'text'=>array());
                            $new = false;
*/
                            $result = $data->getResults();
                            foreach($result as $record) {
                                foreach($record as $field) {
                                    $new_params = array('request_id'=>$params['request_id'],'sources'=>$params['sources'],'recursive'=>$params['recursive'],'person'=>array(),'phone'=>array(),'email'=>array(),'nick'=>array(),'url'=>array(),'car'=>array(),'ip'=>array(),'org'=>array(),'card'=>array(),'fssp_ip'=>array(),'osago'=>array(),'text'=>array());
                                    $new = false;

                                    $name = $field->getName();
                                    $ftype = $type = $field->getType();
                                    if ($type=='skype' || $type=='telegram') $type='nick';
                                    $value = $field->getValue();
                                    $recursive = $field->getRecursive();
                                    if ($type=='phone') $value=normal_phone($value);
                                    if ($type=='email') $value=normal_email($value);
                                    if ($type=='nick') $value=normal_login($value);
                                    if ($value && !preg_match("/[\*•]/",$field->getValue()) && ($params['recursive'] || $recursive || $type=='nick') && ($level<=20) && in_array($type,$contact_types) && !in_array($value,$params[$type])) {
                                        $params[$type][] = $value;
                                        $new_params[$type][] = $value;
                                        if (!$params['recursive'] && !$recursive) {
                                            if ($source=='skype')
                                                $new_params['sources'] = array_intersect($new_params['sources'],array($source,'microsoft'));
                                            else
                                                $new_params['sources'] = array_intersect($new_params['sources'],array($source));
                                        }
                                        $new = true;
                                    }
                                    if ($value && ($type=='url' || $type=='hidden') && (/*$params['recursive'] || */$recursive) && ($level<=20) && ($contact_source = array_search(strtr(parse_url($value,PHP_URL_HOST),array('www.'=>'')),$contact_urls))!==false && !in_array($value,$params['url'])/* && ($params['recursive'] || in_array($contact_source,$params['sources']))*/) {
                                        $params['url'][] = $value;
                                        $new_params['url'][] = $value;
                                        $new_params['sources'] = /*array($contact_source.'_url'); //*/ array_intersect($new_params['sources'],array($contact_source,$contact_source.'_url'));
                                        $new_params['recursive'] = false;
                                        $new = true;
                                    }
                                    if ($value && (strtoupper($name)=='OGRN') && (strlen($value)==15) && (array_search($value,$h_params)===false) /*&& !isset($params['person']['ogrn'])*/) {
                                        $h_params[] = $value;
                                        $params['person']['ogrn'] = $value;
                                        $new_params['person']['ogrn'] = $value;
                                        $new_params['sources'] = array_intersect($params['sources'],array('egrul'));
                                        $new = true;
                                    }
                                    if ($value && (strtoupper($name)=='INN' || strtoupper($name)=='HEADINN' || strtoupper($name)=='OWNERINN') && 
                                      preg_match("/\d{12}/",$value) && (sizeof($result)<10/* || $source_name=='egrul'*/) && ($source_name!='Bankrot') && (array_search($value,$h_params)===false) /*&& !isset($params['person']['inn'])*/) {
                                        $h_params[] = $value;
                                        $params['person']['inn'] = $value;
                                        $new_params['person']['inn'] = $value;

                                        if (isset($record['firstname']) && isset($record['lastname'])) {
                                            $params['person']['last_name'] = $record['lastname']->getValue();
                                            $params['person']['first_name'] = $record['firstname']->getValue();
                                            $new_params['person']['last_name'] = $record['lastname']->getValue();
                                            $new_params['person']['first_name'] = $record['firstname']->getValue();
                                            if (isset($record['middlename'])) {
                                                $params['person']['patronymic'] = $record['middlename']->getValue();
                                                $new_params['person']['patronymic'] = $record['middlename']->getValue();
                                            }
                                        } else {
                                            $names = array(
                                                'HeadINN'=>'head',
                                                'OwnerINN'=>'owner',
                                            );
                                            if (isset($names[$name]) && isset($record[$names[$name]])) {
                                                $params['person']['last_name'] = '';
                                                $params['person']['first_name'] = $record[$names[$name]]->getValue();
                                                $new_params['person']['last_name'] = '';
                                                $new_params['person']['first_name'] = $record[$names[$name]]->getValue();
                                            }
                                        }

                                        $new_params['sources'] = array_intersect($params['sources'],array('fns','fns_bi','fns_npd','fns_invalid','bankrot','cbr','egrul','gisgmp','gosuslugi_inn','kad'));
                                        $new = true;
                                    }
                                    if ($value && (strpos(strtoupper($name),'BIRTHDATE')!==false || strpos(strtoupper($name),'BIRTHDAY')!==false) && (strlen($value)==10) && (sizeof($result)<10) && isset($record['name']) && strpos($record['name']->getValue(),' ') && (array_search(mb_strtoupper($record['name']->getValue().' '.$value),$h_params)===false) /*&& !isset($params['person']['date'])*/) {
                                        $new_params['sources'] = array('people','vk','ok');
                                        if (isset($record['firstname']) && isset($record['lastname'])) {
                                            if (!preg_match("/[^А-Яа-яЁё\s\-\.]/ui",$record['lastname']->getValue().' '.$record['firstname']->getValue()))
                                                $new_params['sources'] = array_merge($new_params['sources'],array('fssp','gisgmp','mvd','terrorist','fns','reestrzalogov'));
                                            $h_params[] = mb_strtoupper($record['lastname']->getValue().' '.$record['firstname']->getValue().(isset($record['middlename'])?' '.$record['middlename']->getValue():'').' '.$value);
                                            $h_params[] = mb_strtoupper($record['firstname']->getValue().' '.$record['lastname']->getValue().(isset($record['middlename'])?' '.$record['middlename']->getValue():'').' '.$value);
                                            $h_params[] = str_translit($record['firstname']->getValue().' '.$record['lastname']->getValue().(isset($record['middlename'])?' '.$record['middlename']->getValue():'').' '.$value);
                                            $params['person']['last_name'] = $record['lastname']->getValue();
                                            $params['person']['first_name'] = $record['firstname']->getValue();
                                            $new_params['person']['last_name'] = $record['lastname']->getValue();
                                            $new_params['person']['first_name'] = $record['firstname']->getValue();
                                            if (isset($record['middlename'])) {
                                                $params['person']['patronymic'] = $record['middlename']->getValue();
                                                $new_params['person']['patronymic'] = $record['middlename']->getValue();
                                            } else {
                                                $params['person']['patronymic'] = '';
                                                $new_params['person']['patronymic'] = '';
                                            }
                                            $new = true;
//                                            echo "New full name: ".$record['lastname']->getValue().' '.$record['firstname']->getValue().' '.$value." to ".implode(',',$new_params['sources'])."\n";
                                        } else {
                                            $name = trim(preg_replace("/[^А-Яа-яЁёA-Za-z\s\-\.]/ui","",$record['name']->getValue()));
                                            $new_params['sources'] = array('people','vk','ok');
                                            if (!preg_match("/[^А-Яа-яЁё\s\-\.]/ui",$name))
                                                $new_params['sources'] = array_merge($new_params['sources'],array('fssp','gisgmp'));
                                            if (strpos($name,' ') && array_search(mb_strtoupper($name.' '.$value),$h_params)===false && (!isset($params['person']['last_name']) || !isset($params['person']['first_name']) ||
                                                strpos(mb_strtoupper($name),mb_strtoupper(trim($params['person']['last_name'].' '.$params['person']['first_name'])))!==0)) {
                                                $h_params[] = mb_strtoupper($name.' '.$value);
                                                $h_params[] = str_translit($name.' '.$value);
                                                $params['person']['last_name'] = '';
                                                $params['person']['first_name'] = $name;
                                                $params['person']['patronymic'] = '';
                                                $new_params['person']['last_name'] = '';
                                                $new_params['person']['first_name'] = $name;
                                                $new_params['person']['patronymic'] = '';
                                                $new = true;
//                                                echo "New name: $name $value to ".implode(',',$new_params['sources'])."\n";
                                            }
                                        }
                                        if ($new) {
                                            $params['person']['date'] = $value;
                                            $new_params['person']['date'] = $value;
                                            $new_params['sources'] = array_intersect($params['sources'],$new_params['sources']);
                                        }
                                    }
                                    if ($value && (strpos(strtoupper($name),'AGE')!==false) && intval($value) && !isset($record['birthdate']) && !isset($record['birthday']) && (sizeof($result)<10) /*&& isset($record['location']) && $record['location']->getValue()*/ && isset($record['name']) && strpos($record['name']->getValue(),' ') && (array_search(mb_strtoupper($record['name']->getValue().' '.$value),$h_params)===false) /*&& !isset($params['person']['date'])*/) {
                                        $new_params['sources'] = array('ok');
                                        if (isset($record['firstname']) && isset($record['lastname'])) {
//                                            if (!preg_match("/[^А-Яа-яЁё\s\-\.]/ui",$record['lastname']->getValue().' '.$record['firstname']->getValue()))
//                                                $new_params['sources'] = array_merge($new_params['sources'],array('bankrot','terrorist','fns'));
                                            $h_params[] = mb_strtoupper($record['lastname']->getValue().' '.$record['firstname']->getValue().' '.$value);
                                            $h_params[] = mb_strtoupper($record['firstname']->getValue().' '.$record['lastname']->getValue().' '.$value);
                                            $h_params[] = str_translit($record['firstname']->getValue().' '.$record['lastname']->getValue().' '.$value);
                                            $params['person']['last_name'] = $record['lastname']->getValue();
                                            $params['person']['first_name'] = $record['firstname']->getValue();
                                            $params['person']['patronymic'] = '';
                                            $new_params['person']['last_name'] = $record['lastname']->getValue();
                                            $new_params['person']['first_name'] = $record['firstname']->getValue();
                                            $new_params['person']['patronymic'] = '';
                                            $new = true;
//                                            echo "New full name: ".$record['lastname']->getValue().' '.$record['firstname']->getValue().' '.$value." to ".implode(',',$new_params['sources'])."\n";
                                        } else {
                                            $name = trim(preg_replace("/[^А-Яа-яЁёA-Za-z\s\-\.\*]/ui","",$record['name']->getValue()));
                                            $new_params['sources'] = array('ok');
//                                            if (!preg_match("/[^А-Яа-яЁё\s\-\.]/ui",$name))
//                                                $new_params['sources'] = array_merge($new_params['sources'],array('bankrot','terrorist','fns'));
                                            if (strpos($name,' ') && array_search(mb_strtoupper($name.' '.$value),$h_params)===false && (!isset($params['person']['last_name']) || !isset($params['person']['first_name']) ||
                                                strpos(mb_strtoupper($name),mb_strtoupper(trim($params['person']['last_name'].' '.$params['person']['first_name'])))!==0)) {
                                                $h_params[] = mb_strtoupper($name.' '.$value);
                                                $h_params[] = str_translit($name.' '.$value);
                                                $params['person']['last_name'] = '';
                                                $params['person']['first_name'] = $name;
                                                $params['person']['patronymic'] = '';
                                                $new_params['person']['last_name'] = '';
                                                $new_params['person']['first_name'] = $name;
                                                $new_params['person']['patronymic'] = '';
                                                $new = true;
//                                                echo "New name: $name $value to ".implode(',',$new_params['sources'])."\n";
                                            }
                                        }
                                        if ($new) {
                                            $params['person']['age'] = intval($value);
                                            $new_params['person']['age'] = intval($value);
                                            if (isset($record['location'])) {
                                                $params['person']['location'] = $record['location']->getValue();
                                                $new_params['person']['location'] = $record['location']->getValue();
                                            }
                                            $new_params['sources'] = array_intersect($params['sources'],$new_params['sources']);
                                        }
                                    }
                                    if ($value && (strtoupper($name)=='JOB' || strtoupper($name)=='EMPLOYER') && (sizeof($result)<10) && isset($record['name']) && strpos($record['name']->getValue(),' ') && (array_search(mb_strtoupper($record['name']->getValue().' '.$value),$h_params)===false) /*&& !isset($params['person']['job'])*/) {
                                        $h_params[] = mb_strtoupper($record['name']->getValue().' '.$value);
                                        $h_params[] = str_translit($record['name']->getValue().' '.$value);
/*
                                        $params['person']['last_name'] = '';
                                        $params['person']['first_name'] = $record['name']->getValue();
                                        $params['person']['job'] = $value;
                                        $new_params['person']['last_name'] = '';
                                        $new_params['person']['first_name'] = $record['name']->getValue();
                                        $new_params['person']['job'] = $value;
*/
                                        $val = explode(',',$value);
                                        $params['text'][] = $record['name']->getValue().' '.$val[0];
                                        $new_params['text'][] = $record['name']->getValue().' '.$val[0];
                                        $new_params['sources'] = array();
                                        if ($source_name!='HH') $new_params['sources'][] = 'hh';
                                        if ($source_name!='Facebook') $new_params['sources'][] = 'facebook';
                                        $new_params['sources'] = array_intersect($params['sources'],$new_params['sources']);
                                        $new = true;
                                    }
                                    if ($value && (strtoupper($name)=='PASSPORT') && (strlen($value)==10) && (array_search($value,$h_params)===false) /*&& !isset($params['person']['passport_number'])*/) {
                                        $h_params[] = $value;
                                        $params['person']['passport_series'] = substr($value,0,4);
                                        $params['person']['passport_number'] = substr($value,4);
                                        $new_params['sources'] = array('fms','gisgmp','gosuslugi_passport');
                                        if (isset($params['person']['last_name']) && isset($params['person']['first_name'])) {
                                            $new_params['person']['last_name'] = $params['person']['last_name'];
                                            $new_params['person']['first_name'] = $params['person']['first_name'];
                                            if (isset($params['person']['patronymic']))
                                                $new_params['person']['patronymic'] = $params['person']['patronymic'];
                                            if (isset($params['person']['date']))
                                                $new_params['person']['date'] = $params['person']['date'];
                                            if (isset($params['person']['patronymic']) && isset($params['person']['date']))
                                                $new_params['sources'][] = 'fns';
                                        }
                                        $new_params['person']['passport_series'] = substr($value,0,4);
                                        $new_params['person']['passport_number'] = substr($value,4);
                                        $new_params['sources'] = array_intersect($params['sources'],$new_params['sources']);
                                        $new = true;
                                    }
                                    if ($value && (strtoupper($name)=='DRIVERLICENSE') && (array_search($value,$h_params)===false) && strpos($value,'*')===false /*&& !isset($params['person']['driver_number'])*/) {
                                        $h_params[] = $value;
                                        $params['person']['driver_number'] = $value;
                                        $new_params['sources'] = array('gisgmp','avtokod');
                                        if (isset($params['person']['last_name']) && isset($params['person']['first_name'])) {
                                            $new_params['person']['last_name'] = $params['person']['last_name'];
                                            $new_params['person']['first_name'] = $params['person']['first_name'];
                                            if (isset($params['person']['patronymic']))
                                                $new_params['person']['patronymic'] = $params['person']['patronymic'];
                                            if (isset($params['person']['date']))
                                                $new_params['person']['date'] = $params['person']['date'];
                                            if (isset($params['person']['patronymic']) && isset($params['person']['date']))
                                                $new_params['sources'][] = 'rsa_kbm';
                                        }
                                        $new_params['person']['driver_number'] = $value;
                                        $new_params['sources'] = array_intersect($params['sources'],$new_params['sources']);
                                        $new = true;
                                    }
                                    if ($value && (strtoupper($name)=='INN' || strtoupper($name)=='HEADINN' || strtoupper($name)=='OWNERINN') && (/*$params['recursive'] || */$recursive) && ($level<=20) && (strlen($value)==10) && (sizeof($result)<10) && (array_search($value,$h_params)===false) /*&& !isset($params['org']['inn'])*/) {
                                        $h_params[] = $value;
                                        $params['org']['inn'] = $value;
                                        $new_params['org']['inn'] = $value;
                                        $names = array(
                                            'inn'=>'name',
                                            'INN'=>'name',
                                            'HeadINN'=>'head',
                                            'OwnerINN'=>'owner',
                                        );
                                        if (isset($names[$name]) && isset($record[$names[$name]])) {
                                            $orgname = strtr($record[$names[$name]]->getValue(),array('«'=>'"','»'=>'"'));
                                            $params['org']['name'] = $orgname;
                                            $new_params['org']['name'] = $orgname;
                                        }
                                        $new_params['sources'] = array_intersect($params['sources'],array('fns','bankrot','cbr','egrul','vestnik','reestrzalogov','kad'));
                                        $new = true;
                                    }
                                    if ($value && (strtoupper($name)=='ORGNAME' || strtoupper($name)=='ORGFULLNAME') && (sizeof($result)<5) && (array_search($value,$h_params)===false) /*&& !isset($params['org']['name'])*/) {
                                        $value = strtr($value,array('"'=>' ','«'=>' ','»'=>' '));
                                        $h_params[] = $value;
                                        $params['org']['name'] = $value;
                                        $new_params['org']['name'] = $value;
                                        if (isset($record['address'])) {
                                            $address = $record['address']->getValue().',';

                                            $address = preg_replace("/\s\([^\)]+\)/","",$address);
                                            $address = preg_replace("/\sресп\s/ui","",$address);
                                            $address = preg_replace("/\sрайон\s/ui","",$address);
                                            $address = preg_replace("/\sа\.о\.\,/ui","",$address);
                                            $address = preg_replace("/\sа\.обл\.\,/ui","",$address);
                                            $address = preg_replace("/\sобл[асть]*\,/ui","",$address);
                                            $address = preg_replace("/\sкр[ай]*\,/ui","",$address);
                                            $address = preg_replace("/\sрайон\,/ui","",$address);
                                            $address = preg_replace("/\sр-н\,/ui","",$address);
                                            $address = preg_replace("/\sг\,/ui",",",$address);
                                            $address = preg_replace("/\sГОРОД\,/ui",",",$address);
                                            $address = preg_replace("/\"/"," ",$address);
                                            $address = preg_replace("/[А-Яа-яЁё]+\.\,/ui",",",$address);
                                            $address = preg_replace("/[А-Яа-яЁё]\.\,/ui",",",$address);
                                            $address = preg_replace("/\/[А-Яа-яЁё]*\,/ui",",",$address);

                                            $address = preg_replace("/\sУЛ[\.]+/ui"," ",$address);
                                            $address = preg_replace("/\sУЛИЦА/ui"," ",$address);
                                            $address = preg_replace("/\sПЛ[\.]+/ui"," ",$address);
                                            $address = preg_replace("/\sПЛОЩАДЬ/ui"," ",$address);
                                            $address = preg_replace("/\sПР-[КДТ]+/ui"," ",$address);
                                            $address = preg_replace("/\sБ-Р/ui"," ",$address);
                                            $address = preg_replace("/\sБУЛЬВАР/ui"," ",$address);
                                            $address = preg_replace("/\sМКР/ui"," ",$address);
                                            $address = preg_replace("/\sМИКРОРАЙОН/ui"," ",$address);
                                            $address = preg_replace("/\sКВАРТАЛ/ui"," ",$address);
                                            $address = preg_replace("/\sШОССЕ/ui"," ",$address);
                                            $address = preg_replace("/\sШ[\.]+/ui"," ",$address);
                                            $address = preg_replace("/\sПР[\.]+/ui"," ",$address);
                                            $address = preg_replace("/\sПРОСП[\.]+/ui"," ",$address);
                                            $address = preg_replace("/\sПРОСПЕКТ/ui"," ",$address);
                                            $address = preg_replace("/\sПЕР[\.]+/ui"," ",$address);
                                            $address = preg_replace("/\sПЕРЕУЛОК/ui"," ",$address);

                                            $address = preg_replace("/\s[0-9]+\-[ЙЯ]/ui","",$address);
                                            $address = preg_replace("/,[0-9]+\-[ЙЯ]/ui",",",$address);

                                            $address = preg_replace("/\./"," ",$address);
                                            $address = preg_replace("/\s[\-]+/ui","",$address);
                                            $address = preg_replace("/\s\,/",",",$address);

                                            $address = strtr($address,array('Ё'=>'Е','ё'=>'е'));

                                            if (preg_match_all("/\s([А-Яа-яЁё\-\/]{2,}[0-9]*\,|[А-Яа-яЁё\-\/][0-9]+\,|[0-9]+\-[яЯйЙ]\,)/ui",$address,$matches)) {
                                                $search = preg_replace("/\,/","",implode(' ',$matches[1]));

                                                $address = preg_replace("/\sКОРП[УС\.]*/ui",", КОРПУС ",$address);
                                                $address = preg_replace("/\sСТР[ОЕНИЕ\.]*/ui",", СТРОЕНИЕ ",$address);
                                                $address = preg_replace("/\sЛИТЕР[А\.]*/ui",", ЛИТЕРА ",$address);
                                                $address = preg_replace("/\sОФ[ИС\.]\s/ui"," ",$address);
                                                $address = preg_replace("/\sКОМ[\.]\s/ui"," КОМНАТА ",$address);
                                                $address = preg_replace("/\sНЕЖ.\s/ui"," ",$address);
                                                $address = preg_replace("/\sПОМ[\.]\s/ui"," ПОМЕЩЕНИЕ ",$address);
                                                $address = preg_replace("/\sЭТ[\.]\s/ui"," ЭТАЖ ",$address);
                                                $address = preg_replace("/\sКВ[\.]\s/ui"," КВАРТИРА ",$address);

                                                $address = preg_replace("/\sДОМОВЛ[АДЕНИЕ\.]*/ui"," ",$address);
                                                $address = preg_replace("/\sДОМ\s/ui"," ",$address);
                                                $address = preg_replace("/\sД\s/ui"," ",$address);
                                                $address = preg_replace("/\sКВАРТИРА\s/ui"," ",$address);
//                                                $address = preg_replace("/\sОФИС\s/ui"," ",$address);

//                                                if (preg_match("/\,\s([0-9][0-9\-\/А-Яа-я]*)\,/ui",$address,$matches)) {
                                                if (preg_match("/\,\s([0-9]+)\,/ui",$address,$matches)) {
                                                    $search .= ' '.preg_replace("/\-/","",$matches[1]);
                                                }

                                                $params['org']['address'] = $search;
                                                $new_params['org']['address'] = $search;
                                            }
                                        }
                                        $new_params['sources'] = array_intersect($params['sources'],array('terrorist','fssp','fsspsite','fsspapi'));
                                        $new = true;
                                    }
                                    if ($value && !preg_match("/\*/",$value) && (strtoupper($name)=='VIN') && (sizeof($result)<10) && (array_search($value,$h_params)===false) /*!isset($params['car']['vin'])*/) {
                                        $h_params[] = $value;
                                        $params['car']['vin'] = $value;
                                        $new_params['car']['vin'] = $value;
                                        if (isset($params['car']['ctc']))
                                            $new_params['car']['ctc'] = $params['car']['ctc'];
                                        $new_params['sources'] = array('gibdd','gibdd_history','gibdd_aiusdtp','gibdd_wanted','gibdd_restricted','gibdd_diagnostic','avtokod','reestrzalogov');
                                        if ($source_name!='eaisto' && $source_name!='ЕАИСТО') $new_params['sources'][] = 'eaisto';
//                                        /*if ($source_name!='RSA')*/ $new_params['sources'][] = 'rsa_policy';
                                        if ($source_name!='carinfo') $new_params['sources'][] = 'carinfo';
                                        if ($source_name!='alfastrah') $new_params['sources'][] = 'alfastrah';
                                        if ($source_name!='elpts') $new_params['sources'][] = 'elpts';
                                        $new_params['sources'] = array_intersect($params['sources'],$new_params['sources']);
                                        $new = true;
                                    }
                                    if ($value && !preg_match("/\*/",$value) && (strtoupper($name)=='BODYNUM') && (array_search($value,$h_params)===false) /*!isset($params['car']['bodynum'])*/) {
                                        $h_params[] = $value;
                                        $params['car']['bodynum'] = $value;
                                        $new_params['car']['bodynum'] = $value;
                                        $new_params['sources'] = array('gibdd','gibdd_history','gibdd_aiusdtp','gibdd_wanted','gibdd_restricted','gibdd_diagnostic');
                                        if ($source_name!='eaisto' && $source_name!='ЕАИСТО') $new_params['sources'][] = 'eaisto';
                                        if ($source_name!='RSA') $new_params['sources'][] = 'rsa_policy';
                                        $new_params['sources'] = array_intersect($params['sources'],$new_params['sources']);
                                        $new = true;
                                    }
                                    if ($value && !preg_match("/\*/",$value) && (strtoupper($name)=='REGNUM') && (array_search($value,$h_params)===false) /*!isset($params['car']['regnum'])*/) {
                                        $h_params[] = $value;
                                        $value = trim(strtr(mb_strtoupper(html_entity_decode($value,ENT_COMPAT,"UTF-8")),array(' '=>'','​'=>'','A'=>'А','B'=>'В','C'=>'С','E'=>'Е','H'=>'Н','K'=>'К','M'=>'М','O'=>'О','P'=>'Р','T'=>'Т','Y'=>'У','X'=>'Х','a'=>'а','c'=>'с','e'=>'е','k'=>'к','m'=>'м','o'=>'о','p'=>'р','t'=>'т','y'=>'у','x'=>'х')));
                                        $h_params[] = $value;
                                        $params['car']['regnum'] = $value;
                                        $new_params['car']['regnum'] = $value;
                                        if (isset($params['car']['ctc']))
                                            $new_params['car']['ctc'] = $params['car']['ctc'];
                                        $new_params['sources'] = array();
                                        if ($source_name!='eaisto' && $source_name!='ЕАИСТО') $new_params['sources'][] = 'eaisto';
                                        if ($source_name!='RSA') $new_params['sources'][] = 'rsa_policy';
                                        if ($source_name!='carinfo') $new_params['sources'][] = 'carinfo';
                                        if ($source_name!='alfastrah') $new_params['sources'][] = 'alfastrah';
                                        $new_params['sources'] = array_intersect($params['sources'],$new_params['sources']);
                                        $new = true;
                                    }
                                    if ($value && !preg_match("/\*/",$value) && (strtoupper($name)=='CTC' || strtoupper($name)=='STS') && (array_search($value,$h_params)===false) /*!isset($params['car']['ctc'])*/) {
                                        $h_params[] = $value;
                                        $value = trim(strtr(mb_strtoupper(html_entity_decode($value,ENT_COMPAT,"UTF-8")),array(' '=>'','​'=>'','№'=>'','N'=>'','A'=>'А','B'=>'В','C'=>'С','E'=>'Е','H'=>'Н','K'=>'К','M'=>'М','O'=>'О','P'=>'Р','T'=>'Т','Y'=>'У','X'=>'Х','a'=>'а','c'=>'с','e'=>'е','k'=>'к','m'=>'м','o'=>'о','p'=>'р','t'=>'т','y'=>'у','x'=>'х')));
                                        $h_params[] = $value;
                                        $params['car']['ctc'] = $value;
                                        $new_params['car']['ctc'] = $value;
                                        if (isset($params['car']['regnum']))
                                            $new_params['car']['regnum'] = $params['car']['regnum'];
                                        $new_params['sources'] = array_intersect($params['sources'],array('gibdd_fines','avtokod','gisgmp'));
                                        $new = true;
                                    }
                                    if ($value && !preg_match("/\*/",$value) && (strtoupper($name)=='PTS' || strtoupper($name)=='EPTS') && (array_search($value,$h_params)===false) /*!isset($params['car']['pts'])*/) {
                                        $h_params[] = $value;
                                        $value = trim(strtr(mb_strtoupper(html_entity_decode($value,ENT_COMPAT,"UTF-8")),array(' '=>'','​'=>'','№'=>'','N'=>'','A'=>'А','B'=>'В','C'=>'С','E'=>'Е','H'=>'Н','K'=>'К','M'=>'М','O'=>'О','P'=>'Р','T'=>'Т','Y'=>'У','X'=>'Х','a'=>'а','c'=>'с','e'=>'е','k'=>'к','m'=>'м','o'=>'о','p'=>'р','t'=>'т','y'=>'у','x'=>'х')));
                                        $h_params[] = $value;

                                        $params['car']['pts'] = $value;
                                        $new_params['car']['pts'] = $value;
                                        $new_params['sources'] = array_intersect($params['sources'],array('avtokod'));
                                        $new = true;
                                    }
                                    if ($value && (strtoupper($name)=='IPNUMBER' || strtoupper($name)=='CASENUMBER' || strtoupper($name)=='DOCNUMBER') && preg_match("/([0-9]+[\-\/][0-9]+[\-\/][0-9]+)-ИП/", $value) && sizeof($result)<10 && !in_array($value,$params['fssp_ip'])) {
                                        $params['fssp_ip'][] = $value;
                                        $new_params['fssp_ip'][] = $value;
                                        $new_params['sources'] = array();
                                        if ($source_name!='fssp' && $source_name!='fsspsite' && $source_name!='gisgmp') $new_params['sources'][] = 'fssp';
                                        if ($source_name!='gisgmp' && !isset($record['close_date'])) $new_params['sources'][] = 'gisgmp';
                                        $new_params['sources'] = array_intersect($params['sources'],$new_params['sources']);
                                        $new = true;
                                    }
                                    if ($value && ($name=='PolicyNumber') && sizeof($result)<10 && !in_array(strtr($value,array(' '=>'')),$params['osago'])) {
                                        $params['osago'][] = strtr($value,array(' '=>''));
                                        $new_params['osago'][] = strtr($value,array(' '=>''));
                                        $new = true;
                                    }
                                    if (!$timeoutReach && $new) {
//                                        var_dump($new_params); echo "\r\n";
                                        $newPool = $this->initRequestContexts($new_params,$plugins,++$level,$rContext->getPath(),$rContext->getStart());
                                        $working += sizeof($newPool);
                                        foreach($newPool as $newContext)
                                            $workingPool[] = $newContext;
                                    }
                                }
                            }
                        }
                        if (!$timeoutReach && $rContext->getError() && $rContext->getSource()=='fssp' && ($rContext->getCheckType()=='fssp_person' || $rContext->getCheckType()=='fssp_org') && array_key_exists('fsspsite',$plugins['person']) && !in_array('fsspsite',$params['sources']) && !in_array('fssp_person',$params['sources'])) {
// Ошибка, выполняем через резервный источник
//                            $rContext->setError(false);
                            $new_params = array('request_id'=>$params['request_id'],'sources'=>array(),'recursive'=>$params['recursive'],'person'=>array(),'phone'=>array(),'email'=>array(),'nick'=>array(),'url'=>array(),'car'=>array(),'ip'=>array(),'org'=>array(),'card'=>array(),'fssp_ip'=>array(),'osago'=>array(),'text'=>array());
                            if ($rContext->getCheckType()=='fssp_person')
                                $new_params['person'] = $rContext->getInitData();
                            if ($rContext->getCheckType()=='fssp_org')
                                $new_params['org'] = $rContext->getInitData();
                            $new_params['sources'] = array('fsspsite');

                            $newPool = $this->initRequestContexts($new_params,$plugins,$level,$rContext->getParent(),$rContext->getStart());
                            $working += sizeof($newPool);
                            foreach($newPool as $newContext)
                                $workingPool[] = $newContext;
/*
                        } elseif (!$timeoutReach && $rContext->getError() && $rContext->getCheckType()=='google_phone' && $plugins['phone']['google']['google_phone']!='GooglePlugin') {
// Ошибка, выполняем через резервный плагин
//                            $rContext->setError(false);
                            $new_params = array('request_id'=>$params['request_id'],'sources'=>array(),'recursive'=>$params['recursive'],'person'=>array(),'phone'=>array(),'email'=>array(),'nick'=>array(),'url'=>array(),'car'=>array(),'ip'=>array(),'org'=>array(),'card'=>array(),'fssp_ip'=>array(),'osago'=>array(),'text'=>array());
                            $new_params['phone'][] = $rContext->getInitData()['phone'];
                            $new_params['sources'] = array('google_phone');
                            $plugins['phone']['google']['google_phone'] = 'GooglePlugin';

                            $newPool = $this->initRequestContexts($new_params,$plugins,$level,$rContext->getParent(),$rContext->getStart());
                            $working += sizeof($newPool);
                            foreach($newPool as $newContext)
                                $workingPool[] = $newContext;
                        } elseif (!$timeoutReach && $rContext->getError() && $rContext->getCheckType()=='google_email' && $plugins['email']['google']['google_email']!='GooglePlugin') {
// Ошибка, выполняем через резервный плагин
//                            $rContext->setError(false);
                            $new_params = array('request_id'=>$params['request_id'],'sources'=>array(),'recursive'=>$params['recursive'],'person'=>array(),'phone'=>array(),'email'=>array(),'nick'=>array(),'url'=>array(),'car'=>array(),'ip'=>array(),'org'=>array(),'card'=>array(),'fssp_ip'=>array(),'osago'=>array(),'text'=>array());
                            $new_params['email'][] = $rContext->getInitData()['email'];
                            $new_params['sources'] = array('google_email');
                            $plugins['email']['google']['google_email'] = 'GooglePlugin';

                            $newPool = $this->initRequestContexts($new_params,$plugins,$level,$rContext->getParent(),$rContext->getStart());
                            $working += sizeof($newPool);
                            foreach($newPool as $newContext)
                                $workingPool[] = $newContext;
*/
                        } elseif (!$timeoutReach && $rContext->getError() && $rContext->getCheckType()=='viber_phone' && $plugins['phone']['viber']['viber_phone']!='ViberPlugin') {
// Ошибка, выполняем через резервный плагин
//                            $rContext->setError(false);
                            $new_params = array('request_id'=>$params['request_id'],'sources'=>array(),'recursive'=>$params['recursive'],'person'=>array(),'phone'=>array(),'email'=>array(),'nick'=>array(),'url'=>array(),'car'=>array(),'ip'=>array(),'org'=>array(),'card'=>array(),'fssp_ip'=>array(),'osago'=>array(),'text'=>array());
                            $new_params['phone'][] = $rContext->getInitData()['phone'];
                            $new_params['sources'] = array('viber_phone');
                            $plugins['phone']['viber']['viber_phone'] = 'ViberWinPlugin';

                            $newPool = $this->initRequestContexts($new_params,$plugins,$level,$rContext->getParent(),$rContext->getStart());
                            $working += sizeof($newPool);
                            foreach($newPool as $newContext)
                                $workingPool[] = $newContext;
/*
                        } elseif (!$timeoutReach && $rContext->getError() && $rContext->getCheckType()=='viber_phone' && array_key_exists('viberwin',$plugins['phone']) && !in_array('viberwin',$params['sources'])) {
// Ошибка, выполняем через резервный источник
//                            $rContext->setError(false);
                            $new_params = array('request_id'=>$params['request_id'],'sources'=>array(),'recursive'=>$params['recursive'],'person'=>array(),'phone'=>array(),'email'=>array(),'nick'=>array(),'url'=>array(),'car'=>array(),'ip'=>array(),'org'=>array(),'card'=>array(),'fssp_ip'=>array(),'osago'=>array(),'text'=>array());
                            $new_params['phone'][] = $rContext->getInitData()['phone'];
                            $new_params['sources'] = array('viberwin');

                            $newPool = $this->initRequestContexts($new_params,$plugins,$level,$rContext->getParent(),$rContext->getStart());
                            $working += sizeof($newPool);
                            foreach($newPool as $newContext)
                                $workingPool[] = $newContext;
*/
/*
                        } elseif (!$timeoutReach && $rContext->getError() && $rContext->getCheckType()=='numbuster_phone' && array_key_exists('numbusterapp',$plugins['phone']) && !in_array('numbusterapp',$params['sources'])) {
// Ошибка, выполняем через резервный источник
//                            $rContext->setError(false);
                            $new_params = array('request_id'=>$params['request_id'],'sources'=>array(),'recursive'=>$params['recursive'],'person'=>array(),'phone'=>array(),'email'=>array(),'nick'=>array(),'url'=>array(),'car'=>array(),'ip'=>array(),'org'=>array(),'card'=>array(),'fssp_ip'=>array(),'osago'=>array(),'text'=>array());
                            $new_params['phone'][] = $rContext->getInitData()['phone'];
                            $new_params['sources'] = array('numbusterapp');

                            $newPool = $this->initRequestContexts($new_params,$plugins,$level,$rContext->getParent(),$rContext->getStart());
                            $working += sizeof($newPool);
                            foreach($newPool as $newContext)
                                $workingPool[] = $newContext;
*/
                        } else {
// Сохраняем ответ
                            $this->finishedResults[] = $rContext;
                            logSourceResult($rContext);
//                            logResponse($this->requestContextPool,0);
                            logResponse($this->finishedResults,0);
                        }
            }

            if(time() - $sTime > $this->totalTimeout) { // Общий таймаут выполнения запроса
                $timeoutReach = true;
            }

            if (($working > 0) && !$timeoutReach) usleep(10000);
//            print "running=" . $running . " working=" . $working . " workingPool = [" . sizeof($workingPool) . "]\n";
        } while (($working > 0) && !$timeoutReach);

        foreach($this->requestContextPool as &$rContext) {
            if(!$rContext->isFinished()) {
                $rContext->setFinished();
                $rContext->setError($timeoutReach?'Превышено время ожидания':'Неизвестная ошибка');
                $this->finishedResults[] = $rContext;
                logSourceResult($rContext);

                $ch = $rContext->getCurlHandler();
                if ($ch) curl_close($ch);
            }
        }
//        $response = logResponse($this->requestContextPool,1);
        $response = logResponse($this->finishedResults,1);
        return $response;
//        return $this->requestContextPool;
    }
}

?>
<?php
require('config.php');
require_once('routines.php');
/*
function sig_handler($sig) {
    switch($sig) { 
        case SIGTERM: 
        case SIGINT: 
            exit(); 
        break; 
    } 
} 

pcntl_signal(SIGTERM, 'sig_handler'); 
pcntl_signal(SIGINT, 'sig_handler'); 
*/

$daemonnum = "gosuslugi";
$sessiontable = "session_gosuslugi session";
$sourcefilter = " AND id=48";
$proxyfilter = ""; //" AND mod(id,10)+1=$daemonnum";
$sourceaccessfilter = ""; //" AND mod(sourceaccessid,10)+1=0";
$sessionfilter = ""; //" AND session.sourceid=48"; //" AND (proxyid IS NULL OR mod(session.proxyid,10)+1=$daemonnum)";

// Проверка на повторный запуск
$flock = fopen("daemon$daemonnum.lock", "w"); 
if (!($flock && flock($flock,LOCK_EX|LOCK_NB))) {
    die("Daemon $daemonnum already started\r\n");   
};

$daemonstarttime = microtime(true);
set_time_limit(600);
print_log("Daemon $daemonnum started");
// Подключаемся к базе данных
$db = db_connect($database);
if (!$db) {
    print_log("Connection to SQL server failed");
    sleep(5);
    die();
}
$sessions = array();
$wait = true;
// Удаляем новые сессии с зависшими капчами
db_execute($db,"delete from $sessiontable where sessionstatusid=1 AND statuscode<>'renew' AND starttime < SUBDATE(NOW(), INTERVAL 5 MINUTE) AND sourceid IN (SELECT id FROM source WHERE enabled=1)");
// Сбрасываем обновляемые сессии с зависшими капчами
db_execute($db,"update $sessiontable set sessionstatusid=7,lasttime=now(),statuscode='captchaerror' where sessionstatusid=1 AND statuscode='renew' AND lasttime < SUBDATE(NOW(), INTERVAL 5 MINUTE)");
db_execute($db,"update session set sessionstatusid=7,lasttime=now(),statuscode='captchaerror' where sessionstatusid=4 AND endtime IS NULL AND lasttime < SUBDATE(NOW(), INTERVAL 5 MINUTE)");
// Удаляем использованные сессии старше 1 часа
//db_execute($db,"delete from $sessiontable where sessionstatusid IN (3,4,5) AND endtime IS NOT NULL AND (endtime < SUBDATE(NOW(), INTERVAL 1 HOUR) OR captcha_service IS NULL)");
// Очищаем статистику прокси старше 1 дн
//db_execute($db,"delete from proxyusage where lasttime < SUBDATE(NOW(), INTERVAL 1 DAY)");
// Отвязываем сессии от просроченных и выполненных запросов
db_execute($db,"update $sessiontable set request_id=NULL WHERE request_id IS NOT NULL AND endtime IS NULL AND (SELECT COUNT(*) FROM RequestNew WHERE id=session.request_id AND status<>0)");

// Удаляем капчи с истекшим сроком
    $sql = <<<SQL
update $sessiontable SET captcha='',captchaimage=NULL,captcha_token='',statuscode='captchaexpired'
WHERE sessionstatusid IN (2,6,7) and (captcha>'' OR captchaimage>'')
AND sourceid IN (SELECT id from source WHERE captcha_time>0)
AND unix_timestamp(now())-unix_timestamp(captchatime)+$idle_time+1>=
(SELECT captcha_time from source WHERE id=session.sourceid)
$sessionfilter
SQL;
    db_execute($db,$sql);

// Меняем неактивные прокси в сессиях
    $sql = <<<SQL
update $sessiontable SET proxyid=(SELECT MIN(id) FROM proxy WHERE status=1 AND id>proxyid)
WHERE sessionstatusid IN (2,6,7)
AND sourceid IN (SELECT id FROM source WHERE status=1)
AND proxyid IN (SELECT id FROM proxy WHERE id>100 AND status=0)
$sessionfilter
SQL;
//    db_execute($db,$sql);
/*
// Разблокируем заблокированные логины
    $sql = <<<SQL
UPDATE sourceaccess SET unlocktime=null
WHERE unlocktime IS NOT NULL AND unlocktime<now()
SQL;
    db_execute($db,$sql);
*/
while ($wait)
{
// Разблокируем заблокированные сессии
    $sql = <<<SQL
update $sessiontable SET sessionstatusid=2,statuscode='unlocked'
WHERE sessionstatusid=6 AND unlocktime<now()
$sessionfilter
SQL;
    db_execute($db,$sql);
/*
// Завершаем просроченные сессии
    $sql = <<<SQL
update $sessiontable SET sessionstatusid=5,statuscode='ended',endtime=now()
WHERE sessionstatusid IN (2,6,7)
AND sourceid IN (SELECT id from source WHERE session_time>0)
AND unix_timestamp(now())-unix_timestamp(starttime)+$idle_time+1>=
(SELECT session_time from source WHERE id=session.sourceid)
$sessionfilter
SQL;
    db_execute($db,$sql);

// Стираем просроченные токены рекапчи
    $sql = <<<SQL
update $sessiontable SET sessionstatusid=7,lasttime=now(),statuscode='captchaexpired',captcha='',captcha_id=NULL
WHERE sessionstatusid IN (2,6) AND captcha>''
AND sourceid IN (SELECT id from source WHERE captcha_format IN ('recaptcha','v2ent','v3','hcaptcha','turnstile') AND captcha_check_method='')
AND unix_timestamp(now())-unix_timestamp(captchatime)>110
$sessionfilter
SQL;
    db_execute($db,$sql);

// Завершаем неактивные сессии
    $sql = <<<SQL
update $sessiontable SET sessionstatusid=5,statuscode='inactive',endtime=now()
WHERE sessionstatusid IN (2,6,7)
AND sourceid IN (SELECT id from source WHERE session_inactivity>0)
AND unix_timestamp(now())-unix_timestamp(lasttime)+$idle_time+1>=
(SELECT session_inactivity from source WHERE id=session.sourceid)
$sessionfilter
SQL;
    db_execute($db,$sql);

// Продлеваем сессии, которые скоро станут неактивными
    $sql = <<<SQL
SELECT session.*, source.code, source.name, source.url, source.ping_path, source.ping_token, source.ping_method, source.ping_header, source.ping_content, source.ping_regexp, source.form_path, source.form_token, source.form_header, source.form_regexp, source.logoff_path, source.useragent, source.codepage
FROM $sessiontable, source
WHERE session.sourceid=source.id
AND session.sessionstatusid IN (2,6,7)
AND source.session_inactivity>0
AND unix_timestamp(now())-unix_timestamp(session.lasttime)+60>=source.session_inactivity/2
UNION
SELECT session.*, source.code, source.name, source.url, source.ping2_path ping_path, '' ping_token, source.ping2_method ping_method, '' ping_header, '' ping_content, '' ping_regexp, '' form_path, '' form_token, '' form_header, '' form_regexp, source.logoff_path, source.useragent, source.codepage
FROM $sessiontable, source
WHERE session.sourceid=source.id
AND session.sessionstatusid IN (2,6,7)
AND source.session_inactivity>0
AND source.ping2_path<>''
AND unix_timestamp(now())-unix_timestamp(session.lasttime)+60>=source.session_inactivity/2
$sessionfilter
SQL;
    $result = $db->query($sql);
    while (($row = $result->fetch_object()) && (microtime(true)-$daemonstarttime < 300)) {
        $sessionid = $row->id;
        $sourceid = $row->sourceid;
        $code = $row->code;
        $name = $row->name;
        $useragent = $row->useragent==''?$http_agent:$row->useragent;
        $codepage = $row->codepage;
        $cookies = str_cookies($row->cookies);
        $url = $row->url;
        $logoff_path = $row->logoff_path;
        if ($row->ping_path) {
            $ping_url = (substr($row->ping_path,0,4)=='http' ? '' : ($row->server ? $row->server : $url)) . $row->ping_path;
            if ($row->ping_token)
                $ping_url .= (strpos($ping_url,'?')?'&':'?') . $row->ping_token . '=' . urlencode($row->token);
            $ping_regexp = $row->ping_regexp;
            $ping_method = $row->ping_method;
            $ping_header = $row->ping_header;
            $ping_content = $row->ping_content;
        } else {
            $ping_url = (substr($row->form_path,0,4)=='http' ? '' : ($row->server ? $row->server : $url)) . $row->form_path;
            if ($row->form_token)
                $ping_url .= (strpos($ping_url,'?')?'&':'?') . $row->form_token . '=' . urlencode($row->token);
            $ping_regexp = $row->form_regexp;
            $ping_method = 'GET';
            $ping_header = $row->form_header;
            $ping_content = '';
        }
        $get_options = array('http' => array(
            'method' => $ping_method,
            'timeout' => $http_timeout,
            'follow_location' => 0,
            'header' =>
                "User-Agent: $useragent\r\n" .
//                "Authorization: Bearer ".$row->token."\r\n" .
                ($ping_header?$ping_header."\r\n":"") .
                cookies_header($cookies),
        ),'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
//            'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT,
        ));
        if ($ping_method=='POST') {
            $get_options['http']['content'] = $ping_content;
            $get_options['http']['header'] .= 
//                                "X-Requested-With: XMLHttpRequest\r\n" .
//                                "Content-Type: application/x-www-form-urlencoded\r\n" .
                                "Content-Length: ".strlen($ping_content)."\r\n";
        }
        $get_context = stream_context_create($get_options);
        $ping = @file_get_contents($ping_url,false,$get_context);
        print_log("Pinging $ping_url, size: ".strlen($ping));

        $redirects = 0;
        $next_url = $ping_url;
        while (//!$ping && 
          isset($http_response_header) && sizeof($http_response_header)>0 && (strpos($http_response_header[0],'500')!=false || strpos($http_response_header[0],'307')!=false || strpos($http_response_header[0],'303')!=false || strpos($http_response_header[0],'302')!=false || strpos($http_response_header[0],'301')!=false) && (++$redirects<10) && (microtime(true)-$starttime < $max_seconds)) {
//            print_log("Header: ".implode($http_response_header,"\n"));
            $cookies = array_merge($cookies,parse_cookies($http_response_header));
//            print_log("Cookies: ".cookies_header($cookies));
            foreach($http_response_header as $line) {
                if (strpos($line,'Location:')!==false) {
                    $next_path = trim(substr($line,9));
                    $purl = parse_url($ping_url);
                    $server = $purl['scheme'].'://'.$purl['host'];
                    if (array_key_exists('port',$purl)) $server .= ':'.$purl['port'];
                    $next_url = (substr($next_path,0,4)=='http' ? '' : $server) . $next_path;
                    print_log("Ping redirect: $next_url ($code)");
                }
            }

            $get_options['http']['method'] = 'GET';
            $get_options['http']['header'] = 
                "Cache-Control: max-age=0\r\n" .
//                "Connection: keep-alive\r\n" .
                "User-Agent: $useragent\r\n" .
                cookies_header($cookies);
            $get_context = stream_context_create($get_options);
               
            $ping = @file_get_contents($next_url,false,$get_context);
            print_log("Getting $next_url, size: ".strlen($ping));
        }

        if ($codepage) $ping = iconv($codepage,'utf-8',$ping);
        if (!is_dir("logs/$code")) mkdir("logs/$code");
        file_put_contents("logs/$code/ping_$sessionid.htm",(isset($http_response_header)?implode("\n",$http_response_header):'')."\n\n".$ping);

        if (($ping_method=='POST' || $ping) && (!$logoff_path || strpos($ping, $logoff_path))) {
            if (!$ping_regexp || preg_match($ping_regexp, $ping)) {
                $cookies = array_merge($cookies,parse_cookies($http_response_header));
                $cookies_str = addslashes(cookies_str($cookies));
                print_log("Prolongated session $sessionid ($code)");
                $sql = <<<SQL
update $sessiontable SET cookies='$cookies_str',lasttime=now()
WHERE id=$sessionid
SQL;
                db_execute($db,$sql);
            } else {
                print_log("Invalid session $sessionid - not found ($code)");
                $sql = <<<SQL
update $sessiontable SET sessionstatusid=5,statuscode='invalid',endtime=now()
WHERE id=$sessionid
SQL;
                db_execute($db,$sql);
            }
        } elseif ($ping) {
            print_log("Invalid session $sessionid - ".($ping?"not logged":"empty")." ($code)\n");
            file_put_contents("logs/$code/notlogged_$sessionid.htm",(isset($http_response_header)?implode("\n",$http_response_header):'')."\n\n".$ping);
            if($ping) {
                $sql = <<<SQL
update $sessiontable SET sessionstatusid=5,statuscode='notlogged',endtime=now()
WHERE id=$sessionid
SQL;
                db_execute($db,$sql);
            }
        }
    }
*/
// Отменяем ненужное обновление сессий
// AND sourceid IN (SELECT id from source WHERE captcha_format IN ('recaptcha','v2ent','v3','hcaptcha','turnstile'))
    $sql = <<<SQL
update $sessiontable SET sessionstatusid=2,statuscode='',captchaimage=null
WHERE sessionstatusid IN (7)
AND lasttime<date_add(now(),interval -150 second)
$sessionfilter
ORDER BY session.lasttime
LIMIT 10
SQL;
    db_execute($db,$sql);

// Получаем новые капчи на обновляемых сессиях
    $sql = <<<SQL
SELECT session.*, 
source.code, source.name, source.url, source.useragent, source.codepage, source.form_path, source.captcha_path, source.captcha_path_regexp, source.captcha_token, source.captcha_token_regexp, source.captcha_action, source.captcha_minscore, 
source.captcha_format, source.captchatypeid, source.captcha_size, source.captcha_check_method, source.captcha_check_path, source.captcha_check_token_regexp, source.captcha_field, source.token_field, source.sid_field
FROM $sessiontable, source
WHERE session.sourceid=source.id
AND session.sessionstatusid IN (7)
AND session.sourceid NOT IN (SELECT sourceid FROM $sessiontable WHERE sessionstatusid=1 GROUP BY 1 HAVING count(*)>250)
AND (captcha_id IS NULL OR captcha_reporttime IS NOT NULL)
$sessionfilter
ORDER BY session.lasttime
LIMIT 50
SQL;
    $result = $db->query($sql);
    $starttime = microtime(true);
    while (($row = $result->fetch_object()) && (microtime(true)-$daemonstarttime < 300)) {
        $sessionid = $row->id;
        $cookies = str_cookies($row->cookies);
        $sourceid = $row->sourceid;
        $code = $row->code;
        $name = $row->name;

        print_log("Renewing session: $sessionid ($code)");
        $url = $row->url;
        $form_path = $row->form_path;
        $form_url = (substr($form_path,0,4)=='http' ? '' : $url) . $form_path;
        $captcha_image = $row->captchaimage;
        $captcha_path = $row->captcha_path;
        $captcha_path_regexp = $row->captcha_path_regexp;
        $captcha_token = $row->captcha_token;
        $captcha_token_regexp = $row->captcha_token_regexp;
        $captcha_action = $row->captcha_action;
        $captcha_minscore = $row->captcha_minscore;
        $captcha_format = $row->captcha_format;
        $captcha_type = $row->captchatypeid;
        $captcha_size = $row->captcha_size;
        $captcha_check_method = $row->captcha_check_method;
        $captcha_check_path = $row->captcha_check_path;
        $captcha_check_url = $captcha_check_path;
        if ($captcha_check_path) $captcha_check_url = (substr($captcha_check_path,0,4)=='http' ? '' : $url) . $captcha_check_path;
        $captcha_check_token_regexp = $row->captcha_check_token_regexp;
        $captcha_field = $row->captcha_field;
        $token_field = $row->token_field;
        $sid_field = $row->sid_field;

        $captcha_url = $captcha_path;
        $captcha = $captcha_image?base64_decode($captcha_image):false;
        $token = '';
        $params = array();

        $proxy = $row->proxyid;
        $proxy_auth = false;

        if ($captcha || $captcha_format=='recaptcha' || $captcha_format=='v2ent' || $captcha_format=='v3' || $captcha_format=='hcaptcha' || $captcha_format=='turnstile') {
            if ($proxy) {
                $sql = "SELECT * FROM proxy WHERE id=$proxy";
                $proxy_result = $db->query($sql);
                if ($proxy_result !== false && ($proxy_row = $proxy_result->fetch_object())) {
                    $proxyid = $proxy_row->id;
                    $proxy = 'tcp://'.$proxy_row->server.':'.$proxy_row->port;
                    if ($proxy_row->login)
                        $proxy_auth = base64_encode($proxy_row->login.':'.$proxy_row->password); 
                    else
                        $proxy_auth = false;
                } else {
                    $proxy = 0;
                    break;
                }
            }

            while ($captcha_url && !$captcha && (microtime(true)-$starttime < $max_seconds)) {
                $captcha_url = (substr($captcha_url,0,4)=='http' ? '' : $url) . $captcha_url;
                $get_options = array('http' => array(
                    'method' => 'GET',
                    'timeout' => $http_timeout,
                    'follow_location' => 0,
                    'header' =>
                        "Cache-Control: no-cache, no-store, must-revalidate\r\n" .
//                        "Connection: keep-alive\r\n" .
                        "User-Agent: $useragent\r\n" .
                        "Referer: $form_url\r\n" .
                        cookies_header($cookies),
                ),'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ));
                if ($proxy) {
                    $get_options['http']['proxy'] = $proxy;
//                    $get_options['http']['request_fulluri'] = true;
                    if ($proxy_auth)
                        $get_options['http']['header'] .= "Proxy-Authorization: Basic $proxy_auth\r\nAuthorization: Basic $proxy_auth\r\n";
                    print_log("Using proxy $proxyid $proxy ($code)");
                }
                $get_context = stream_context_create($get_options);

                if ($captcha_token && $token){
                    if (isset($cookies[$captcha_token])) {
                        $token = $cookies[$captcha_token];
                    }
                    $captcha_url = $captcha_url.'?'.$captcha_token.'='.$token;
                }

                $captcha = @file_get_contents($captcha_url,false,$get_context);
                print_log("Getting captcha: $captcha_url, size: ".strlen($captcha));
                $cookies = array_merge($cookies,parse_cookies($http_response_header));
                if ($captcha && strlen($captcha)>100) {
                    if (strpos($captcha,'<html')===false || strpos($captcha,'<html')>30) {
                        print_log("Captcha loaded successfully ($code)");
                    } else {
                        $captcha = "ERROR_NOT_IMAGE";
                        print_log("Captcha not loaded - html received ($code)");
                    }
                } elseif (isset($http_response_header) && sizeof($http_response_header)>0 && strpos($http_response_header[0],'302')!=false) {
                    $captcha = "ERROR_BAD_IMAGE";
                    foreach($http_response_header as $line) {
                        if (strpos($line,'Location:')!==false) {
                            $captcha_path = trim(substr($line,9));
                            $captcha_url = (substr($captcha_path,0,4)=='http' ? '' : $server) . $captcha_path;
//                            print_log("Captcha redirect: $captcha_url ($code)");
                            $captcha = '';
                        }
                    }
                } else {
                    $captcha = "ERROR_ZERO_IMAGE";
                    print_log("Captcha not loaded - answer or redirect expected ($code)");
                }

                if ($proxy) {
                    $success = $captcha && (substr($captcha,0,5)!="ERROR")?1:0;
                    db_execute($db,"INSERT INTO proxyusage (sourceid,proxyid,success) VALUES ($sourceid,$proxyid,$success)");
                    if ($success) {
                        db_execute($db,"UPDATE proxy SET used=used+1,lasttime=now(),success=success+1,successtime=now() WHERE id=$proxyid");
                    } else {
                        db_execute($db,"UPDATE proxy SET used=used+1,lasttime=now() WHERE id=$proxyid");
                        print_log("Proxy $proxyid $proxy failed ($code)");
                    }
                }

                if($captcha && (substr($captcha,0,5)!="ERROR") && ($row->captcha_format=='base64')) {
                    if ($captcha_token_regexp) {
                        if(preg_match($captcha_token_regexp, $captcha, $matches)){
                            $token = $matches[1];
                            print_log("Token $token found ($code)");
                        } else {
                            print_log("Token $captcha_token_regexp not found ($code)");
//                            print_log($form);
                            $captcha = false;
                        }
                    }

                    $prefix = 'data:image/jpeg;base64,';
                    $start = strpos($captcha,$prefix);
                    if($start!==false) {
                        $captcha = substr($captcha,$start+strlen($prefix));
                        $finish = strpos($captcha,'=');
                        if ($finish!==false) {
                            $captcha = base64_decode(substr($captcha,0,$finish+1));
                            print_log("Captcha decoded successfully ($code)");
                        } else {
                            $captcha ="ERROR_BASE64";
                            print_log("Captcha decoding error ($code)");
                        }
                    } else {
                        $captcha = "ERROR_BASE64";
                        print_log("Captcha decoding error ($code)");
                    }
                }

                if($captcha && (substr($captcha,0,5)!="ERROR") && ($row->captcha_format=='json')) {
                    $json = json_decode($captcha, true);
                    if ($token_field) {
                        if(is_array($json) && isset($json[$token_field])){
                            $captcha_token = $json[$token_field];
                            print_log("Token $captcha_token found ($code)");
                        } else {
                            print_log("Token $token_field not found ($code)");
                            $captcha = false;
                        }
                    }
                    if ($sid_field) {
                        if(is_array($json) && isset($json[$sid_field])){
                            $captcha_sid = $json[$sid_field];
                            print_log("SID $captcha_sid found ($code)");
                        } else {
                            print_log("SID $sid_field not found ($code)");
                            $captcha = false;
                        }
                    }
                    if($captcha_field) {
                        if(is_array($json) && isset($json[$captcha_field])){
                            $captcha = base64_decode($json[$captcha_field]);
                            print_log("Captcha decoded successfully ($code)");
                        } else {
                            $captcha ="ERROR_JSON";
                            print_log("Captcha decoding error ($code)");
                        }
                    }
                }
            }

            $cookies_str = addslashes(cookies_str($cookies));
            if (substr($captcha,0,5)=="ERROR") {
                $captcha = false;
                $captcha_format = false;
            }
            if ($captcha) $captcha_format = 'image';
//            print_log("Format $captcha_format for session: $sessionid ($code)");
            
            if ($captcha_format) {
                if (db_execute($db,"update $sessiontable SET lasttime=now(),endtime=NULL,sessionstatusid=1,statuscode='renew',".($captcha_image?"":"captchatime=now(),")."captcha_reporttime=NULL,captcha=''".($token?",token='$token'":"")." WHERE id=$sessionid")) {
                    print_log("New captcha for session: $sessionid ($code)");
                    if ($captcha) {
                        if (!is_dir("captcha/$code")) mkdir("captcha/$code");
                        $captcha_pic = "captcha/$code/__$sessionid.jpg";
                        file_put_contents($captcha_pic,$captcha);
//                        file_put_contents("captcha/$code/$sessionid.htm",$http_response_header."\n\n".$form);
                    }
                } else {
                    print_log("Session renewal failed ($code)");
                }                
                
                if ($captcha && isset($neuro_sources[$code])) {
                    $key = '';
                    $host = 'neuro';
                    $antigateid = neuro_post($captcha,$neuro_sources[$code].'decode'); // передаем на распознавание
                } elseif ($captcha) {
                    $key = $antigate_key;
                    $host = $antigate_host;
                    $antigateid = antigate_post($captcha,$key,false,$host,0,(int)($captcha_type==2),(int)($captcha_type==1),$captcha_size,$captcha_size?$captcha_size:99,(int)($captcha_type==3)); // передаем на распознавание
                } elseif ($captcha_format=='hcaptcha') {
                    $key = $hcaptcha_key;
                    $host = $hcaptcha_host;
                    $antigateid = captcha_create($captcha_format,false,$captcha_token,$form_url,$captcha_action,$captcha_minscore,$key,false,$host); // запрашиваем новый токен
                } elseif ($captcha_format=='v3') {
                    $key = $captchav3_key;
                    $host = $captchav3_host;
                    $antigateid = captcha_create($captcha_format,false,$captcha_token,$form_url,$captcha_action,$captcha_minscore,$key,false,$host); // запрашиваем новый токен
                } else {
                    $key = $captcha_key;
                    $host = $captcha_host;
                    $antigateid = captcha_create($captcha_format,false,$captcha_token,$form_url,$captcha_action,$captcha_minscore,$key,false,$host); // запрашиваем новый токен
                }

                if ($antigateid && (strpos($antigateid,'ERROR')===false)) {
                    $sessions[$sessionid] = array('sourceid'=>$sourceid,'code'=>$code,'captcha_format'=>$captcha_format,'captcha_type'=>$captcha_type,'captcha_size'=>$captcha_size,'cookies'=>$cookies,'antigatehost'=>$host,'antigatekey'=>$key,'antigateid'=>$antigateid,'starttime'=>microtime(true),'method'=>$captcha_check_method,'url'=>$captcha_check_url,'params'=>$params,'field'=>$captcha_field,'token_field'=>$token_field,'token'=>$token,'token_regexp'=>$captcha_check_token_regexp,'proxy'=>$proxy,'proxy_auth'=>$proxy_auth);
                    db_execute($db,"update $sessiontable SET captcha_service='".$host."'".($host<>'_neuro'?",captcha_id=$antigateid":",captcha_id=NULL")." WHERE id=$sessionid");
                    print_log("Captcha id from ".$host." - $antigateid ($code)");
                } else {
                    print_log("Failed sending captcha to ".$host." - $antigateid ($code)");
                    if ($captcha && isset($neuro_sources[$code])) {
                        $key = $antigate_key;
                        $host = $antigate_host;
                        $antigateid = antigate_post($captcha,$key,false,$host,0,(int)($captcha_type==2),(int)($captcha_type==1),$captcha_size,$captcha_size?$captcha_size:99,(int)($captcha_type==3)); // передаем на распознавание
                    } elseif ($captcha) {
                        $key = $antigate_key2;
                        $host = $antigate_host2;
                        $antigateid = antigate_post($captcha,$key,false,$host,0,(int)($captcha_type==2),(int)($captcha_type==1),$captcha_size,$captcha_size?$captcha_size:99,(int)($captcha_type==3)); // передаем на распознавание
                    } elseif ($captcha_format=='hcaptcha') {
                        $key = $hcaptcha_key2;
                        $host = $hcaptcha_host2;
                        $antigateid = captcha_create($captcha_format,false,$captcha_token,$form_url,$captcha_action,$captcha_minscore,$key,false,$host); // запрашиваем новый токен
                    } elseif ($captcha_format=='v3') {
                        $key = $captchav3_key2;
                        $host = $captchav3_host2;
                        $antigateid = captcha_create($captcha_format,false,$captcha_token,$form_url,$captcha_action,$captcha_minscore,$key,false,$host); // запрашиваем новый токен
                    } else {
                        $key = $captcha_key2;
                        $host = $captcha_host2;
                        $antigateid = captcha_create($captcha_format,false,$captcha_token,$form_url,$captcha_action,$captcha_minscore,$key,false,$host); // запрашиваем новый токен
                    }

                    if ($antigateid && (strpos($antigateid,'ERROR')===false)) {
                        $sessions[$sessionid] = array('sourceid'=>$sourceid,'code'=>$code,'captcha_format'=>$captcha_format,'captcha_type'=>$captcha_type,'captcha_size'=>$captcha_size,'cookies'=>$cookies,'antigatehost'=>$host,'antigatekey'=>$key,'antigateid'=>$antigateid,'starttime'=>microtime(true),'method'=>$captcha_check_method,'url'=>$captcha_check_url,'params'=>$params,'field'=>$captcha_field,'token_field'=>$token_field,'token'=>$token,'token_regexp'=>$captcha_check_token_regexp,'proxy'=>$proxy,'proxy_auth'=>$proxy_auth);
                        db_execute($db,"update $sessiontable SET captcha_service='".$host."',captcha_id=$antigateid WHERE id=$sessionid");
                        print_log("Captcha id from ".$host." - $antigateid ($code)");
                    } else {
                        print_log("Failed sending captcha to ".$host." - $antigateid ($code)");
                        db_execute($db,"update $sessiontable SET sessionstatusid=4,statuscode='failedcaptcha' WHERE id=$sessionid AND sessionstatusid=1");
                    }
                }
            }
        } else {
            if (db_execute($db,"update $sessiontable SET lasttime=now(),sessionstatusid=2,statuscode='',captchatime=NULL,captcha_service=NULL,captcha_id=NULL,captcha_reporttime=NULL,captcha='' WHERE id=$sessionid")) {
//                print_log("Renewed session: $sessionid ($code)");
            }
        }
    }

// Читаем список источников и количество недостающих сессий по ним
$sql = <<<SQL
SELECT
	*,
	GREATEST(IFNULL((SELECT count(sourceid) FROM $sessiontable WHERE sourceid=source.id AND sessionstatusid IN (3,4) AND endtime IS NOT NULL AND used IS NOT NULL AND endtime >= SUBDATE(NOW(), INTERVAL 5 MINUTE))/10,0),min_sessions)-
	IFNULL((SELECT count(sourceid) FROM $sessiontable WHERE sourceid=source.id AND (source.session_time=0 OR unix_timestamp(now())-unix_timestamp(starttime)<source.session_time) AND sessionstatusid IN (1,2,7)),0) count
FROM source
WHERE status=1 $sourcefilter
HAVING count>=1
ORDER BY count DESC
LIMIT 1
;
SQL;
    $result = $db->query($sql);
    while (($row = $result->fetch_object()) && (microtime(true)-$daemonstarttime < 300)) {

        $sourceid = $row->id;
        $code = $row->code;
        $name = $row->name;
        $count = intval($row->count);
        print_log("Sessions required: $count ($code)");

        $url = $row->url;
        $useragent = $row->useragent==''?$http_agent:$row->useragent;
        $codepage = $row->codepage;
        $proxy = $row->proxy;
        $proxygroup = $row->proxygroup;
        $proxy_sessions = $row->proxy_sessions;
        $login_form_path = $row->login_form_path;
        $login_form_url = (substr($login_form_path,0,4)=='http' ? '' : $url) . $login_form_path;
        $login_post_path = $row->login_post_path;
        $login_post_url = (substr($login_post_path,0,4)=='http' ? '' : $url) . $login_post_path;
        $login_field = $row->login_field;
        $password_field = $row->password_field;
        $other_fields = $row->other_fields;
        $auth_path = '';
        $login_locked_regexp = $row->login_locked_regexp;
        $auth_path_regexp = $row->auth_path_regexp;
        $logoff_path = $row->logoff_path;
        $form_path = $row->form_path;
        $form_url = $form_path=='' ? false : ((substr($form_path,0,4)=='http' ? '' : $url) . $form_path);
        $form_regexp = $row->form_regexp;
        $form_header = $row->form_header;
        $post_method = $row->post_method;
        $post_path = $row->post_path;
        $post_url = (substr($post_path,0,4)=='http' ? '' : $url) . $post_path;
        $captcha_path = $row->captcha_path;
        $captcha_path_regexp = $row->captcha_path_regexp;
        $captcha_token = $row->captcha_token;
        $captcha_token_regexp = $row->captcha_token_regexp;
        $captcha_action = $row->captcha_action;
        $captcha_minscore = $row->captcha_minscore;
        $captcha_format = $row->captcha_format;
        $captcha_type = $row->captchatypeid;
        $captcha_size = $row->captcha_size;
        $captcha_check_method = $row->captcha_check_method;
        $captcha_check_path = $row->captcha_check_path;
        $captcha_check_url = $captcha_check_path;
        if ($captcha_check_path) $captcha_check_url = (substr($captcha_check_path,0,4)=='http' ? '' : $url) . $captcha_check_path;
        $captcha_check_token_regexp = $row->captcha_check_token_regexp;
        $captcha_field = $row->captcha_field;
        $token_field = $row->token_field;
        $sid_field = $row->sid_field;

        if ($count>20 && sizeof($sessions)) $count=20;
        if ($count && $login_post_path) $count=1;
        if ($count>$max_sessions) $count = $max_sessions;
        print_log("Sessions will be created: $count ($code)");

        if ($captcha_check_url) {
            if (!$proxy && !$proxygroup) {
                $count = 1;
                foreach($sessions as $sessionid => &$s)
                    if ($s['code']==$code) $count = 0;
            }
        }

// Создаем новые сессии
        $starttime = microtime(true);
        while ($count-- > 0 && (microtime(true)-$starttime < $max_seconds)) {
$sql = <<<SQL
SELECT
	GREATEST(IFNULL((SELECT count(sourceid) FROM $sessiontable WHERE sourceid=source.id AND sessionstatusid IN (3,4) AND endtime IS NOT NULL AND used IS NOT NULL AND endtime >= SUBDATE(NOW(), INTERVAL 5 MINUTE))/10,0),min_sessions)-
	IFNULL((SELECT count(sourceid) FROM $sessiontable WHERE sourceid=source.id AND (source.session_time=0 OR unix_timestamp(now())-unix_timestamp(starttime)<source.session_time) AND sessionstatusid IN (1,2,7)),0) count
FROM source
WHERE id=$sourceid
;
SQL;
            if (db_select($db,$sql)===0) {
                $count = 0;
                break;
            }

            $server = '';
            $cookies = str_cookies($row->cookies);
            $auth_url = false;
            $login = false;
            $login_post = false;
            $form = false;
            $captcha_url = $captcha_path;
            $captcha = false;
            $token = '';
            $sourceaccessid = false;
            $params = array();

            $proxy = $row->proxy;
            $proxy_auth = false;

            if ($proxy==-1) { // Сначала проверяем есть ли сессия без прокси
$sql = <<<SQL
SELECT COUNT(*) count FROM $sessiontable WHERE sourceid=$sourceid AND endtime IS NULL AND proxyid IS NULL
;
SQL;
                $noproxy_result = $db->query($sql);
                if ($noproxy_row = $noproxy_result->fetch_object()) {
                    if ($noproxy_row->count==0) $proxy=0; // Можно создать сессию без прокси
                }
            }

            if ($proxy) {
// выбираем активные прокси, кроме тех, которые с этим источником за последний час успешно работали менее чем в 50% случаев
                $sql = "SELECT * FROM proxy"; 
                if ($proxy<0) { // Выбираем прокси, по которому еще нет сессии
                    $sql .= " WHERE status>0 AND enabled>0 AND id NOT IN (SELECT proxyid FROM proxysourcehourstats WHERE sourceid=$sourceid AND successrate<0.5)";
//                    if ($proxy_sessions) $sql .= " AND id not in (SELECT proxyid FROM $sessiontable WHERE sourceid=$sourceid AND endtime IS NULL AND proxyid IS NOT NULL)";
                    if ($proxy_sessions) $sql .= " AND (rotation>0 OR (SELECT COUNT(*) FROM $sessiontable WHERE proxyid=proxy.id AND sourceid=$sourceid AND sessionstatusid IN (1,2,6,7))<$proxy_sessions)";
                    if ($proxygroup) $sql .= " AND proxygroup=$proxygroup"; 
                    $sql .= $proxyfilter;
                } else {
                    $sql .= " WHERE id=$proxy";
                }
                $sql .= " ORDER BY lasttime LIMIT 1";
                $proxy_result = $db->query($sql);
                if ($proxy_result !== false && ($proxy_row = $proxy_result->fetch_object())) {
                    $proxyid = $proxy_row->id;
                    $proxy = 'tcp://'.$proxy_row->server.':'.$proxy_row->port;
                    if ($proxy_row->login)
                        $proxy_auth = base64_encode($proxy_row->login.':'.$proxy_row->password); 
                    else
                        $proxy_auth = false;
                    print_log("Selected proxy $proxyid $proxy ($code)");
                    db_execute($db,"UPDATE proxy SET lasttime=now() WHERE id=$proxyid");
                } else {
                    $proxy = 0;
                    $count = 0;
//                    print_log("Not enough proxies ($code)");
                    break;
                }
            }
            if ($login_post_path) {
$sql = <<<SQL
SELECT * FROM sourceaccess
WHERE sourceid=$sourceid AND status=1 AND (unlocktime IS NULL OR unlocktime<now()) AND unix_timestamp(now())-unix_timestamp(lasttime)>30
AND sourceaccessid not in (SELECT sourceaccessid FROM $sessiontable WHERE endtime IS NULL AND sourceaccessid IS NOT NULL)
$sourceaccessfilter
ORDER BY lasttime
;
SQL;
                $access_result = $db->query($sql);
                if ($access_row = $access_result->fetch_object()) {
                    $sourceaccessid = $access_row->sourceaccessid;
                    $login = $access_row->login;
                    $password = $access_row->password;
                    db_execute($db,"UPDATE sourceaccess SET lasttime=now() WHERE sourceaccessid=$sourceaccessid");

                    $params = array();
                    print_log("Log in as $login ($code)");

                    if ($login_form_path) {
                        $get_options = array('http' => array(
                            'method' => 'GET',
                            'timeout' => $http_timeout,
                            'follow_location' => 0,
                            'header' =>
                                "User-Agent: $useragent\r\n" .
                                "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n" .
                                "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3\r\n" .
                                "DNT: 1\r\n" .
                                "Connection: keep-alive\r\n" .
                                "Upgrade-Insecure-Requests: 1\r\n" .
                                cookies_header($cookies),
                        ),'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                        ));
                        if ($proxy) {
                            $get_options['http']['proxy'] = $proxy;
                            $get_options['http']['request_fulluri'] = true;
                            if ($proxy_auth)
                                $get_options['http']['header'] .= "Proxy-Authorization: Basic $proxy_auth\r\nAuthorization: Basic $proxy_auth\r\n";
                            print_log("Using proxy $proxyid $proxy ($code)");
                        }
                        $get_context = stream_context_create($get_options);
                        $login_form = @file_get_contents($login_form_url,false,$get_context);
                        print_log("Getting login form: $login_form_url, size: ".strlen($login_form));
//                        print_log($get_options['http']['header']);

                        $redirects = 0;
                        $next_url = $login_form_url;
                        while (/*!$login_form && */ isset($http_response_header) && sizeof($http_response_header)>0 && (strpos($http_response_header[0],'500')!=false || strpos($http_response_header[0],'307')!=false || strpos($http_response_header[0],'303')!=false || strpos($http_response_header[0],'302')!=false || strpos($http_response_header[0],'301')!=false) && (++$redirects<10) /*&& (microtime(true)-$starttime < $max_seconds)*/) {
//                            print_log("\n\n".(isset($http_response_header)?implode("\n",$http_response_header):''));
                            $cookies = array_merge($cookies,parse_cookies($http_response_header));
//                            print_log("Cookies: ".cookies_header($cookies));
                            foreach($http_response_header as $line) {
                                if (strpos($line,'Location:')!==false) {
                                    $next_path = trim(substr($line,9));
                                    $purl = parse_url($next_url);
                                    $server = $purl['scheme'].'://'.$purl['host'];
                                    if (array_key_exists('port',$purl)) $server .= ':'.$purl['port'];
                                    $next_url = (substr($next_path,0,4)=='http' ? '' : $server) . $next_path;
                                    print_log("Login form redirect: $next_url ($code)");
                                }
                            }

                            $get_options['http']['header'] = 
                                "User-Agent: $useragent\r\n" .
                                "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n" .
                                "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3\r\n" .
                                "DNT: 1\r\n" .
                                "Connection: keep-alive\r\n" .
                                "Upgrade-Insecure-Requests: 1\r\n" .
                                cookies_header($cookies);
                            if ($proxy && $proxy_auth) {
                                $get_options['http']['header'] .= "Proxy-Authorization: Basic $proxy_auth\r\nAuthorization: Basic $proxy_auth\r\n";
                            }
                            $get_context = stream_context_create($get_options);
                   
                            $login_form = @file_get_contents($next_url,false,$get_context);
                            print_log("Getting login form: $next_url, size: ".strlen($login_form));
//                            print_log($get_options['http']['header']);
                        }

                        if ($login_form) {
                            $cookies = array_merge($cookies,parse_cookies($http_response_header));
//                            print_log("Cookies: ".cookies_header($cookies));
                            if ($codepage) $login_form = iconv($codepage,'utf-8',$login_form);
                            if (!is_dir("logs/$code")) mkdir("logs/$code");
                            file_put_contents("logs/$code/login_form.htm",(isset($http_response_header)?implode("\n",$http_response_header):'')."\n\n".$login_form);
                            if (preg_match_all("/<input[^>]+name=\"([^\"]+)[^>]+value=\"([^\"]+)[^>]+>/",$login_form,$matches)) {
                                foreach ($matches[1] as $i => $v) {
                                    if (!isset($params[$v])) {
                                        $params[$v] = $matches[2][$i];
                                        print_log("Parameter $v = ".$params[$v]);
                                    }
                                }
                            }
                            if (preg_match_all("/<input[^>]+value=\"([^\"]+)[^>]+name=\"([^\"]+)[^>]+>/",$login_form,$matches)) {
                                foreach ($matches[1] as $i => $v) {
                                    if (!isset($params[$matches[2][$i]])) {
                                        $params[$matches[2][$i]] = $v;
                                        print_log("Parameter ".$matches[2][$i]." = ".$v);
                                    }
                                }
                            }
                        }

                        if ($proxy) {
                            $success = $login_form?1:0;
                            db_execute($db,"INSERT INTO proxyusage (sourceid,proxyid,success) VALUES ($sourceid,$proxyid,$success)");
                            if ($success) {
                                db_execute($db,"UPDATE proxy SET used=used+1,lasttime=now(),success=success+1,successtime=now() WHERE id=$proxyid");
                            } else {
                                db_execute($db,"UPDATE proxy SET used=used+1,lasttime=now() WHERE id=$proxyid");
                                print_log("Proxy $proxyid $proxy failed ($code)");
                            }
                        }
                    }

                    if (!$login_form_path || $login_form) {
                        $params[$login_field] = $login;
                        $params[$password_field] = $password;
                        $post_data = http_build_query($params);
//                        $post_data = $login_field.'='.$login.'&'.$password_field.'='.$password;
                        if ($other_fields) $post_data .= '&'.$other_fields;
                        $post_options = array('http' => array(
                            'method' => 'POST',
                            'content' => $post_data,
                            'timeout' => $http_timeout,
                            'follow_location' => 0,
                            'header' =>
                                "Content-Type: application/x-www-form-urlencoded\r\n" .
                                "Content-Length: ".strlen($post_data)."\r\n" .
                                "X-Requested-With: XMLHttpRequest\r\n" .
                                "Cache-Control: no-cache, no-store, must-revalidate\r\n" .
//                                "Connection: keep-alive\r\n" .
                                "User-Agent: $useragent\r\n" .
                                "Origin: $url\r\n" .
                                "Referer: $login_form_url\r\n" .
                                cookies_header($cookies),
                        ),'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
//                            'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT,
                        ));
                        if ($proxy) {
                            $post_options['http']['proxy'] = $proxy;
                            $post_options['http']['request_fulluri'] = true;
                            if ($proxy_auth)
                                $post_options['http']['header'] .= "Proxy-Authorization: Basic $proxy_auth\r\nAuthorization: Basic $proxy_auth\r\n";
                            print_log("Using proxy $proxyid $proxy ($code)");
                        }
                        $post_context = stream_context_create($post_options);
                        $login_post = @file_get_contents($login_post_url,false,$post_context);
                        print_log("Posting: $login_post_url, size: ".strlen($login_post));
                        file_put_contents("logs/$code/login_$login.htm",(isset($http_response_header)?implode("\n",$http_response_header):'')."\n\n".$login_post);

                        $redirects = 0;
                        $next_url = $login_post_url;
                        while (/*!$login_post && */ isset($http_response_header) && sizeof($http_response_header)>0 && (strpos($http_response_header[0],'500')!=false || strpos($http_response_header[0],'307')!=false || strpos($http_response_header[0],'303')!=false || strpos($http_response_header[0],'302')!=false || strpos($http_response_header[0],'301')!=false) && (++$redirects<10) /*&& (microtime(true)-$starttime < $max_seconds)*/) {
                            $cookies = array_merge($cookies,parse_cookies($http_response_header));
                            foreach($http_response_header as $line) {
                                if (strpos($line,'Location:')!==false) {
                                    $next_path = trim(substr($line,9));
                                    $purl = parse_url($login_post_url);
                                    $server = $purl['scheme'].'://'.$purl['host'];
                                    if (array_key_exists('port',$purl)) $server .= ':'.$purl['port'];
                                    $next_url = (substr($next_path,0,4)=='http' ? '' : $server) . $next_path;
                                    print_log("Login redirect: $next_url ($code)");
                                }
                            }

                            $get_options = array('http' => array(
                                'timeout' => $http_timeout,
                                'follow_location' => 0,
                                'header' =>
                                    "User-Agent: $useragent\r\n" .
                                    "Referer: $login_form_url\r\n" .
                                    cookies_header($cookies),
                            ),'ssl' => array(
                                'verify_peer' => false,
                                'verify_peer_name' => false,
//                                'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT,
                            ));
                            if ($proxy) {
                                $get_options['http']['proxy'] = $proxy;
                                $get_options['http']['request_fulluri'] = true;
                                if ($proxy_auth)
                                    $get_options['http']['header'] .= "Proxy-Authorization: Basic $proxy_auth\r\nAuthorization: Basic $proxy_auth\r\n";
                            }
                            $get_context = stream_context_create($get_options);
                            $login_post = @file_get_contents($next_url,false,$get_context);
                        }
                    }
                } else {
//                    print_log("Not enough accounts ($code)");
                    $count = 0;
                    $auth_path = false;
                    $auth_url = false;
                    $form_path = false;
                    $form_url = false;
                    break;
                }

                if ($login_post) {
                    if ($codepage) $login_post = iconv($codepage,'utf-8',$login_post);
                    file_put_contents("logs/$code/logged_$login.htm",(isset($http_response_header)?implode("\n",$http_response_header):'')."\n\n".$login_post);
                    $cookies = array_merge($cookies,parse_cookies($http_response_header));
//                    print_log("Cookies: ".cookies_header($cookies));
/*!!!!!*/
                    if ($captcha_path_regexp) {
                        if(preg_match($captcha_path_regexp, $login_post, $matches)){
                            $captcha_url .= $matches[1];
                            $captcha = false;
                            print_log("Captcha URL: $captcha_url ($code)");
                        }
                    }

                    if ($captcha_token_regexp) {
                        if(preg_match($captcha_token_regexp, $login_post, $matches)){
                            $token = $matches[1];
                            print_log("Token $token found ($code)");
                        } else {
                            $captcha_url = false;
                        }
                    }
/*!!!!!*/
                    if ($login_locked_regexp && preg_match($login_locked_regexp, $login_post, $matches)){
                        print_log("User $login is locked for 2 hours ($code)");
                        db_execute($db,"UPDATE sourceaccess SET unlocktime=date_add(now(),interval 2 hour) WHERE sourceaccessid=$sourceaccessid");
                    }
                    if($auth_path_regexp) {
                        if(preg_match($auth_path_regexp, $login_post, $matches)){
                            $auth_path = $matches[1];
                            print_log("Authentication path: $auth_path ($code)");
                            if (substr($auth_path,0,4)=='http') {
                                $auth_url = $auth_path;
                                $purl = parse_url($auth_url);
                                $server = $purl['scheme'].'://'.$purl['host'];
                                if (array_key_exists('port',$purl)) $server .= ':'.$purl['port'];
//                                if ($server!=$url) $cookies = array();
                                $form_url = (substr($form_path,0,4)=='http' ? '' : $server) . $form_path;
                            } else {
                                $auth_url = $url . $auth_path;
                                $server = $url;
                            }
                        } else {
                            $auth_path = false;
                            $auth_url = false;
                            $form_path = false;
                            $form_url = false;
                            print_log("Authentication path not found ($code)");
                        }
                    }
                } else {
                    $auth_path = false;
                    $auth_url = false;
                    $form_path = false;
                    $form_url = false;
                    $count = 0;
                    break;
                }
            }
            while ($auth_url) {
                $get_options = array('http' => array(
                    'method' => 'GET',
                    'timeout' => $http_timeout,
                    'follow_location' => 0,
//                    'max_redirects' => 10,
                    'header' =>
//                        "X-Requested-With: XMLHttpRequest\r\n" .
//                        "Cache-Control: no-cache, no-store, must-revalidate\r\n" .
//                        "Connection: keep-alive\r\n" .
                        "User-Agent: $useragent\r\n" .
//                        "Referer: $login_form_url\r\n" .
//                        "Upgrade-Insecure-Requests: 1\r\n" .
                        cookies_header($cookies),
                ),'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ));
                if ($proxy) {
                    $get_options['http']['proxy'] = $proxy;
                    $get_options['http']['request_fulluri'] = true;
                    if ($proxy_auth)
                        $get_options['http']['header'] .= "Proxy-Authorization: Basic $proxy_auth\r\nAuthorization: Basic $proxy_auth\r\n";
                    print_log("Using proxy $proxyid $proxy ($code)");
                }
                $get_context = stream_context_create($get_options);
                $auth = @file_get_contents($auth_url,false,$get_context);
                print_log("Getting: $auth_url, size: ".strlen($auth));
                $cookies = array_merge($cookies,parse_cookies($http_response_header));
                if ($auth) {
                    if ($codepage) $auth = iconv($codepage,'utf-8',$auth);
                    file_put_contents("logs/$code/auth_$login.htm",(isset($http_response_header)?implode("\n",$http_response_header):'')."\n\n".$auth);

                    if($logoff_path && strpos($auth, $logoff_path)==false){
                        $auth_url = false;
                        $auth_path = false;
                        $form_url = false;
                        $form_path = false;
                        print_log("Authentification failed - logoff not found ($code)");
                    } else {
                        $auth_url = false;
                    }
                } elseif (isset($http_response_header) && sizeof($http_response_header)>0 && strpos($http_response_header[0],'302')!=false) {
                    $auth_url = false;
                    $auth_path = false;
                    foreach($http_response_header as $line) {
                        if (strpos($line,'Location:')!==false) {
                            $auth_path = trim(substr($line,9));
                            $auth_url = (substr($auth_path,0,4)=='http' ? '' : $server) . $auth_path;
//                            print_log("Authentication redirect: $auth_url ($code)");
                        }
                    }
                } else {
                    $auth_url = false;
                    $auth_path = false;
                    $form_url = false;
                    $form_path = false;
                    print_log("Authentification failed - answer or redirect expected ($code)");
                }
            }
            if ($form_url) {
                $params = array();
                $get_options = array('http' => array(
                    'method' => 'GET',
                    'timeout' => $http_timeout,
                    'follow_location' => 0,
                    'header' =>
                        "User-Agent: $useragent\r\n" .
                        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8\r\n" .
//                        "Accept-Encoding: identity\r\n" .
                        "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3\r\n" .
                        "DNT: 1\r\n" .
                        "Connection: keep-alive\r\n" .
                        "Upgrade-Insecure-Requests: 1\r\n" .
                        "Sec-Fetch-Dest: document\r\n" .
                        "Sec-Fetch-Mode: navigate\r\n" .
                        "Sec-Fetch-Site: none\r\n" .
                        "Sec-Fetch-User: ?1\r\n" .
                        ($form_header?$form_header."\r\n":"") .
                        cookies_header($cookies),
                ),'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ));
                if ($proxy) {
                    $get_options['http']['proxy'] = $proxy;
                    $get_options['http']['request_fulluri'] = true;
                    if ($proxy_auth)
                        $get_options['http']['header'] .= "Proxy-Authorization: Basic $proxy_auth\r\nAuthorization: Basic $proxy_auth\r\n";
                    print_log("Using proxy $proxyid $proxy ($code)");
                }
                $get_context = stream_context_create($get_options);
                $form = @file_get_contents($form_url,false,$get_context);
                print_log("Getting form: $form_url, size: ".strlen($form));
                if (!is_dir("logs/$code")) mkdir("logs/$code");
                file_put_contents("logs/$code/form".($login?"_".$login:"").".htm",(isset($http_response_header)?implode("\n",$http_response_header):'')."\n\n".$form);

                $redirects = 0;
                $next_url = $form_url;
                while (/*!$form &&*/ isset($http_response_header) && sizeof($http_response_header)>0 && (strpos($http_response_header[0],'500')!=false || strpos($http_response_header[0],'403')!=false || strpos($http_response_header[0],'307')!=false || strpos($http_response_header[0],'303')!=false || strpos($http_response_header[0],'302')!=false || strpos($http_response_header[0],'301')!=false) && (++$redirects<10) && (microtime(true)-$starttime < $max_seconds)) {
//                    print_log("Header: ".implode($http_response_header,"\n"));
                    $cookies = array_merge($cookies,parse_cookies($http_response_header));
//                    print_log("Cookies: ".cookies_header($cookies));
                    foreach($http_response_header as $line) {
                        if (strpos($line,'Location:')!==false) {
                            $next_path = trim(substr($line,9));
                            $purl = parse_url($form_url);
                            $server = $purl['scheme'].'://'.$purl['host'];
                            if (array_key_exists('port',$purl)) $server .= ':'.$purl['port'];
                            $next_url = (substr($next_path,0,4)=='http' ? '' : $server) . $next_path;
                            print_log("Form redirect: $next_url ($code)");
                        }
                    }

                    $get_options['http']['header'] = 
                        "User-Agent: $useragent\r\n" .
                        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n" .
                        "Accept-Encoding: identity\r\n" .
                        "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3\r\n" .
                        "DNT: 1\r\n" .
                        "Connection: keep-alive\r\n" .
                        "Upgrade-Insecure-Requests: 1\r\n" .
                        ($form_header?$form_header."\r\n":"") .
                        cookies_header($cookies);
                    if ($proxy && $proxy_auth) {
                        $get_options['http']['header'] .= "Proxy-Authorization: Basic $proxy_auth\r\nAuthorization: Basic $proxy_auth\r\n";
                    }
                    $get_context = stream_context_create($get_options);
                   
                    $form = @file_get_contents($next_url,false,$get_context);
                    print_log("Getting form: $next_url, size: ".strlen($form));
                }

                if ($proxy) {
                    $success = $form?1:0;
                    db_execute($db,"INSERT INTO proxyusage (sourceid,proxyid,success) VALUES ($sourceid,$proxyid,$success)");
                    if ($success) {
                        db_execute($db,"UPDATE proxy SET used=used+1,lasttime=now(),success=success+1,successtime=now() WHERE id=$proxyid");
                    } else {
                        db_execute($db,"UPDATE proxy SET used=used+1,lasttime=now() WHERE id=$proxyid");
                        print_log("Proxy $proxyid $proxy failed ($code)");
                        $post_path = false;
                    }
                }

                if ($form) {
                    if ($codepage) $form = iconv($codepage,'utf-8',$form);
                    file_put_contents("logs/$code/form".($login?"_".$login:"").".htm",(isset($http_response_header)?implode("\n",$http_response_header):'')."\n\n".$form);
                    $cookies = array_merge($cookies,parse_cookies($http_response_header));
//                    print_log("Cookies: ".cookies_header($cookies));

                    if (preg_match_all("/<input[^>]+name=\"([^\"]+)[^>]+value=\"([^\"]+)[^>]+>/",$form,$matches)) {
                        foreach ($matches[1] as $i => $v) {
                            if (!isset($params[$v])) {
                                $params[$v] = $matches[2][$i];
                                print_log("Parameter $v = ".$params[$v]);
                            }
                        }
                    }
                    if (preg_match_all("/<input[^>]+value=\"([^\"]+)[^>]+name=\"([^\"]+)[^>]+>/",$form,$matches)) {
                        foreach ($matches[1] as $i => $v) {
                            if (!isset($params[$matches[2][$i]])) {
                                $params[$matches[2][$i]] = $v;
                                print_log("Parameter ".$matches[2][$i]." = ".$v);
                            }
                        }
                    }

                    if ($captcha_path_regexp) {
                        if(preg_match($captcha_path_regexp, $form, $matches)){
                            $captcha_url .= $matches[1];
                            $captcha = false;
                            print_log("Captcha URL: $captcha_url ($code)");
                        } else {
//                            $captcha_url = false;
                            print_log("Captcha path $captcha_path_regexp not found ($code)");
                            file_put_contents("logs/$code/nocaptcha_".($login?"_".$login:"").".htm",(isset($http_response_header)?implode("\n",$http_response_header):'')."\n\n".$form);
//                            print_log($form);
                        }
                    }

                    if ($captcha_token_regexp) {
                        if(preg_match($captcha_token_regexp, $form, $matches)){
                            $token = $matches[1];
                            print_log("Token $token found ($code)");
                        } else {
                            print_log("Token $captcha_token_regexp not found ($code)");
//                            print_log($form);
                            $form = false;
                        }
                    }
                }

                if ($form && (!$logoff_path || strpos($form, $logoff_path)) && (!$form_regexp || preg_match($form_regexp, $form))){
//
                } elseif ($login_post) {
                    $auth_url = false;
                    $auth_path = false;
                    print_log("Logoff path, token or form not found, user $login is locked for 2 hours ($code)");
                    db_execute($db,"UPDATE sourceaccess SET unlocktime=date_add(now(),interval 2 hour) WHERE sourceaccessid=$sourceaccessid");
                }
            }

            if ($post_path) {
                $params = array();
                $post_data = http_build_query($params);
                $post_options = array('http' => array(
                    'method' => 'POST',
                    'content' => $post_data,
                    'timeout' => $http_timeout,
                    'header' =>
                        "Content-Type: application/x-www-form-urlencoded\r\n" .
                        "Content-Length: ".strlen($post_data)."\r\n" .
                        "X-Requested-With: XMLHttpRequest\r\n" .
                        "Cache-Control: no-cache, no-store, must-revalidate\r\n" .
//                        "Connection: keep-alive\r\n" .
                        "User-Agent: $useragent\r\n" .
                        "Origin: $url\r\n" .
                        "Referer: $form_url\r\n" .
                        cookies_header($cookies),
                ),'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ));
                if ($proxy) {
                    $post_options['http']['proxy'] = $proxy;
                    $post_options['http']['request_fulluri'] = true;
                    if ($proxy_auth)
                        $post_options['http']['header'] .= "Proxy-Authorization: Basic $proxy_auth\r\nAuthorization: Basic $proxy_auth\r\n";
                    print_log("Using proxy $proxyid $proxy ($code)");
                }
                $post_context = stream_context_create($post_options);
                $post = @file_get_contents($post_url,false,$post_context);
                print_log("Posting: $post_url, size: ".strlen($post));
                file_put_contents("logs/$code/post".($login?"_".$login:"").".htm",(isset($http_response_header)?implode("\n",$http_response_header):'')."\n\n".$post);
                $cookies = array_merge($cookies,parse_cookies($http_response_header));
            }

/*!!!!!*/
            while (($form || $login_post) && $captcha_url && !$captcha /*&& (microtime(true)-$starttime < $max_seconds)*/) {
/*!!!!!*/
                $captcha_url = (substr($captcha_url,0,4)=='http' ? '' : $url) . $captcha_url;
                $get_options = array('http' => array(
                    'method' => 'GET',
                    'timeout' => $http_timeout,
                    'follow_location' => 0,
                    'header' =>
/*
                        "Cache-Control: no-cache, no-store, must-revalidate\r\n" .
//                        "Connection: keep-alive\r\n" .
                        "User-Agent: $useragent\r\n" .
*/
                        "User-Agent: $useragent\r\n" .
                        "Accept: */*\r\n" .
//                        "Accept-Encoding: identity\r\n" .
                        "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3\r\n" .
                        "DNT: 1\r\n" .
                        "Connection: keep-alive\r\n" .
                        "Referer: $form_url\r\n" .
                        cookies_header($cookies) .
                        "Upgrade-Insecure-Requests: 1\r\n" .
                        "Sec-Fetch-Dest: script\r\n" .
                        "Sec-Fetch-Mode: no-cors\r\n" .
                        "Sec-Fetch-Site: same-site\r\n" .
                        ($form_header?$form_header."\r\n":"")
                ),'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ));
                if ($proxy) {
                    $get_options['http']['proxy'] = $proxy;
//                    $get_options['http']['request_fulluri'] = true;
                    if ($proxy_auth)
                        $get_options['http']['header'] .= "Proxy-Authorization: Basic $proxy_auth\r\nAuthorization: Basic $proxy_auth\r\n";
                    print_log("Using proxy $proxyid $proxy ($code)");
                }
                $get_context = stream_context_create($get_options);

                if ($captcha_token && $token){
                    if (isset($cookies[$captcha_token])) {
                        $token = $cookies[$captcha_token];
                    }
                    $captcha_url = $captcha_url.'?'.$captcha_token.'='.$token;
                }

                $captcha = @file_get_contents($captcha_url,false,$get_context);
                print_log("Getting captcha: $captcha_url, size: ".strlen($captcha));
                $cookies = array_merge($cookies,parse_cookies($http_response_header));
                if ($captcha && strlen($captcha)>100) {
                    if (strpos($captcha,'<html')===false || strpos($captcha,'<html')>30) {
                        print_log("Captcha loaded successfully");
                    } else {
                        $captcha = "ERROR_NOT_IMAGE";
                        print_log("Captcha not loaded - html received ($code)");
                    }
                } elseif (isset($http_response_header) && sizeof($http_response_header)>0 && strpos($http_response_header[0],'302')!=false) {
                    $captcha = "ERROR_BAD_IMAGE";
                    foreach($http_response_header as $line) {
                        if (strpos($line,'Location:')!==false) {
                            $captcha_path = trim(substr($line,9));
                            $captcha_url = (substr($captcha_path,0,4)=='http' ? '' : $server) . $captcha_path;
//                            print_log("Captcha redirect: $captcha_url ($code)");
                            $captcha = '';
                        }
                    }
                } else {
                    $captcha = "ERROR_ZERO_IMAGE";
                    print_log("Captcha not loaded - answer or redirect expected ($code)");
                }

                if ($proxy) {
                    $success = $captcha && (substr($captcha,0,5)!="ERROR")?1:0;
                    db_execute($db,"INSERT INTO proxyusage (sourceid,proxyid,success) VALUES ($sourceid,$proxyid,$success)");
                    if ($success) {
                        db_execute($db,"UPDATE proxy SET used=used+1,lasttime=now(),success=success+1,successtime=now() WHERE id=$proxyid");
                    } else {
                        db_execute($db,"UPDATE proxy SET used=used+1,lasttime=now() WHERE id=$proxyid");
                        print_log("Proxy $proxyid $proxy failed ($code)");
                    }
                }

                if($captcha && (substr($captcha,0,5)!="ERROR") && ($row->captcha_format=='base64')) {
                    if ($captcha_token_regexp) {
                        if(preg_match($captcha_token_regexp, $captcha, $matches)){
                            $token = $matches[1];
                            print_log("Token $token found ($code)");
                        } else {
                            print_log("Token $captcha_token_regexp not found ($code)");
//                            print_log($form);
                            $captcha = false;
                        }
                    }

                    $prefix = 'data:image/jpeg;base64,';
                    $start = strpos($captcha,$prefix);
                    if($start!==false) {
                        $captcha = substr($captcha,$start+strlen($prefix));
                        $finish = strpos($captcha,'=');
                        if ($finish!==false) {
                            $captcha = base64_decode(substr($captcha,0,$finish+1));
                            print_log("Captcha decoded successfully ($code)");
                        } else {
                            $captcha ="ERROR_BASE64";
                            print_log("Captcha decoding error ($code)");
                        }
                    } else {
                        $captcha = "ERROR_BASE64";
                        print_log("Captcha decoding error ($code)");
                    }
                }

                if($captcha && (substr($captcha,0,5)!="ERROR") && ($row->captcha_format=='json')) {
                    $json = json_decode($captcha, true);
                    if ($token_field) {
                        if(is_array($json) && isset($json[$token_field])){
                            $captcha_token = $json[$token_field];
                            print_log("Token $captcha_token found ($code)");
                        } else {
                            print_log("Token $token_field not found ($code)");
                            $captcha = false;
                        }
                    }
                    if ($sid_field) {
                        if(is_array($json) && isset($json[$sid_field])){
                            $captcha_sid = $json[$sid_field];
                            print_log("SID $captcha_sid found ($code)");
                        } else {
                            print_log("SID $sid_field not found ($code)");
                            $captcha = false;
                        }
                    }
                    if($captcha_field) {
                        if(is_array($json) && isset($json[$captcha_field])){
                            $captcha = base64_decode($json[$captcha_field]);
                            print_log("Captcha decoded successfully ($code)");
                        } else {
                            $captcha ="ERROR_JSON";
                            print_log("Captcha decoding error ($code)");
                        }
                    }
                }
            }

            $cookies_str = addslashes(cookies_str($cookies));
            if (substr($captcha,0,5)=="ERROR") {
                $captcha = false;
                $captcha_format = false;
            }
            if ($captcha) $captcha_format = 'image';

            if (($form || $login_post) && $captcha_format) {
                if (db_execute($db,"insert into $sessiontable (sourceid,cookies,starttime,lasttime,sessionstatusid,captcha,token,server,sourceaccessid,proxyid) VALUES ($sourceid,'$cookies_str',now(),now(),1,'','$token','$server',".($sourceaccessid?$sourceaccessid:'NULL').",".($proxy?"'".$proxyid."'":'NULL').")")) {
                    $sessionid = $db->insert_id;
                    print_log("Created captcha session: $sessionid ($code)");
                    if ($captcha) {
                        if (!is_dir("captcha/$code")) mkdir("captcha/$code");
                        $captcha_pic = "captcha/$code/__$sessionid.jpg";
                        file_put_contents($captcha_pic,$captcha);
//                        file_put_contents("captcha/$code/$sessionid.htm",$http_response_header."\n\n".$form);
                    }
                } else {
                    print_log("Session insert failed ($code)\n"."insert into $sessiontable (sourceid,cookies,starttime,lasttime,sessionstatusid,token) VALUES ($sourceid,'$cookies_str',now(),now(),1,'$token')");
                }                
                
                if ($captcha && isset($neuro_sources[$code])) {
                    $key = '';
                    $host = 'neuro';
                    $antigateid = neuro_post($captcha,$neuro_sources[$code].'decode'); // передаем на распознавание
                } elseif ($captcha) {
                    $key = $antigate_key;
                    $host = $antigate_host;
                    $antigateid = antigate_post($captcha,$key,false,$host,0,(int)($captcha_type==2),(int)($captcha_type==1),$captcha_size,$captcha_size?$captcha_size:99,(int)($captcha_type==3)); // передаем на распознавание
                } elseif ($captcha_format=='hcaptcha') {
                    $key = $hcaptcha_key;
                    $host = $hcaptcha_host;
                    $antigateid = captcha_create($captcha_format,false,$captcha_token,$form_url,$captcha_action,$captcha_minscore,$key,false,$host); // запрашиваем новый токен
                } elseif ($captcha_format=='v3') {
                    $key = $captchav3_key;
                    $host = $captchav3_host;
                    $antigateid = captcha_create($captcha_format,false,$captcha_token,$form_url,$captcha_action,$captcha_minscore,$key,false,$host); // запрашиваем новый токен
                } else {
                    $key = $captcha_key;
                    $host = $captcha_host;
                    $antigateid = captcha_create($captcha_format,false,$captcha_token,$form_url,$captcha_action,$captcha_minscore,$key,false,$host); // запрашиваем новый токен
                }

                if ($antigateid && (strpos($antigateid,'ERROR')===false)) {
                    $sessions[$sessionid] = array('sourceid'=>$sourceid,'code'=>$code,'captcha_format'=>$captcha_format,'captcha_type'=>$captcha_type,'captcha_size'=>$captcha_size,'cookies'=>$cookies,'antigatehost'=>$host,'antigatekey'=>$key,'antigateid'=>$antigateid,'starttime'=>microtime(true),'method'=>$captcha_check_method,'url'=>$captcha_check_url,'params'=>$params,'field'=>$captcha_field,'token_field'=>$token_field,'token'=>$token,'token_regexp'=>$captcha_check_token_regexp,'proxy'=>$proxy,'proxy_auth'=>$proxy_auth);
                    db_execute($db,"update $sessiontable SET captcha_service='".$host."'".($host<>'_neuro'?",captcha_id=$antigateid":",captcha_id=NULL")." WHERE id=$sessionid");
                    print_log("Captcha id from ".$host." - $antigateid ($code)");
                } else {
                    print_log("Failed sending captcha to ".$host." - $antigateid ($code)");
                    if ($captcha && isset($neuro_sources[$code])) {
                        $key = $antigate_key;
                        $host = $antigate_host;
                        $antigateid = antigate_post($captcha,$key,false,$host,0,(int)($captcha_type==2),(int)($captcha_type==1),$captcha_size,$captcha_size?$captcha_size:99,(int)($captcha_type==3)); // передаем на распознавание
                    } elseif ($captcha) {
                        $key = $antigate_key2;
                        $host = $antigate_host2;
                        $antigateid = antigate_post($captcha,$key,false,$host,0,(int)($captcha_type==2),(int)($captcha_type==1),$captcha_size,$captcha_size?$captcha_size:99,(int)($captcha_type==3)); // передаем на распознавание
                    } elseif ($captcha_format=='hcaptcha') {
                        $key = $hcaptcha_key2;
                        $host = $hcaptcha_host2;
                        $antigateid = captcha_create($captcha_format,false,$captcha_token,$form_url,$captcha_action,$captcha_minscore,$key,false,$host); // запрашиваем новый токен
                    } elseif ($captcha_format=='v3') {
                        $key = $captchav3_key2;
                        $host = $captchav3_host2;
                        $antigateid = captcha_create($captcha_format,false,$captcha_token,$form_url,$captcha_action,$captcha_minscore,$key,false,$host); // запрашиваем новый токен
                    } else {
                        $key = $captcha_key2;
                        $host = $captcha_host2;
                        $antigateid = captcha_create($captcha_format,false,$captcha_token,$form_url,$captcha_action,$captcha_minscore,$key,false,$host); // запрашиваем новый токен
                    }

                    if ($antigateid && (strpos($antigateid,'ERROR')===false)) {
                        $sessions[$sessionid] = array('sourceid'=>$sourceid,'code'=>$code,'captcha_format'=>$captcha_format,'captcha_type'=>$captcha_type,'captcha_size'=>$captcha_size,'cookies'=>$cookies,'antigatehost'=>$host,'antigatekey'=>$key,'antigateid'=>$antigateid,'starttime'=>microtime(true),'method'=>$captcha_check_method,'url'=>$captcha_check_url,'params'=>$params,'field'=>$captcha_field,'token_field'=>$token_field,'token'=>$token,'token_regexp'=>$captcha_check_token_regexp,'proxy'=>$proxy,'proxy_auth'=>$proxy_auth);
                        db_execute($db,"update $sessiontable SET captcha_service='".$host."',captcha_id=$antigateid WHERE id=$sessionid");
                        print_log("Captcha id from ".$host." - $antigateid ($code)");
                    } else {
                        print_log("Failed sending captcha to ".$host." - $antigateid ($code)");
                        db_execute($db,"update $sessiontable SET sessionstatusid=4,statuscode='failedcaptcha' WHERE id=$sessionid AND sessionstatusid=1");
                    }
                }
            }
            if (($login_post_path || $form) && !$captcha_url && !$captcha_format && (!$auth_path_regexp || $auth_path)) {
                db_execute($db,"insert into $sessiontable (sourceid,cookies,starttime,lasttime,sessionstatusid,captcha,token,server,sourceaccessid,proxyid) VALUES ($sourceid,'$cookies_str',now(),now(),2,'','$token','$server',".($sourceaccessid?$sourceaccessid:'NULL').",".($proxy?"'".$proxyid."'":'NULL').")");
                $sessionid = $db->insert_id;
                if ($sessionid) {
                    print_log("Created session: $sessionid ($code" . ($login?" login $login":"") . ")");
//                    if ($sourceaccessid)
//                        db_execute($db,"UPDATE sourceaccess SET unlocktime=NULL WHERE sourceaccessid=$sourceaccessid");
                } else {
                    print_log("Session insert failed ($code login $login)\n"."insert into $sessiontable (sourceid,cookies,starttime,lasttime,sessionstatusid,captcha,server,sourceaccessid,proxyid) VALUES ($sourceid,'$cookies_str',now(),now(),2,'','$server',".($sourceaccessid?$sourceaccessid:'NULL').",".($proxy?"'".$proxyid."'":'NULL').")");
                }
            }
        }
    }


    if (count($sessions)>0) {
        foreach($sessions as $sessionid => &$s) {
            if (!isset($s['lasttime'])) $s['lasttime'] = $s['starttime'];
            $code = $s['code'];
            $lasttime = $s['lasttime'];
            if ($s['antigatehost']=='neuro') {
//                $captcha_value = $s['antigateid'];
                $captcha_value = trim(neuro_get($s['antigateid'])); // Запрашиваем значение капчи
            } elseif ((microtime(true)-$s['starttime'] < 10) || (microtime(true)-$s['lasttime'] < 5)) { // еще рано проверять
                $captcha_value = false;
            } elseif (microtime(true)-$s['starttime'] > $captcha_timeout) { // очень долго распознается
//                print_log("Captcha id {$s['antigateid']} ({$s['antigatehost']}) solving timeout for session: $sessionid (".$sessions[$sessionid]['code'].")");
                $captcha_value = 'ERROR_TIMEOUT_EXCEEDED';
            } else {
                if ($s['captcha_format']=='image')
                    $captcha_value = trim(antigate_get($s['antigateid'],$s['antigatekey'],false,$s['antigatehost'])); // Запрашиваем значение капчи
                else
                    $captcha_value = trim(captcha_result($s['antigateid'],$s['antigatekey'],false,$s['antigatehost'])); // Запрашиваем значение токена
                $s['lasttime'] = microtime(true);
            }
            if (($captcha_value) && (strpos($captcha_value,'ERROR')===false)) {
                if ($s['captcha_format']=='image' && $s['captcha_type']==3) {
                    $captcha_value = trim(strtr($captcha_value,array('A'=>'А','B'=>'В','C'=>'С','E'=>'Е','F'=>'Г','H'=>'Н','K'=>'К','M'=>'М','N'=>'И','O'=>'О','P'=>'Р','R'=>'Я','T'=>'Т','Y'=>'У','X'=>'Х','a'=>'а','c'=>'с','e'=>'е','f'=>'г','h'=>'н','k'=>'к','m'=>'м','n'=>'п','o'=>'о','p'=>'р','r'=>'г','t'=>'т','y'=>'у','x'=>'х')));
                }
//                db_execute($db,"update $sessiontable SET captchaimage=NULL,captcha_reporttime=NULL,captchatime=now(),captcha='$captcha_value'".($s['captcha_format']=='image'?"":",lasttime=now(),endtime=null,sessionstatusid=1 ")." WHERE id=$sessionid");
                db_execute($db,"UPDATE $sessiontable SET captchaimage=NULL,captcha_reporttime=NULL,captcha='$captcha_value'".($s['captcha_format']=='image'?"":",captchatime=now(),endtime=null,sessionstatusid=1 ")." WHERE id=$sessionid");
                print_log("Recognized captcha id {$s['antigateid']} ({$s['antigatehost']}) for session: $sessionid - ".(strlen($captcha_value)<20?$captcha_value:substr($captcha_value,0,2).'...'.substr($captcha_value,strlen($captcha_value)-5))." ($code), starttime = ".date("H:i:s",$s['starttime']).", lasttime = ".date("H:i:s",$lasttime)."");
                if ($s['captcha_format']=='image') {
                    $captcha_pic = "captcha/$code/__$sessionid.jpg";
                    $captcha_pic_new = "captcha/$code/".strtr($captcha_value,array(':'=>'_','/'=>'_','\\'=>'_','?'=>'_','*'=>'_','"'=>'_','<'=>'_','>'=>'_','|'=>'_')).".jpg";
                    if (file_exists($captcha_pic)) rename($captcha_pic,$captcha_pic_new);
                }

                if (($s['captcha_format']=='image') && $s['captcha_size'] && (mb_strlen($captcha_value)<>$s['captcha_size'])) {
                    $captcha_value = 'ERROR_INVALID_SIZE';
                    print_log("Invalid captcha size $captcha_value (must be {$s['captcha_size']})");
                } elseif (($s['captcha_format']=='image') && $s['captcha_type']==1 && !preg_match("/^[0-9]+$/",$captcha_value)) {
                    $captcha_value = 'ERROR_INVALID_CHAR';
                    print_log("Invalid captcha char $captcha_value (only digits allowed)");
                } elseif (($s['captcha_format']=='image') && $s['captcha_type']==3 && !preg_match("/^[0-9А-Яа-я]+$/u",$captcha_value)) {
                    $captcha_value = 'ERROR_INVALID_CHAR';
                    print_log("Invalid captcha char $captcha_value (only digits and cyrillic allowed)");
                } elseif ($s['method'] && $s['url']) {
                    $proxy = $s['proxy'];
                    $proxy_auth = $s['proxy_auth'];
                    $s['params'][$s['field']] = $captcha_value;
                    if ($s['token_field'])
                        $s['params'][$s['token_field']] = $s['token'];
                    $data = http_build_query($s['params']);
                    $check_url = $s['url'];
                    $check_options = array('http' => array(
                        'method' => $s['method'],
                        'timeout' => $http_timeout,
                        'ignore_errors' => true,
                        'header' =>
                            "Cache-Control: no-cache, no-store, must-revalidate\r\n" .
                            "User-Agent: $useragent\r\n" .
//                            "Referer: $form_url\r\n" .
                            cookies_header($s['cookies']),
                    ),'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ));
                    if ($s['method']=='POST') {
                        $check_options['http']['content'] = $data;
                        $check_options['http']['header'] =  "Content-Type: application/x-www-form-urlencoded\r\n" . "Content-Length: ".strlen($data)."\r\n" . $check_options['http']['header'];
                    } else {
                        $check_url .= (strpos($check_url,'?')?'&':'?').http_build_query($s['params']);
                        $check_options['http']['header'] = "Content-Length: 0\r\n" . "X-Requested-With: XMLHttpRequest\r\n" . $check_options['http']['header'];
                    }
                    if ($proxy) {
                        $check_options['http']['proxy'] = $proxy;
                        $check_options['http']['request_fulluri'] = true;
                        if ($proxy_auth)
                            $check_options['http']['header'] .= "Proxy-Authorization: Basic $proxy_auth\r\nAuthorization: Basic $proxy_auth\r\n";
                        print_log("Using proxy $proxy ($code)");
                    }
                    $check_context = stream_context_create($check_options);
                    $check = @file_get_contents($check_url,false,$check_context);
                    print_log("Checking captcha: $check_url, size: ".strlen($check));
                    file_put_contents("logs/$code/check_$sessionid.htm",(isset($http_response_header)?implode("\n",$http_response_header):'')."\n\n".$check);
                    $cookies = array_merge($s['cookies'],parse_cookies($http_response_header));
                    $cookies_str = addslashes(cookies_str($cookies));

                    if($s['token_regexp']) {
                        if(preg_match($s['token_regexp'], $check, $matches)){
                            $token = $matches[1];
//                            print_log("Check content: $check ($code)");
//                            print_log("Check regexp: {$s['token_regexp']} ($code)");
                            print_log("Session token: $token ($code)");
                            db_execute($db,"update $sessiontable SET cookies='$cookies_str', token='$token' WHERE id=$sessionid");
                        } elseif (!$check) {
//                            $captcha_value = false;
                            $captcha_value = 'ERROR_CHECKING_CAPTCHA';
                            print_log("Checking captcha failed ($code)");
                        } else {
                            $captcha_value = 'ERROR_INVALID_CAPTCHA';
                            print_log("Session token ".$s['token_regexp']." not found ($code)");
//                            print_log($check);
                        }
                    }
                }
            } elseif ($captcha_value) {
                db_execute($db,"update $sessiontable SET captcha='$captcha_value' WHERE id=$sessionid AND sessionstatusid=1");
                print_log("Captcha id {$s['antigateid']} ({$s['antigatehost']}) solving error $captcha_value for session: $sessionid ($code), starttime = ".date("H:i:s",$s['starttime']).", lasttime = ".date("H:i:s",$lasttime)."");
            }

            if ($captcha_value) {
                if (strpos($captcha_value,'ERROR')===false) {
                    db_execute($db,"update $sessiontable SET sessionstatusid=2 WHERE id=$sessionid AND sessionstatusid=1");
                } elseif ($captcha_value=='ERROR_CHECKING_CAPTCHA') {
//                    db_execute($db,"update $sessiontable SET sessionstatusid=7,lasttime=now(),captcha_service=NULL,captcha_id=NULL WHERE id=$sessionid AND sessionstatusid=1 AND statuscode='renew'");
//                    db_execute($db,"update $sessiontable SET captchaimage=NULL,captchatime=now(),captcha='',sessionstatusid=4,statuscode='checkingerror' WHERE id=$sessionid AND sessionstatusid=1");
//                    db_execute($db,"UPDATE $sessiontable SET sessionstatusid=4,statuscode='checkingerror',captchaimage=NULL WHERE id=$sessionid AND sessionstatusid=1 AND statuscode<>'renew'");
                    db_execute($db,"UPDATE $sessiontable SET sessionstatusid=7,statuscode='checkingerror',captchaimage=NULL,captcha='',captcha_service=NULL,captcha_id=NULL,captcha_reporttime=NULL WHERE id=$sessionid AND sessionstatusid=1 AND statuscode='renew'");
                } else {
//                    db_execute($db,"update $sessiontable SET sessionstatusid=7,lasttime=now(),captcha_service=NULL,captcha_id=NULL,captcha='' WHERE id=$sessionid AND sessionstatusid=1 AND statuscode='renew'");
//                    db_execute($db,"update $sessiontable SET captchaimage=NULL,captchatime=now(),sessionstatusid=4,statuscode='invalidcaptcha' WHERE id=$sessionid AND sessionstatusid=1");
//                    db_execute($db,"UPDATE $sessiontable SET sessionstatusid=4,statuscode='invalidcaptcha',captchaimage=NULL WHERE id=$sessionid AND sessionstatusid=1 AND statuscode<>'renew'");
                    db_execute($db,"UPDATE $sessiontable SET sessionstatusid=4,statuscode='invalidcaptcha',captchaimage=NULL WHERE id=$sessionid AND sessionstatusid=1 AND statuscode='renew'");
                }
                unset($sessions[$sessionid]);
            } else {
//                print_log("Captcha id {$s['antigateid']} ({$s['antigatehost']}) not ready for session: $sessionid ($code), starttime = ".date("H:i:s",$s['starttime']).", lasttime = ".date("H:i:s",$lasttime)."");
            }
        }
    }

// Если новых сессий не нужно, можно подождать
    if ($result->num_rows==0) sleep($idle_time);
    $result->close();

// Отправляем отчеты о неверно распознанных капчах
    $sql = <<<SQL
SELECT session.*,source.code source_code FROM $sessiontable,source WHERE session.sourceid=source.id AND session.sessionstatusid=4 AND session.captcha_service IS NOT NULL AND session.captcha_reporttime IS NULL 
$sessionfilter
ORDER BY session.captchatime DESC LIMIT 10
SQL;
    $result = $db->query($sql);
    while ($row = $result->fetch_object()) {
//        print_log("Reporting captcha ".$row->captcha." ID=".$row->captcha_id." to ".$row->captcha_service);
        $report_result = '';
        if (substr($row->captcha,0,6)=='ERROR_' || !$row->captcha_id) {
        } elseif ($row->captcha_service=='neuro') {
            $report_result = neuro_report($row->captcha_id,false);
            print_log("Captcha ".(strlen($row->captcha)<20?$row->captcha:substr($row->captcha,0,2).'...'.substr($row->captcha,strlen($row->captcha)-5))." ({$row->source_code}) ID={$row->captcha_id} time={$row->captchatime} from {$row->captcha_service} reported as bad. Result is $report_result");
        } elseif ($row->captcha_service=='rucaptcha.com') {
            $report_result = trim(antigate_reportbad($row->captcha_id,$antigate_host=='rucaptcha.com'?$antigate_key:$antigate_key2,false,'rucaptcha.com'));
            print_log("Captcha ".(strlen($row->captcha)<20?$row->captcha:substr($row->captcha,0,2).'...'.substr($row->captcha,strlen($row->captcha)-5))." ({$row->source_code}) ID={$row->captcha_id} time={$row->captchatime} from {$row->captcha_service} reported as bad. Result is $report_result");
        } elseif ($row->captcha_service=='anti-captcha.com' || $row->captcha_service=='api.anti-captcha.com') {
            if (strlen($row->captcha)<20) {
                $report_result = trim(captcha_bad($row->captcha_id,$antigate_host=='anti-captcha.com'?$antigate_key:$antigate_key2,false,'api.anti-captcha.com'));
            } else {
                $report_result = trim(recaptcha_bad($row->captcha_id,$antigate_host=='anti-captcha.com'?$antigate_key:$antigate_key2,false,'api.anti-captcha.com'));
            }
            print_log("Captcha ".(strlen($row->captcha)<20?$row->captcha:substr($row->captcha,0,2).'...'.substr($row->captcha,strlen($row->captcha)-5))." ({$row->source_code}) ID={$row->captcha_id} time={$row->captchatime} from {$row->captcha_service} reported as bad. Result is $report_result");
        } else {
            print_log("Captcha ".(strlen($row->captcha)<20?$row->captcha:substr($row->captcha,0,2).'...'.substr($row->captcha,strlen($row->captcha)-5))." ({$row->source_code}) ID={$row->captcha_id} time={$row->captchatime} from {$row->captcha_service} was bad.");
        }
        db_execute($db,"UPDATE $sessiontable SET captcha_reporttime=now(),data='$report_result' WHERE captcha_reporttime IS NULL AND id=".$row->id);
        db_execute($db,"UPDATE $sessiontable SET captcha_reporttime=now(),data='$report_result' WHERE captcha_reporttime IS NULL AND id=".$row->id);
        db_execute($db,"update $sessiontable SET used=0,success=0,captchaimage=NULL,captcha='',sessionstatusid=6,statuscode='toomanyinvalid',unlocktime=date_add(now(),interval 24 hour) WHERE captchaimage IS NOT NULL AND used/(success+1)>5 AND id=".$row->id);
        db_execute($db,"update $sessiontable SET used=0,success=0,captchaimage=NULL,captcha='',sessionstatusid=6,statuscode='toomanyinvalid',unlocktime=date_add(now(),interval 24 hour) WHERE captchaimage IS NOT NULL AND used/(success+1)>5 AND id=".$row->id);
        db_execute($db,"update $sessiontable SET sessionstatusid=7,lasttime=now(),statuscode='captcha',captcha='' WHERE captcha_reporttime IS NOT NULL AND captchaimage IS NOT NULL AND id=".$row->id);
        db_execute($db,"update $sessiontable SET sessionstatusid=7,lasttime=now(),statuscode='captcha',captcha='' WHERE captcha_reporttime IS NOT NULL AND captchaimage IS NOT NULL AND id=".$row->id);
        db_execute($db,"update $sessiontable SET sessionstatusid=2,captcha='' WHERE captcha_reporttime IS NOT NULL AND endtime IS NULL AND captchaimage IS NULL AND id=".$row->id);
        db_execute($db,"update $sessiontable SET sessionstatusid=2,captcha='' WHERE captcha_reporttime IS NOT NULL AND endtime IS NULL AND captchaimage IS NULL AND id=".$row->id);
        if (strlen($row->captcha)<20) {
            $row->captcha = strtr($row->captcha,array(':'=>'_','/'=>'_','\\'=>'_','?'=>'_','*'=>'_','"'=>'_','<'=>'_','>'=>'_','|'=>'_'));
            $captcha_dir = "captcha/{$row->source_code}";
            $captcha_pic = "$captcha_dir/{$row->captcha}.jpg";
            $captcha_new_dir = "$captcha_dir/bad.{$row->captcha_service}";
            $captcha_new_pic = "$captcha_new_dir/{$row->captcha}.jpg";
            if (!is_dir($captcha_new_dir)) mkdir($captcha_new_dir,0777,true);
            if (file_exists($captcha_pic)) rename($captcha_pic,$captcha_new_pic);
        }
    }
    $result->close();

// Отправляем отчеты об успешно распознанных капчах
    $sql = <<<SQL
SELECT session.*,source.code source_code FROM $sessiontable,source WHERE session.sourceid=source.id AND session.sessionstatusid IN (2,3,7) AND (session.statuscode='success' OR (source.captcha_check_method>'' AND source.captcha_check_token_regexp>'')) AND session.captcha_service IS NOT NULL AND session.captcha_reporttime IS NULL 
$sessionfilter
ORDER BY session.captchatime DESC LIMIT 10
SQL;
    $result = $db->query($sql);
    while ($row = $result->fetch_object()) {
//        print_log("Reporting captcha ".$row->captcha." ID=".$row->captcha_id." to ".$row->captcha_service);
        $report_result = '';
        if (!$row->captcha_id) {
        } elseif ($row->captcha_service=='neuro') {
            $report_result = neuro_report($row->captcha_id,true);
            print_log("Captcha ".(strlen($row->captcha)<20?$row->captcha:substr($row->captcha,0,2).'...'.substr($row->captcha,strlen($row->captcha)-5))." ({$row->source_code}) ID={$row->captcha_id} time={$row->captchatime} from {$row->captcha_service} reported as good. Result is $report_result");
        } elseif ($row->captcha_service=='rucaptcha.com') {
            $report_result = trim(antigate_reportgood($row->captcha_id,$antigate_host=='rucaptcha.com'?$antigate_key:$antigate_key2,false,'rucaptcha.com'));
            print_log("Captcha ".(strlen($row->captcha)<20?$row->captcha:substr($row->captcha,0,2).'...'.substr($row->captcha,strlen($row->captcha)-5))." ({$row->source_code}) ID={$row->captcha_id} time={$row->captchatime} from {$row->captcha_service} reported as good. Result is $report_result");
        } elseif ($row->captcha_service=='anti-captcha.com' || $row->captcha_service=='api.anti-captcha.com') {
            if (strlen($row->captcha)<20) {
                $report_result = 'NONE'; //trim(captcha_good($row->captcha_id,$antigate_host=='anti-captcha.com'?$antigate_key:$antigate_key2,false,'api.anti-captcha.com'));
            } else {
                $report_result = trim(recaptcha_good($row->captcha_id,$antigate_host=='anti-captcha.com'?$antigate_key:$antigate_key2,false,'api.anti-captcha.com'));
            }
            print_log("Captcha ".(strlen($row->captcha)<20?$row->captcha:substr($row->captcha,0,2).'...'.substr($row->captcha,strlen($row->captcha)-5))." ({$row->source_code}) ID={$row->captcha_id} time={$row->captchatime} from {$row->captcha_service} reported as good. Result is $report_result");
        } else {
            print_log("Captcha ".(strlen($row->captcha)<20?$row->captcha:substr($row->captcha,0,2).'...'.substr($row->captcha,strlen($row->captcha)-5))." ({$row->source_code}) ID={$row->captcha_id} time={$row->captchatime} from {$row->captcha_service} was good.");
        }
        db_execute($db,"UPDATE $sessiontable SET captcha_reporttime=now(),data='$report_result' WHERE captcha_reporttime IS NULL AND id=".$row->id);
        db_execute($db,"UPDATE $sessiontable SET captcha_reporttime=now(),data='$report_result' WHERE captcha_reporttime IS NULL AND id=".$row->id);
        db_execute($db,"update $sessiontable SET used=0,success=0,sessionstatusid=7,lasttime=now(),statuscode='captcha' WHERE captcha_reporttime IS NOT NULL AND captchaimage IS NOT NULL AND id=".$row->id);
        db_execute($db,"update $sessiontable SET used=0,success=0,sessionstatusid=7,lasttime=now(),statuscode='captcha' WHERE captcha_reporttime IS NOT NULL AND captchaimage IS NOT NULL AND id=".$row->id);
        db_execute($db,"update $sessiontable SET sessionstatusid=2,captcha='' WHERE captcha_reporttime IS NOT NULL AND endtime IS NULL AND captchaimage IS NULL AND id=".$row->id);
        db_execute($db,"update $sessiontable SET sessionstatusid=2,captcha='' WHERE captcha_reporttime IS NOT NULL AND endtime IS NULL AND captchaimage IS NULL AND id=".$row->id);
        if (strlen($row->captcha)<20) {
            $row->captcha = strtr($row->captcha,array(':'=>'_','/'=>'_','\\'=>'_','?'=>'_','*'=>'_','"'=>'_','<'=>'_','>'=>'_','|'=>'_'));
            $captcha_dir = "captcha/{$row->source_code}";
            $captcha_pic = "$captcha_dir/{$row->captcha}.jpg";
            $captcha_new_dir = "$captcha_dir/good.{$row->captcha_service}";
            $captcha_new_pic = "$captcha_new_dir/{$row->captcha}.jpg";
            if (!is_dir($captcha_new_dir)) mkdir($captcha_new_dir,0777,true);
            if (file_exists($captcha_pic)) rename($captcha_pic,$captcha_new_pic);
        }
    }
    $result->close();

    $wait = (count($sessions)>0);
}
/*
// Активируем приостановленные прокси (условие приостановления уже не выполняется)
    $sql = <<<SQL
UPDATE proxy SET status=1
WHERE enabled>0 AND status=0 AND id NOT IN (
SELECT proxyid FROM proxyhourstats WHERE successrate<0.3)
AND unix_timestamp(now())-unix_timestamp(lasttime)>600
SQL;
    db_execute($db,$sql);

// Деактивируем нерабочие прокси (успешность <50% за последний час)
    $sql = <<<SQL
UPDATE proxy SET status=0
WHERE enabled>0 AND status>0 AND (id IN (
SELECT proxyid FROM proxyhourstats WHERE successrate<0.3)
OR (unix_timestamp(now())-unix_timestamp(lasttime)<=600
AND unix_timestamp(now())-unix_timestamp(successtime)>600)
)
SQL;
    db_execute($db,$sql);

// Выключаем мертвые прокси (успешность <30% за сутки)
    $sql = <<<SQL
UPDATE proxy SET enabled=0,status=0,endtime=now()
WHERE enabled>0 AND in IN (
SELECT proxyid FROM proxystats WHERE successrate<0.3)
SQL;
    db_execute($db,$sql);
*/
// Отключаемся от базы данных
db_close($db);
print_log("Daemon $daemonnum stopped");
file_put_contents("CYCLE$daemonnum.txt", (intval(file_get_contents("CYCLE$daemonnum.txt")) + 1));
fclose($flock);
sleep(5);

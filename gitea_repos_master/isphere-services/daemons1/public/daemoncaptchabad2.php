#!/usr/bin/php -q
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

$daemonnum = "captchabad2";
$proxyfilter = "";
$sourceaccessfilter = "";
$sessionfilter = " AND mod(session.id,5)+1=2";;

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
/*
// Удаляем новые сессии с зависшими капчами
db_execute($db,"delete from session where sessionstatusid=1 AND statuscode<>'renew' AND starttime < SUBDATE(NOW(), INTERVAL 5 MINUTE) AND sourceid IN (SELECT id FROM source WHERE enabled=1)");
// Сбрасываем обновляемые сессии с зависшими капчами
db_execute($db,"update session set sessionstatusid=7,lasttime=now(),statuscode='captchaerror' where sessionstatusid=1 AND statuscode='renew' AND lasttime < SUBDATE(NOW(), INTERVAL 5 MINUTE)");
db_execute($db,"update session set sessionstatusid=7,lasttime=now(),statuscode='captchaerror' where sessionstatusid=4 AND endtime IS NULL AND lasttime < SUBDATE(NOW(), INTERVAL 5 MINUTE)");
// Удаляем использованные сессии старше 1 часа
db_execute($db,"delete from session where sessionstatusid IN (3,4,5) AND endtime IS NOT NULL AND (endtime < SUBDATE(NOW(), INTERVAL 1 HOUR) OR captcha_service IS NULL)");
// Очищаем статистику прокси старше 1 дня
db_execute($db,"delete from proxyusage where lasttime < SUBDATE(NOW(), INTERVAL 1 DAY)");
// Отвязываем сессии от старых и выполненных запросов
db_execute($db,"update session set request_id=NULL WHERE request_id IS NOT NULL AND endtime IS NULL AND (SELECT COUNT(*) FROM RequestNew WHERE id=session.request_id AND status<>0)");

// Удаляем капчи с истекшим сроком
    $sql = <<<SQL
UPDATE session SET captcha='',statuscode='captchaexpired'
WHERE sessionstatusid IN (2,6,7) and captcha>''
AND sourceid IN (SELECT id from source WHERE captcha_time>0)
AND unix_timestamp(now())-unix_timestamp(captchatime)+$idle_time+1>=
(SELECT captcha_time from source WHERE id=session.sourceid)
SQL;
    db_execute($db,$sql);

// Меняем неактивные прокси в сессиях
    $sql = <<<SQL
UPDATE session SET proxyid=(SELECT MIN(id) FROM proxy WHERE status=1 AND id>proxyid)
WHERE sessionstatusid IN (2,6,7)
AND sourceid IN (SELECT id FROM source WHERE status=1)
AND proxyid IN (SELECT id FROM proxy WHERE id>100 AND status=0)
SQL;
//    db_execute($db,$sql);

// Разблокируем заблокированные логины
    $sql = <<<SQL
UPDATE sourceaccess SET unlocktime=null
WHERE unlocktime IS NOT NULL AND unlocktime<now()
SQL;
    db_execute($db,$sql);
*/
while ($wait)
{
/*
// Разблокируем заблокированные сессии
    $sql = <<<SQL
UPDATE session SET sessionstatusid=2,statuscode='unlocked'
WHERE sessionstatusid=6 AND unlocktime<now()
SQL;
    db_execute($db,$sql);

// Завершаем просроченные сессии
    $sql = <<<SQL
UPDATE session SET sessionstatusid=5,statuscode='ended',endtime=now()
WHERE sessionstatusid IN (2,6,7)
AND sourceid IN (SELECT id from source WHERE session_time>0)
AND unix_timestamp(now())-unix_timestamp(starttime)+$idle_time+1>=
(SELECT session_time from source WHERE id=session.sourceid)
SQL;
    db_execute($db,$sql);

// Стираем просроченные токены рекапчи
    $sql = <<<SQL
UPDATE session SET sessionstatusid=7,lasttime=now(),statuscode='captchaexpired',captcha='',captcha_id=NULL
WHERE sessionstatusid IN (2,6) AND captcha>''
AND sourceid IN (SELECT id from source WHERE captcha_format IN ('recaptcha','v2ent','v3','hcaptcha','turnstile') AND captcha_check_method='')
AND unix_timestamp(now())-unix_timestamp(captchatime)>110
SQL;
    db_execute($db,$sql);

// Завершаем неактивные сессии
    $sql = <<<SQL
UPDATE session SET sessionstatusid=5,statuscode='inactive',endtime=now()
WHERE sessionstatusid IN (2,6,7)
AND sourceid IN (SELECT id from source WHERE session_inactivity>0)
AND unix_timestamp(now())-unix_timestamp(lasttime)+$idle_time+1>=
(SELECT session_inactivity from source WHERE id=session.sourceid)
SQL;
    db_execute($db,$sql);
*/
    $wait = false;

// Отправляем отчеты о неверно распознанных капчах
    $sql = <<<SQL
SELECT session.*,source.code source_code FROM session,source WHERE session.sourceid=source.id AND session.sessionstatusid=4 AND session.captcha_service IS NOT NULL AND session.captcha_reporttime IS NULL 
$sessionfilter
ORDER BY session.captchatime LIMIT 1
SQL;
    $result = $db->query($sql);
    while ($row = $result->fetch_object()) {
        $wait = true;
//        print_log("Reporting captcha ".$row->captcha." ID=".$row->captcha_id." to ".$row->captcha_service);
        $report_result = '';
        if (substr($row->captcha,0,6)=='ERROR_' || !$row->captcha_id) {
        } elseif ($row->captcha_service=='neuro') {
            $report_result = neuro_report($row->captcha_id,false);
            print_log("Captcha ".(strlen($row->captcha)<20?$row->captcha:substr($row->captcha,0,2).'...'.substr($row->captcha,strlen($row->captcha)-5))." ({$row->source_code}) ID={$row->captcha_id} time={$row->captchatime} from {$row->captcha_service} reported as bad. Result is $report_result");
        } elseif ($row->captcha_service=='rucaptcha.com') {
            $report_result = trim(antigate_reportbad($row->captcha_id,$captcha_services[$row->captcha_service],false,'rucaptcha.com'));
            print_log("Captcha ".(strlen($row->captcha)<20?$row->captcha:substr($row->captcha,0,2).'...'.substr($row->captcha,strlen($row->captcha)-5))." ({$row->source_code}) ID={$row->captcha_id} time={$row->captchatime} from {$row->captcha_service} reported as bad. Result is $report_result");
        } elseif ($row->captcha_service=='anti-captcha.com' || $row->captcha_service=='api.anti-captcha.com') {
            if (strlen($row->captcha)<20) {
                $report_result = trim(captcha_bad($row->captcha_id,$captcha_services[$row->captcha_service],false,'api.anti-captcha.com'));
            } elseif($row->captchatime) {
                $report_result = trim(recaptcha_bad($row->captcha_id,$captcha_services[$row->captcha_service],false,'api.anti-captcha.com'));
            }
            print_log("Captcha ".(strlen($row->captcha)<20?$row->captcha:substr($row->captcha,0,2).'...'.substr($row->captcha,strlen($row->captcha)-5))." ({$row->source_code}) ID={$row->captcha_id} time={$row->captchatime} from {$row->captcha_service} reported as bad. Result is $report_result");
        } elseif ($row->captcha_service=='api.capmonster.cloud') {
            if (strlen($row->captcha)<20) {
                $report_result = trim(captcha_bad($row->captcha_id,$captcha_services[$row->captcha_service],false,'api.capmonster.cloud'));
            } else {
                $report_result = trim(recaptcha_bad($row->captcha_id,$captcha_services[$row->captcha_service],false,'api.capmonster.cloud'));
            }
            print_log("Captcha ".(strlen($row->captcha)<20?$row->captcha:substr($row->captcha,0,2).'...'.substr($row->captcha,strlen($row->captcha)-5))." ({$row->source_code}) ID={$row->captcha_id} time={$row->captchatime} from {$row->captcha_service} reported as bad. Result is $report_result");
//        } else {
//            print_log("Captcha ".(strlen($row->captcha)<20?$row->captcha:substr($row->captcha,0,2).'...'.substr($row->captcha,strlen($row->captcha)-5))." ({$row->source_code}) ID={$row->captcha_id} time={$row->captchatime} from {$row->captcha_service} was bad.");
        }
        db_execute($db,"UPDATE session SET captcha_reporttime=now(),data='$report_result' WHERE captcha_reporttime IS NULL AND id=".$row->id);
        db_execute($db,"UPDATE session SET captcha_reporttime=now(),data='$report_result' WHERE captcha_reporttime IS NULL AND id=".$row->id);
        db_execute($db,"UPDATE session SET used=0,success=0,captchaimage=NULL,captcha='',sessionstatusid=6,statuscode='toomanyinvalid',unlocktime=date_add(now(),interval 24 hour) WHERE captchaimage IS NOT NULL AND used/(success+1)>5 AND id=".$row->id);
        db_execute($db,"UPDATE session SET used=0,success=0,captchaimage=NULL,captcha='',sessionstatusid=6,statuscode='toomanyinvalid',unlocktime=date_add(now(),interval 24 hour) WHERE captchaimage IS NOT NULL AND used/(success+1)>5 AND id=".$row->id);
        db_execute($db,"UPDATE session SET sessionstatusid=7,lasttime=now(),statuscode='captcha',captcha='' WHERE captcha_reporttime IS NOT NULL AND captchaimage IS NOT NULL AND id=".$row->id);
        db_execute($db,"UPDATE session SET sessionstatusid=7,lasttime=now(),statuscode='captcha',captcha='' WHERE captcha_reporttime IS NOT NULL AND captchaimage IS NOT NULL AND id=".$row->id);
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
/*
// Отправляем отчеты об успешно распознанных капчах
    $sql = <<<SQL
SELECT session.*,source.code source_code FROM session,source WHERE session.sourceid=source.id AND session.sessionstatusid IN (2,3,7) AND (session.statuscode='success' OR (source.captcha_check_method>'' AND source.captcha_check_token_regexp>'')) AND session.captcha_service IS NOT NULL AND session.captcha_reporttime IS NULL 
$sessionfilter
ORDER BY session.captchatime DESC LIMIT 5
SQL;
    $result = $db->query($sql);
    while ($row = $result->fetch_object()) {
        $wait = true;
//        print_log("Reporting captcha ".$row->captcha." ID=".$row->captcha_id." to ".$row->captcha_service);
        $report_result = '';
        if (!$row->captcha_id) {
        } elseif ($row->captcha_service=='neuro') {
            $report_result = neuro_report($row->captcha_id,true);
            print_log("Captcha ".(strlen($row->captcha)<20?$row->captcha:substr($row->captcha,0,2).'...'.substr($row->captcha,strlen($row->captcha)-5))." ({$row->source_code}) ID={$row->captcha_id} time={$row->captchatime} from {$row->captcha_service} reported as good. Result is $report_result");
        } elseif ($row->captcha_service=='rucaptcha.com') {
            $report_result = trim(antigate_reportgood($row->captcha_id,$captcha_services[$row->captcha_service],false,'rucaptcha.com'));
            print_log("Captcha ".(strlen($row->captcha)<20?$row->captcha:substr($row->captcha,0,2).'...'.substr($row->captcha,strlen($row->captcha)-5))." ({$row->source_code}) ID={$row->captcha_id} time={$row->captchatime} from {$row->captcha_service} reported as good. Result is $report_result");
        } elseif ($row->captcha_service=='anti-captcha.com' || $row->captcha_service=='api.anti-captcha.com') {
            if (strlen($row->captcha)<20) {
                $report_result = 'NONE'; //trim(captcha_good($row->captcha_id,$captcha_services[$row->captcha_service],false,'api.anti-captcha.com'));
            } else {
                $report_result = trim(recaptcha_good($row->captcha_id,$captcha_services[$row->captcha_service],false,'api.anti-captcha.com'));
            }
            print_log("Captcha ".(strlen($row->captcha)<20?$row->captcha:substr($row->captcha,0,2).'...'.substr($row->captcha,strlen($row->captcha)-5))." ({$row->source_code}) ID={$row->captcha_id} time={$row->captchatime} from {$row->captcha_service} reported as good. Result is $report_result");
//        } else {
//            print_log("Captcha ".(strlen($row->captcha)<20?$row->captcha:substr($row->captcha,0,2).'...'.substr($row->captcha,strlen($row->captcha)-5))." ({$row->source_code}) ID={$row->captcha_id} time={$row->captchatime} from {$row->captcha_service} was good.");
        }
        db_execute($db,"UPDATE session SET captcha='',captcha_reporttime=now(),data='$report_result' WHERE id=".$row->id);
        db_execute($db,"UPDATE session SET captcha='',captcha_reporttime=now(),data='$report_result' WHERE id=".$row->id);
        db_execute($db,"UPDATE session SET used=0,success=0,sessionstatusid=7,lasttime=now(),statuscode='captcha' WHERE captcha_reporttime IS NOT NULL AND captchaimage IS NOT NULL AND id=".$row->id);
        db_execute($db,"UPDATE session SET used=0,success=0,sessionstatusid=7,lasttime=now(),statuscode='captcha' WHERE captcha_reporttime IS NOT NULL AND captchaimage IS NOT NULL AND id=".$row->id);
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
*/
}

// Отключаемся от базы данных
db_close($db);
print_log("Daemon $daemonnum stopped");
file_put_contents("CYCLE$daemonnum.txt", (intval(file_get_contents("CYCLE$daemonnum.txt")) + 1));
fclose($flock);
sleep(5);

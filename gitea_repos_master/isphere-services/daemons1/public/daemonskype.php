<?php

require('config.php');
require_once('routines.php');
set_time_limit(300);
require("skype.class.php");

// Подключаемся к базе данных
$db = db_connect($database);
if (!$db) {
    print_log("Connection to SQL server failed");
    sleep(5);
    die();
}
$sourceid = 16;

// Читаем активные аккаунты, по которым нет действующих или приостановленных сессий
$sql = <<<SQL
SELECT * FROM sourceaccess
WHERE sourceid=$sourceid AND status=1 AND (unlocktime IS NULL OR unlocktime<now()) AND unix_timestamp(now())-unix_timestamp(lasttime)>30
AND sourceaccessid not in (SELECT sourceaccessid FROM session WHERE sourceaccessid IS NOT NULL AND endtime IS NULL)
ORDER BY lasttime
;
SQL;
$access_result = $db->query($sql);
while ($access_row = $access_result->fetch_object()) {
    $sourceaccessid = $access_row->sourceaccessid;
    db_execute($db, "UPDATE sourceaccess SET lasttime=now() WHERE sourceaccessid=$sourceaccessid");

    $proxy = true;
    $proxy_auth = false;

    if ($proxy) { // Выбираем последний рабочий прокси, через который ходили под этим аккаунтом
        $sql = <<<SQL
SELECT * FROM proxy
WHERE status=1 
AND id=(SELECT proxyid FROM session WHERE id=(SELECT MAX(id) FROM session WHERE proxyid IS NOT NULL AND sourceid=$sourceid AND sourceaccessid=$sourceaccessid))
;
SQL;
// AND id not in (SELECT proxyid FROM session WHERE sourceid=$sourceid AND sourceaccessid<>$sourceaccessid AND endtime IS NULL AND proxyid IS NOT NULL)
        $proxy_result = $db->query($sql);
        if ($proxy_row = $proxy_result->fetch_object()) {
            $proxyid = $proxy_row->id;
            $proxy = 'tcp://' . $proxy_row->server . ':' . $proxy_row->port;
            if ($proxy_row->login) {
                $proxy_auth = base64_encode($proxy_row->login . ':' . $proxy_row->password);
            } else {
                $proxy_auth = false;
            }
            db_execute($db, "UPDATE proxy SET lasttime=now() WHERE id=$proxyid");
        } else {
            $proxy = 0;
        }
    }
    /*
        if (!$proxy) { // Проверяем есть ли сессия без прокси
    $sql = <<<SQL
    SELECT COUNT(*) count FROM session WHERE sourceid=$sourceid AND endtime IS NULL AND proxyid IS NULL
    ;
    SQL;
            $noproxy_result = $db->query($sql);
            if ($noproxy_row = $noproxy_result->fetch_object()) {
                if ($noproxy_row->count>0) $proxy=true; // Нужно создать сессию с прокси
            }
        }
    */
    if (!$proxy) { // Выбираем прокси, по которому еще нет сессии
        $sql = <<<SQL
SELECT * FROM proxy
WHERE status=1 AND proxygroup=1 AND id not in (SELECT proxyid FROM session WHERE sourceid=$sourceid AND endtime IS NULL AND proxyid IS NOT NULL)
ORDER BY lasttime LIMIT 1
;
SQL;
        $proxy_result = $db->query($sql);
        if ($proxy_row = $proxy_result->fetch_object()) {
            $proxyid = $proxy_row->id;
            $proxy = 'tcp://' . $proxy_row->server . ':' . $proxy_row->port;
            if ($proxy_row->login) {
                $proxy_auth = base64_encode($proxy_row->login . ':' . $proxy_row->password);
            } else {
                $proxy_auth = false;
            }
            db_execute($db, "UPDATE proxy SET lasttime=now() WHERE id=$proxyid");
        } else {
//            print_log("Not enough proxies ($code)");
            $proxy = 0;
        }
    }

    print "Log in as " . $access_row->login . ($proxy ? " through " . $proxy . " (" . $proxyid . ") with " . $proxy_auth : "") . "\n";
    try {
        $skype = new Skype($access_row->login, $access_row->password/*,"skypephp",$proxy,$proxy_auth*/);
    } catch (Exception $e) {
    }

    if (isset($skype) && ($token = $skype->getToken())) {
        // Проверяем нет ли уже сессии с таким токеном
        $count = db_select($db, "SELECT COUNT(*) count FROM session WHERE sourceid=16 AND sessionstatusid=2 AND token='$token'");
        if ($count == 0) {
            // Завершаем старые сессии
            db_execute(
                $db,
                "UPDATE session SET sessionstatusid=5, endtime=now() WHERE sourceid=16 AND sourceaccessid=$sourceaccessid AND sessionstatusid IN (2,6,7)"
            );
            // Создаём новую сессию
            if (!$proxy) {
                $proxyid = 'NULL';
            }
            db_execute(
                $db,
                "INSERT INTO session (sourceid,cookies,starttime,lasttime,sessionstatusid,captcha,token,server,sourceaccessid,proxyid) VALUES (16,'',now(),now(),2,'','$token','',$sourceaccessid,$proxyid)"
            );
        }
    } else {
        // Авторизация не прошла, отключаем учетку
        db_execute($db, "UPDATE sourceaccess SET status=0 WHERE sourceaccessid=$sourceaccessid");
    }
}

// Отключаемся от базы данных
db_close($db);
sleep(5);

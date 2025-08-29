<?php

chdir('/var/www/crontab/sourcesAlert');

define('STATUS_FILE', 'files/status.txt');
define('SPEED_FILE', 'files/speed.txt');
define('SESSIONS_FILE', 'files/sessions.txt');
define('PROXIES_FILE', 'files/proxies.txt');

function doAlert($msg){
	$serviceurl = "https://api.telegram.org/bot2103347962:AAHMdZY-Bh6ELR-NB7qOapnnD7sbh2c3bsQ/sendMessage?chat_id=-1001662664995";

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $serviceurl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "text=".urlencode($msg));
	curl_setopt($ch, CURLOPT_POST, 1);

	$data = curl_exec($ch);

	$answer = $data;

	curl_close($ch);
	return true;
}

    $now = date('d.m.Y H:i:s');

    $db = mysqli_connect (getenv('DATABASE_SERVER'),getenv('DATABASE_LOGIN'),getenv('DATABASE_PASSWORD'), getenv('DATABASE_NAME'));
    mysqli_query($db, "SET NAMES 'utf8'");

    $status = json_decode(file_get_contents(STATUS_FILE), true);

    $interval = '10 minute';
    $rare_interval = '60 minute';

    $checktypes = array();
    $rare_checktypes = array();

    $response = mysqli_query($db, "SELECT checktype, ROUND(SUM(CASE WHEN res_code < 500 THEN 1 ELSE 0 END)/count(*)*100) AS successrate, count(*) FROM ResponseNew WHERE created_at >= (NOW() - INTERVAL $interval) AND res_code > 0 GROUP BY checktype ORDER BY 3 DESC;");
    while($row = $response->fetch_row()){
        if ($row[2]>=20) {
            $successrates[$row[0]] = $row[1];
            $intervals[$row[0]] = substr($interval,0,6);
            $checktypes[] = "'".$row[0]."'";
        } else {
            $rare_checktypes[] = "'".$row[0]."'";
        }
        $count[$row[0]] = $row[2];
    }

    $response = mysqli_query($db, "SELECT checktype, ROUND(SUM(CASE WHEN res_code < 500 THEN 1 ELSE 0 END)/count(*)*100) AS successrate, count(*) FROM ResponseNew WHERE created_at >= (NOW() - INTERVAL $rare_interval) AND res_code > 0 AND checktype NOT IN (".implode(',',$checktypes).") GROUP BY checktype ORDER BY 3 DESC;");
    while($row = $response->fetch_row()){
        if ($row[2]>=20) {
            $successrates[$row[0]] = $row[1];
            $intervals[$row[0]] = substr($rare_interval,0,6);
        }
    }

    $minsuccessrate = array(
        'gibdd_aiusdtp' => 80,
        'gibdd_diagnostic' => 80,
        'gibdd_driver' => 80,
        'gibdd_history' => 80,
        'gibdd_restricted' => 80,
        'gibdd_wanted' => 80,
        'smsc_phone' => 50,
        'hlr_phone' => 80,
        'emt_phone' => 80,
        'aeroflot_email' => 80,
        'aeroflot_phone' => 80,
        'facebook_email' => 80,
        'facebook_phone' => 80,
        'rz_person' => 80,
        'rz_auto' => 80,
        'reestrzalogov_person' => 80,
        'reestrzalogov_auto' => 80,
    );
    $msg_b = "";
    $msg_g = "";
    foreach($successrates as $checktype => $successrate){
        if (!isset($minsuccessrate[$checktype])) $minsuccessrate[$checktype] = 90;
        $errors = "";
        $errorcount = 0;
        if ($successrate < $minsuccessrate[$checktype]) {
            $response = mysqli_query($db, "SELECT text, count(*) FROM ResponseError WHERE response_id IN ( SELECT id FROM ResponseNew WHERE created_at >= (NOW() - INTERVAL ".(in_array($checktype,$checktypes)?$interval:$rare_interval).") AND res_code >= 500 AND checktype='$checktype' ) GROUP BY 1 ORDER BY 2 DESC;");
            while($row = $response->fetch_row()){
                $errors .= "{$row[0]} - {$row[1]}\n";
                $errorcount += $row[1];
            }
        }
        $msg = "";
        if ($successrate < 10) {
            if (!isset($status[$checktype]) || $status[$checktype]!='dead' || date('i')=='00') {
                $status[$checktype] = 'dead';
                $msg = "!!! $checktype is ".($intervals[$checktype]=='10 min' && $count[$checktype]>50?mb_strtoupper($status[$checktype]):$status[$checktype])." ($successrate%) in {$intervals[$checktype]}\n";
                $msg_b .= $msg.$errors;
            }
        } elseif ($successrate<$minsuccessrate[$checktype] && $errorcount>=3) {
            if (!isset($status[$checktype]) || $status[$checktype]!='bad' || date('i')=='00') {
                $status[$checktype] = 'bad';
                $msg = "--- $checktype is ".($intervals[$checktype]=='10 min' && $count[$checktype]>50?mb_strtoupper($status[$checktype]):$status[$checktype])." ($successrate%) in {$intervals[$checktype]}\n";
                $msg_b .= $msg.$errors;
            }
        } elseif ($successrate>=$minsuccessrate[$checktype]) {
            if (!isset($status[$checktype]) || $status[$checktype]!='good'){
                $status[$checktype] = 'good';
                $msg = "+++ $checktype is ".($intervals[$checktype]=='10 min' && $count[$checktype]>50?mb_strtoupper($status[$checktype]):$status[$checktype])." ($successrate%) in {$intervals[$checktype]}\n";
                $msg_g .= $msg;
            }
        }
    }
    if (strlen($msg_b)+strlen($msg_g))
        doAlert($msg_b.$msg_g);

    file_put_contents(STATUS_FILE, json_encode($status));

    $speed = json_decode(file_get_contents(SPEED_FILE), true);

    $response = mysqli_query($db, "SELECT checktype, ROUND(AVG(process_time),1) FROM ResponseNew WHERE created_at >= (NOW() - INTERVAL $interval) AND res_code IN (200,204) GROUP BY checktype HAVING count(*)>=20 ORDER BY 1;");
    while($row = $response->fetch_row()){
        $times[$row[0]] = $row[1];
    }

    $response = mysqli_query($db, "SELECT checktype, ROUND(AVG(process_time),1) FROM ResponseNew WHERE created_at >= (NOW() - INTERVAL $rare_interval) AND res_code IN (200,204) AND checktype NOT IN (".implode(',',$checktypes).") GROUP BY checktype HAVING count(*)>=20 ORDER BY 1;");
    while($row = $response->fetch_row()){
        $times[$row[0]] = $row[1];
    }

    $maxtime = array(
        'aeroflot_phone' => 30,
        'announcement_phone' => 15,
        'avito_phone' => 30,
        'bankrot_person' => 15,
        'bankrot_inn' => 15,
        'boards_phone' => 5,
        'boards_phone_kz' => 5,
        'callapp_phone' => 15,
        'emt_phone' => 60,
        'facebook_email' => 30,
        'facebook_phone' => 30,
        'fms_passport' => 15,
        'fns_bi' => 15,
        'fns_disqualified' => 5,
        'fns_inn' => 15,
        'fns_invalid' => 15,
        'fns_mru' => 5,
        'fns_npd' => 15,
        'fssp_person' => 30,
        'getcontact_phone' => 10,
        'gibdd_aiusdtp' => 60,
        'gibdd_diagnostic' => 60,
        'gibdd_driver' => 60,
        'gibdd_history' => 60,
        'gibdd_restricted' => 60,
        'gibdd_wanted' => 60,
        'gosuslugi_email' => 20,
        'gosuslugi_passport' => 20,
        'gosuslugi_phone' => 20,
        'hlr_phone' => 60,
        'instagram_email' => 30,
        'instagram_phone' => 30,
        'listorg_phone' => 15,
        'mailru_email' => 15,
        'names_phone' => 5,
        'numbuster_phone' => 5,
        'ok_email' => 15,
        'ok_person' => 15,
        'ok_phone' => 15,
        'ok_url' => 30,
        'papajohns_phone' => 30,
        'phones_phone' => 15,
        'reestrzalogov_auto' => 120,
        'reestrzalogov_person' => 120,
        'rossvyaz_phone' => 30,
        'rz_auto' => 120,
        'rz_person' => 120,
        'simpler_phone' => 30,
        'skype_email' => 15,
        'skype_phone' => 15,
        'truecaller_phone' => 15,
        'twitter_email' => 30,
        'twitter_phone' => 30,
        'viberwin_phone' => 15,
        'viber_phone' => 15,
        'vk_email' => 5,
        'vk_emailcheck' => 15,
        'vk_person' => 15,
        'vk_phone' => 5,
        'vk_phonecheck' => 15,
        'vk_url' => 15,
        'whatsappweb_phone' => 15,
        'whatsapp_phone' => 15,
    );
    $msg_s = "";
    $msg_f = "";
    foreach($times as $checktype => $time){
        if (!isset($maxtime[$checktype])) $maxtime[$checktype] = 60;
        if ($time>=$maxtime[$checktype]) {
            if (!isset($speed[$checktype]) || $speed[$checktype]!='slow'){
                $speed[$checktype] = 'slow';
                $msg_s .= "$checktype is slow ($time >= {$maxtime[$checktype]})\n";
            }
        } else {
            if (!isset($speed[$checktype]) || $speed[$checktype]!='fast'){
                $speed[$checktype] = 'fast';
                $msg_f .= "$checktype is fast ($time < {$maxtime[$checktype]})\n";
            }
        }
    }
    if (strlen($msg_s)+strlen($msg_f))
        doAlert($msg_s.$msg_f);

    file_put_contents(SPEED_FILE, json_encode($speed));

    $sessions = json_decode(file_get_contents(SESSIONS_FILE), true);

    $response = mysqli_query($db, "WITH sess AS (SELECT sourceid,count(*) live_sessions FROM session WHERE sessionstatusid IN (1,2,7) GROUP BY sourceid UNION SELECT sourceid,count(*) live_sessions FROM session_getcontact WHERE sessionstatusid IN (1,2,7) GROUP BY sourceid UNION SELECT sourceid,count(*) live_sessions FROM session_gosuslugi WHERE sessionstatusid IN (1,2,7) GROUP BY sourceid) SELECT s.code,sess.live_sessions,s.min_sessions FROM `source` s JOIN sess ON sess.sourceid=s.id WHERE s.status=0 or (s.status=1 and id in (select sourceid from sourceaccess));");
    while($row = $response->fetch_row()){
        $live_sessions[$row[0]] = $row[1];
        $min_sessions[$row[0]] = $row[2];
    }

    $msg_i = "";
    $msg_e = "";
    foreach($live_sessions as $sourcecode => $count){
        if ($count < round($min_sessions[$sourcecode]*0.8)) {
            if (!isset($sessions[$sourcecode]) || $sessions[$sourcecode]!='insufficient' || date('H:i')=='09:00'){
                $sessions[$sourcecode] = 'insufficient';
                $msg_i .= "$sourcecode has insufficient sessions ($count < {$min_sessions[$sourcecode]})\n";
            }
        } elseif ($count>=$min_sessions[$sourcecode]) {
            if (!isset($sessions[$sourcecode]) || $sessions[$sourcecode]!='enough'){
                $sessions[$sourcecode] = 'enough';
                $msg_e .= "$sourcecode has enough sessions ($count >= {$min_sessions[$sourcecode]})\n";
            }
        }
    }
    if (strlen($msg_i)+strlen($msg_e))
        doAlert($msg_i.$msg_e);

    file_put_contents(SESSIONS_FILE, json_encode($sessions));


    $proxies = json_decode(file_get_contents(PROXIES_FILE), true);

    $response = mysqli_query($db, "SELECT ifnull(comment,server) proxy,status,successtime,unix_timestamp(now())-unix_timestamp(successtime) successint FROM proxy WHERE (status=11111 OR enabled=1) AND (lasttime>date_sub(now(),interval 10 minute) OR successtime>date_sub(now(),interval 10 minute)) ORDER BY id");
    while($row = $response->fetch_row()){
        $proxy_status[$row[0]] = $row[1];
        $success_time[$row[0]] = $row[2];
        $success_int[$row[0]] = $row[3];
    }

    $msg_u = "";
    $msg_w = "";
    foreach($proxy_status as $proxy => $status){
        if ($status==0 && $success_int[$proxy]>300) {
            if (!isset($proxies[$proxy]) || $proxies[$proxy]!='unavailable' || date('H:i')=='00:00' || date('H:i')=='06:00' || date('H:i')=='12:00' || date('H:i')=='18:00'){
                $proxies[$proxy] = 'unavailable';
                $msg_u .= "Proxy $proxy is {$proxies[$proxy]} from {$success_time[$proxy]}\n";
            }
        } elseif ($status==1) {
            if (!isset($proxies[$proxy]) || $proxies[$proxy]!='working'){
                $proxies[$proxy] = 'working';
                $msg_w .= "Proxy $proxy is {$proxies[$proxy]}\n";
            }
        }
    }
    if (strlen($msg_u)+strlen($msg_w))
        doAlert($msg_u.$msg_w);

    file_put_contents(PROXIES_FILE, json_encode($proxies));

    if (/*date('i')=='00'*/ date('H:i')=='03:00') {
        mysqli_query($db, "OPTIMIZE TABLE session");
        mysqli_query($db, "OPTIMIZE TABLE session_getcontact");
        mysqli_query($db, "OPTIMIZE TABLE session_gosuslugi");
        mysqli_query($db, "OPTIMIZE TABLE proxy");
        mysqli_query($db, "OPTIMIZE TABLE proxyusage");
    }

    mysqli_close($db);

?>

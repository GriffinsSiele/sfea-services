<?php

// xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);

if ($_SERVER['REQUEST_METHOD']=='GET') {
    header('Location: admin.php',true, 302);
}

// header('Access-Control-Allow-Origin: https://itkom.amocrm.ru');

if ($_SERVER['REQUEST_METHOD']!='POST') {
    exit();
}

$xml = file_get_contents('php://input');
$xml = substr($xml,strpos($xml,'<'));

if (!$xml) {
    echo '
    <error>
        <code>400</code>
        <message>Запрос отсутствует</message>
    </error>';
    header('HTTP/1.1 400 Bad Request'); 
    exit();
}
if (strpos($xml,'<Request>')===false) {
    echo '
    <error>
        <code>400</code>
        <message>В запросе отсутствует тег Request</message>
    </error>';
    header('HTTP/1.1 400 Bad Request'); 
    exit();
}

//////////////////////////////////////////////////////////
include ('config_new.php');
include ('cookies.php');
include ('str.php');
include ('neuro_new.php');

include ('engine/RequestManager_new.php');
include ('engine/RequestContext_new.php');
include ('engine/ResultDataField.php');
include ('engine/ResultDataList.php');

include ('engine/plugins/PluginInterface.php');

//////////////////////////////////////////////////////////

$mysqli = mysqli_init();
mysqli_options($mysqli,MYSQLI_OPT_CONNECT_TIMEOUT,$database['connect_timeout']);
mysqli_options($mysqli,MYSQLI_OPT_READ_TIMEOUT,$database['read_timeout']);
//$mysqli = mysqli_connect($database['server'],$database['login'],$database['password'],$database['name']);
//if ($mysqli) {
if (mysqli_real_connect($mysqli,$database['server'],$database['login'],$database['password'],$database['name'])) {
    mysqli_query($mysqli, "Set character set utf8");
    mysqli_query($mysqli, "Set names 'utf8'");
} else {
    echo '
    <error>
        <code>500</code>
        <message>Внутренняя ошибка сервиса</message>
    </error>';
    header('HTTP/1.1 500 Internal Server Error'); 
    exit();
}

$mysqls=false;
/*
$mysqls = isset($dbstat['server'])?mysqli_connect($dbstat['server'],$dbstat['login'],$dbstat['password'],$dbstat['name']):false;
if ($mysqls) {
    mysqli_query($mysqli, "Set character set utf8");
    mysqli_query($mysqli, "Set names 'utf8'");
}
*/
//////////////////////////////////////////////////////////

$contact_types = array('phone','email','skype','telegram','nick');
$contact_urls = array('vk'=>'vk.com','facebook'=>'facebook.com','ok'=>'ok.ru','instagram'=>'instagram.com'/*,'mymail'=>'my.mail.ru'*/,'hh'=>'hh.ru');

$start_time = time();
$reqtime = date("Y-m-d\TH:i:s");
$reqdate = date("Y-m-d");
$restime = '';
/*
$fout1 = fopen($logpath.'request.'.date('Y-m-d').'.log', 'a');
fputs($fout1, date("Y-m-d H:i:s")."\n".$xml."\n\n");
fclose($fout1);
*/
$userId = authUserId($xml);

$processing = 0;
$total_processing = 0;
if($userId && ($result = mysqli_query($mysqli, "SELECT sum(user_id=$userId) processing,COUNT(*) total_processing FROM RequestNew WHERE created_at>date_sub(now(),interval 5 minute) AND status=0"))){
    if($row = $result->fetch_assoc()){
        $processing = $row['processing'];
        $total_processing = $row['total_processing'];
    }
    mysqli_free_result($result);
    if ($processing>=30) {
        echo '
        <error>
            <code>429</code>
            <message>Слишком много запросов в обработке</message>
        </error>';
        header('HTTP/1.1 429 Too Many Requests'); 
        exit();
    }
    if ($total_processing>=300) {
        echo '
        <error>
            <code>503</code>
            <message>Сервис перегружен запросами</message>
        </error>';
        header('HTTP/1.1 503 Service Unavailable'); 
        exit();
    }
}

$clientId = 'NULL';
if($userId && ($result = mysqli_query($mysqli, 'SELECT ClientId FROM SystemUsers WHERE ClientId IS NOT NULL AND id='.$userId))){
    if($row = $result->fetch_assoc()){
        $clientId = $row['ClientId'];
    }
    mysqli_free_result($result);
}

if($clientId<>'NULL' && ($result = mysqli_query($mysqli, 'SELECT CASE WHEN (StartTime IS NULL OR CURRENT_TIMESTAMP>=StartTime) AND (EndTime IS NULL OR CURRENT_TIMESTAMP<EndTime) THEN Status ELSE -1 END Status FROM Client WHERE id='.$clientId))){
    $status = 1;
    if($row = $result->fetch_assoc()){
        $status = intval($row['Status']);
    }
    if ($status<1) {
        echo '
        <error>
            <code>401</code>
            <message>Доступ приостановлен</message>
        </error>';
        header('HTTP/1.1 401 Unauthorized'); 
        exit();
    }
    mysqli_free_result($result);
}

$params = parseParams($xml);
$req = preg_replace("/<\?xml[^>]+>/", "", $xml);
$req = preg_replace("/<Password>[^<]+<\/Password>/", "<Password>***</Password>", $req);
$reqId = logRequest($params,$req);
if (!$params['request_id']) $params['request_id'] = $reqId;
$req = preg_replace("/<Password>[^<]+<\/Password>/", "<requestDateTime>$reqtime</requestDateTime>", $req);
$srclist = "('".implode("','",$params['sources'])."')";

$user_sources = array();
if($userId && ($result = mysqli_query($mysqli, 'SELECT a.source_name FROM AccessSource a,SystemUsers u WHERE a.allowed=1 AND a.Level=u.AccessLevel AND u.id='.$userId))){
    while($row = $result->fetch_assoc()){
        $user_sources[$row['source_name']] = true;
        if ($row['source_name']=='fssp') $user_sources['fsspsite'] = true;
        if ($row['source_name']=='viber') $user_sources['viberwin'] = true;
        if ($row['source_name']=='numbuster') $user_sources['numbusterapp'] = true;
    }
    mysqli_free_result($result);
}

$checktypes = array();
if($result = mysqli_query($mysqli, 'SELECT * FROM CheckType WHERE source_code IN '.$srclist.' OR code IN '.$srclist)){
    while($row = $result->fetch_assoc()){
        $checktypes[$row['code']] = $row;
    }
    mysqli_free_result($result);
}

$fields = array();
if($result = mysqli_query($mysqli, 'SELECT * FROM Field WHERE checktype IN '.$srclist.' OR source_name IN (SELECT source_name FROM CheckType WHERE source_code IN '.$srclist.' OR code IN '.$srclist.')')){
    while($row = $result->fetch_assoc()){
        $fields[$row['checktype']?$row['checktype']:$row['source_name']][$row['name']] = array('type'=>$row['type'], 'title'=>$row['title'], 'description'=>$row['description']);
    }
    mysqli_free_result($result);
}
//var_dump($checktypes); echo "\n\n";
//var_dump($fields); echo "\n\n";

if ($params['async']) {
    $response = logResponse(array(),0);
    print $response;
    session_write_close();
    fastcgi_finish_request();
}

$plugin_interface = array();
$response = runRequests($params);
print $response;
session_write_close();
fastcgi_finish_request();
try {
    if (isset($keydb['db'])) $keydb['db']->close();
} catch (Exception $e) {
}
try {
    if (isset($rabbitmq['connection'])) $rabbitmq['connection']->disconnect();
} catch (Exception $e) {
}

/*
$fout2 = fopen($logpath.'response.'.date('Y-m-d').'.log', 'a');
fputs($fout2, date("Y-m-d H:i:s")."\n".$response."\n\n");
fclose($fout2);
*/

//////////////////////////////////////////////////////////

function logResponse($results,$status)
{
    global $mysqli;
    global $mysqls;
    global $reqId;
    global $xmlpath;
    global $params;
    global $userId;
    global $clientId;
    global $restime;

    $restime = date("Y-m-d\TH:i:s");

    if ($params['async'] || $status) {
        $response = generateResponse($results,$status);
        if ($status) {
            $mysqli->query("UPDATE RequestNew set status=$status".($status?",processed_at='$restime'":"")." WHERE id=$reqId");
            $mysqli->query("INSERT INTO RequestSource SELECT $reqId,min(created_at),min(created_date),$userId,$clientId,source_id,source_name,start_param,SUM(1),SUM(res_code<400),SUM(res_code=200),SUM(res_code>=400),MAX(process_time) FROM ResponseNew WHERE request_id=$reqId and res_code>0 GROUP BY source_id,source_name,start_param");
/*
            if ($mysqls) {
                $mysqls->query("UPDATE RequestNew set status=$status".($status?",processed_at='$restime'":"")." WHERE id=$reqId");
                $mysqls->query("INSERT INTO RequestSource SELECT $reqId,min(created_at),min(created_date),$userId,$clientId,source_id,source_name,start_param,SUM(1),SUM(res_code<400),SUM(res_code=200),SUM(res_code>=400),MAX(process_time) FROM ResponseNew WHERE request_id=$reqId and res_code>0 GROUP BY source_id,source_name,start_param");
            } elseif (isset($dbstat['server'])) {
                file_put_contents('dbstat_append.sql',"UPDATE RequestNew set status=$status".($status?",processed_at='$restime'":"")." WHERE id=$reqId;\n",FILE_APPEND);
                file_put_contents('dbstat_append.sql',"INSERT INTO RequestSource SELECT $reqId,min(created_at),min(created_date),$userId,$clientId,source_id,source_name,start_param,SUM(1),SUM(res_code<400),SUM(res_code=200),SUM(res_code>=400),MAX(process_time) FROM ResponseNew WHERE request_id=$reqId and res_code>0 GROUP BY source_id,source_name,start_param\n",FILE_APPEND);
            }
*/
        }

        $sid = str_pad($reqId,9,'0',STR_PAD_LEFT);
        $dir = $xmlpath.substr($sid,0,3).'/'.substr($sid,3,3);
        if (!is_dir($dir)) @mkdir($dir,0777,true);
        file_put_contents($dir.'/'.substr($sid,6,3).'_res.xml',$response);
/*
//        if ($status && ($userId==2783 || $userId==2840 || $userId==2899)) {
//            $url = 'https://infosphere'.($userId==2899?'':'-test').'.sberleasing.ru:18093/sendInfoSphereDataResult';
            $url = 'https://i-sphere.ru/2.00/save.php';
//            $user = 'esb';
//            $password = 'xYlkFYl3ZG0decgv';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
//            curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$password); 
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $response);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $content = curl_exec($ch);
            if (curl_errno($ch)) {
                file_put_contents('logs/callback_'.$reqId.'_'.time().'.err',curl_error($ch));
            } else {
                file_put_contents('logs/callback_'.$reqId.'_'.time().'.xml',$content);
            }
            curl_close($ch);
//        }
*/
/*
        if ($status && ($clientId==265)) {
            $url = 'https://isphere-services-collector-master.git.i-sphere.ru/collector';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            $header = array(
                'Content-Type: application/json',
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            $content = curl_exec($ch);
            if (curl_errno($ch)) {
                file_put_contents('logs/collector_'.$reqId.'_'.time().'.err',curl_error($ch));
            } else {
                file_put_contents('logs/collector_'.$reqId.'_'.time().'.xml',$content);
            }
            curl_close($ch);
        }
*/
        if ($status && ($clientId==265)) {
            $start = microtime(true);
            $url = 'http://172.16.0.24:8000/api/v1/files/responses/'.$reqId;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $response);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            $header = array(
                'Content-Type: application/xml',
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            $content = curl_exec($ch);
            $process_time = number_format(microtime(true)-$start,2,'.','');
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                file_put_contents('./logs/isto-new-'.date('Y-m-d').'.log',date('Y-m-d H:i:s')." id=$reqId processtime=$process_time error $error\n",FILE_APPEND);
            } else {
                file_put_contents('./logs/isto-new-'.date('Y-m-d').'.log',date('Y-m-d H:i:s')." id=$reqId processtime=$process_time success\n",FILE_APPEND);
            }
            curl_close($ch);
        }
        return $response;
    }
}

function logSourceResult($result)
{
           global $mysqli;
           global $mysqls;
           global $userId;
           global $clientId;
           global $reqId;
           global $reqdate;
           global $reqtime;

           if ($result->getSourceName()=='') return;

           $id = $result->getInitData();
           $xml='';
           $error = 'NULL';
           $rCount = 0;
           $rCode = 0;
           $rData = '';
           $data = $result->getResultData();

           if($result->getError()) {
               $error = "'".$result->getError()."'";
               $rCode = 500;
           } elseif($result->getResultData() instanceof ResultDataList) {
               $rData = $result->getResultData();
               $rCount = $rData->getResultsCount();
               $rCode = $rCount>0?200:204;
               $status = 1;

               foreach($rData->getResults() as $record)
                   compileField($xml, $record);
           } else {
               return;
           }

           $mysqli->query("INSERT INTO ResponseNew (request_id, user_id, client_id, source_name, checktype, start_param, check_index, check_level, created_at, created_date, processed_at, process_time, result_count, res_code) VALUES
               ($reqId,$userId,$clientId,'".$result->getSourceName()."','".$result->getCheckType()."','".$result->getStart()."',".$result->getLevel().",".substr_count($result->getPath(),'/').",FROM_UNIXTIME(".$result->startTime()."),'$reqdate',FROM_UNIXTIME(".$result->endTime()."),".$result->processTime().",".$rCount.",".$rCode.")");
           $resId = $mysqli->insert_id;
/*
           if ($mysqls) {
               $mysqls->query("INSERT INTO ResponseNew (id, request_id, user_id, client_id, source_name, checktype, start_param, check_index, check_level, created_at, created_date, processed_at, process_time, result_count, res_code) VALUES
                   ($resId,$reqId,$userId,$clientId,'".$result->getSourceName()."','".$result->getCheckType()."','".$result->getStart()."',".$result->getLevel().",".substr_count($result->getPath(),'/').",FROM_UNIXTIME(".$result->startTime()."),'$reqdate',FROM_UNIXTIME(".$result->endTime()."),".$result->processTime().",".$rCount.",".$rCode.")");
           } elseif (isset($dbstat['server'])) {
               file_put_contents('dbstat_append.sql',"INSERT INTO ResponseNew (id, request_id, user_id, client_id, source_name, checktype, start_param, check_index, check_level, created_at, created_date, processed_at, process_time, result_count, res_code) VALUES
                   ($resId,$reqId,$userId,$clientId,'".$result->getSourceName()."','".$result->getCheckType()."','".$result->getStart()."',".$result->getLevel().",".substr_count($result->getPath(),'/').",FROM_UNIXTIME(".$result->startTime()."),'$reqdate',FROM_UNIXTIME(".$result->endTime()."),".$result->processTime().",".$rCount.",".$rCode.");\n",FILE_APPEND);
           }
*/
           if($result->getError()) {
               $mysqli->query("INSERT INTO ResponseError (response_id, text) VALUES ($resId,'".mysqli_real_escape_string($mysqli,$result->getError())."')");
/*
               if ($mysqls) {
                   $mysqls->query("INSERT INTO ResponseError (response_id, text) VALUES ($resId,'".mysqli_real_escape_string($mysqli,$result->getError())."')");
               } elseif (isset($dbstat['server'])) {
                   file_put_contents('dbstat_append.sql',"INSERT INTO ResponseError (response_id, text) VALUES ($resId,'".mysqli_real_escape_string($mysqli,$result->getError())."');\n",FILE_APPEND);
               }
*/
           } elseif($result->getResultData() instanceof ResultDataList) {
               $source_name = $result->getPlugin()->getName()?$result->getPlugin()->getName():$result->getSourceName();
               $checktype = $result->getCheckType();
               $rData = $result->getResultData();
               foreach($rData->getResults() as $record) {
                   if (sizeof($record)) {
//                       $mysqli->query("INSERT INTO Record (response_id) VALUES ($resId)");
//                       $recId = $mysqli->insert_id;
                       foreach($record as $field) if (!isset($fields[$checktype][$field->getName()])) {
                           $query = $mysqli->query("SELECT id FROM Field WHERE source_name='".$source_name."' AND checktype='".$checktype."' AND name='".$field->getName()."'");
                           if ($query) {
                               if ($row=$query->fetch_object()) {
                                   $fieldId = $row->id;
                               } else  {
                                   $mysqli->query("INSERT INTO Field (source_name, checktype, name, type, title, description) VALUES
                                       ('".$source_name."','".$checktype."','".$field->getName()."','".$field->getType()."','".$field->getTitle()."','".$field->getDesc()."')");
                                   $fieldId = $mysqli->insert_id;
                               }
/*
                               $value = $mysqli->query("INSERT INTO Value (record_id, field_id, value) VALUES ($recId,$fieldId,'".preg_replace('/(?:\\\\u[\pL\p{Zs}])+/','',strval($field->getValue()))."')");
                               if ($value) {
                               } else {
// Ошибка при сохранении значения поля                         
//                                   print "INSERT INTO Value (record_id, field_id, value) VALUES ($recId,$fieldId,'".preg_replace('/(?:\\\\u[\pL\p{Zs}])+/','',strval($field->getValue()))."')\n";
                               }
*/
                           } else {
// Ошибка при поиске поля                         
                           }
                       }
                   }
               }
           }
}

function logRequest($params,$request)
{
    global $mysqli;
    global $mysqls;
    global $userId;
    global $clientId;
    global $xmlpath;
    global $reqdate;
    global $reqtime;

    $mysqli->query("INSERT INTO RequestNew (created_at, created_date, ip, user_id, client_id, external_id, channel, type, `recursive`, status) VALUES ('$reqtime','$reqdate','".$params['user_ip']."',$userId,$clientId,".($params['request_id']?"'".$params['request_id']."'":"NULL").",".(strpos($params['request_type'],'check')===0?"1":"0").",".($params['request_type']?"'".$params['request_type']."'":"NULL").",".($params['recursive']?"1":"0").",0)");
    $id = $mysqli->insert_id;
/*
    if ($mysqls) {
        $mysqls->query("INSERT INTO RequestNew (id, created_at, created_date, ip, user_id, client_id, external_id, channel, type, `recursive`, status) VALUES ($id,'$reqtime','$reqdate','".$params['user_ip']."',$userId,$clientId,".($params['request_id']?"'".$params['request_id']."'":"NULL").",".(strpos($params['request_type'],'check')===0?"1":"0").",".($params['request_type']?"'".$params['request_type']."'":"NULL").",".($params['recursive']?"1":"0").",0)");
    } elseif (isset($dbstat['server'])) {
        file_put_contents('dbstat_append.sql',"INSERT INTO RequestNew (id, created_at, created_date, ip, user_id, client_id, external_id, channel, type, `recursive`, status) VALUES ($id,'$reqtime','$reqdate','".$params['user_ip']."',$userId,$clientId,".($params['request_id']?"'".$params['request_id']."'":"NULL").",".(strpos($params['request_type'],'check')===0?"1":"0").",".($params['request_type']?"'".$params['request_type']."'":"NULL").",".($params['recursive']?"1":"0").",0);\n",FILE_APPEND);
    }
*/
    $sid = str_pad($id,9,'0',STR_PAD_LEFT);
    $dir = $xmlpath.substr($sid,0,3).'/'.substr($sid,3,3);
    if (!is_dir($dir)) @mkdir($dir,0777,true);
    file_put_contents($dir.'/'.substr($sid,6,3).'_req.xml',$request);

    return $id;
}

function authUserId($xml)
{
    global $mysqli;
    preg_match('/<UserID>([^<]+)/', $xml, $userid);
    preg_match('/<Password>([^<]+)/', $xml, $password);

    $authData = false;
    if(isset($userid[1]) && isset($password[1]))
    {
        $userid = $userid[1];
        $password = $password[1];

        $select = "SELECT Id FROM isphere.SystemUsers WHERE Login='".mysqli_real_escape_string($mysqli,$userid)."' AND (Password='".mysqli_real_escape_string($mysqli,$password)."' OR Password='".md5($password)."') AND (Locked IS NULL OR Locked=0) AND (StartTime IS NULL OR StartTime<CURRENT_TIMESTAMP) AND (EndTime IS NULL OR EndTime>CURRENT_TIMESTAMP) LIMIT 1";
        $sqlRes = $mysqli->query($select);

        if($sqlRes)
            $authData = $sqlRes->fetch_array();
//        if(isset($authData[0]['Id']))
          if(isset($authData['Id']))
            return $authData['Id'];
        
    }

    echo '
    <error>
        <code>401</code>
        <message>Пользователь не авторизован</message>
    </error>';
    header('HTTP/1.1 401 Unauthorized'); 
    exit();
}

function parseParams($xml)
{
    global $total_timeout;
    $params = array('user_ip'=>$_SERVER['REMOTE_ADDR'],'request_id'=>false,'request_type'=>false,'sources'=>array(),'rules'=>array(),'recursive'=>0,'async'=>0,'timeout'=>$total_timeout,'person'=>array(),'phone'=>array(),'email'=>array(),'nick'=>array(),'url'=>array(),'car'=>array(),'ip'=>array(),'org'=>array(),'card'=>array(),'fssp_ip'=>array(),'osago'=>array(),'text'=>array());

    if ($_SERVER['REMOTE_ADDR']=='127.0.0.1' && preg_match('/<UserIP>([^<]+)/', $xml, $res)) {
        $params['user_ip'] = trim($res[1]);
    }

    if(preg_match('/<requestId>([^<]+)/', $xml, $res)) {
        $params['request_id'] = trim($res[1]);
        $params['request_id'] = preg_replace("/[^A-Za-z0-9\.\-\_\/]/u","_",$params['request_id']);
        if (strlen($params['request_id'])>100) $params['request_id']=substr($params['request_id'],0,100);
    }
	    
    if($_SERVER['REMOTE_ADDR']=='127.0.0.1' && preg_match('/<requestType>([^<]+)/', $xml, $res)) {
        $params['request_type'] = trim($res[1]);
    }
	    
    if(preg_match('/<sources>([^<]+)/', $xml, $res)) {
        $params['sources'] = explode(',',$res[1]);
        foreach($params['sources'] as $i => $source) $params['sources'][$i] = strtr(strtolower($source),array(' '=>'',' '=>''));
    }
	    
    if(preg_match('/<rules>([^<]+)/', $xml, $res)) {
        $params['rules'] = explode(',',$res[1]);
        foreach($params['rules'] as $i => $rule) $params['rules'][$i] = strtolower(trim($rule));
    }
	    
    if(preg_match('/<recursive>([^<]+)/', $xml, $res) && intval($res[1])) {
        $params['recursive'] = intval($res[1]);
    }
    if(preg_match('/<async>([^<]+)/', $xml, $res) && intval($res[1])) {
        $params['async'] = intval($res[1]);
    }
    if((preg_match('/<timeout>([^<]+)/', $xml, $res) || preg_match('/<Timeout>([^<]+)/', $xml, $res)) && intval($res[1]) && intval($res[1])<=600) {
        $total_timeout = $params['timeout'] = intval($res[1]);
    }
	    
    if(preg_match('/<PersonReq/', $xml))
    {
        if(preg_match('/<paternal>([^<]+)/', $xml, $res) && trim($res[1]))
            $params['person']['last_name'] = trim(strtr(html_entity_decode($res[1],ENT_COMPAT,"UTF-8"),array('​'=>'')));

        if(preg_match('/<first>([^<]+)/', $xml, $res) && trim($res[1]))
            $params['person']['first_name'] = trim(strtr(html_entity_decode($res[1],ENT_COMPAT,"UTF-8"),array('​'=>'')));

        if(preg_match('/<middle>([^<]+)/', $xml, $res) && trim($res[1])) {
            $m = trim(strtr(html_entity_decode($res[1],ENT_COMPAT,"UTF-8"),array('​'=>'')));
            if ($m!='-' && $m!='--' && mb_strtoupper($m)!='NONE' && mb_strtoupper($m)!='НЕТ' && mb_strtoupper($m)!='ОТСУТСТВУЕТ')
                $params['person']['patronymic'] = $m;
        }

        if(preg_match('/<birthDt>([^<]+)/', $xml, $res) && strtotime(trim($res[1])))
            $params['person']['date'] = date('d.m.Y',strtotime(trim($res[1])));

	if(preg_match('/<placeOfBirth>([^<]+)/', $xml, $res) && trim($res[1]))
	    $params['person']['placeOfBirth'] = trim(strtr(html_entity_decode($res[1],ENT_COMPAT,"UTF-8"),array('​'=>'')));

        if(preg_match('/<passport_series>([^<]+)/', $xml, $res) && trim($res[1]))
            $params['person']['passport_series'] = sprintf("%04d",strtr($res[1],array(' '=>'','​'=>'')));

        if(preg_match('/<passport_number>([^<]+)/', $xml, $res) && trim($res[1]))
            $params['person']['passport_number'] = sprintf("%06d",strtr($res[1],array(' '=>'','​'=>'')));
	    
	if(preg_match('/<issueDate>([^<]+)/', $xml, $res) && strtotime(trim($res[1])))
	    $params['person']['issueDate'] = date('d.m.Y',strtotime(trim($res[1])));
	    
        if(preg_match('/<issueAuthority>([^<]+)/', $xml, $res) && trim($res[1]))
	    $params['person']['issueAuthority'] = trim(strtr(html_entity_decode($res[1],ENT_COMPAT,"UTF-8"),array('​'=>'')));

        if(preg_match('/<driver_number>([^<]+)/', $xml, $res) && trim($res[1]))
            $params['person']['driver_number'] = trim(strtr(mb_strtoupper(html_entity_decode($res[1],ENT_COMPAT,"UTF-8")),array(' '=>'','​'=>'','№'=>'','N'=>'','A'=>'А','B'=>'В','C'=>'С','E'=>'Е','H'=>'Н','K'=>'К','M'=>'М','O'=>'О','P'=>'Р','T'=>'Т','Y'=>'У','X'=>'Х','a'=>'а','c'=>'с','e'=>'е','k'=>'к','m'=>'м','o'=>'о','p'=>'р','t'=>'т','y'=>'у','x'=>'х')));
	    
	if(preg_match('/<driver_date>([^<]+)/', $xml, $res) && strtotime(trim($res[1])))
	    $params['person']['driver_date'] = date('d.m.Y',strtotime(trim($res[1])));
	    
        if(preg_match('/<inn>([^<]+)/', $xml, $res) && ($inn = normal_inn(trim($res[1]))))
            if (strlen($inn)==10) $params['org']['inn'] = $inn;
            else $params['person']['inn'] = $inn;
	    
        if(preg_match('/<snils>([^<]+)/', $xml, $res) && ($snils = normal_snils(trim($res[1]))))
            $params['person']['snils'] = $snils;
	    
        if(preg_match('/<region_id>([^<]+)/', $xml, $res))
            $params['person']['region_id'] = trim($res[1]);

	if(preg_match('/<homeaddress>([^<]+)/', $xml, $res) && trim($res[1]))
	    $params['person']['homeaddress'] = trim(strtr(html_entity_decode($res[1],ENT_COMPAT,"UTF-8"),array('​'=>'')));
	    
	if(preg_match('/<homeaddressArr>([^<]+)/', $xml, $res))
	    $params['person']['homeaddressArr'] = trim(res[1]);
	    
	if(preg_match('/<regaddress>([^<]+)/', $xml, $res) && trim($res[1]))
	    $params['person']['regaddress'] = trim(strtr(html_entity_decode($res[1],ENT_COMPAT,"UTF-8"),array('​'=>'')));
	    
	if(preg_match('/<regaddressArr>([^<]+)/', $xml, $res))
	    $params['person']['regaddressArr'] = trim($res[1]);

        if(preg_match('/<bik>([^<]+)/', $xml, $res) && trim($res[1]))
            $params['person']['bik'] = trim($res[1]);
    }

    if(preg_match('/<PhoneReq/', $xml))
    {
        if(preg_match_all('/<phone>([^<]+)/', $xml, $res))
            foreach ($res[1] as $elem) {
                $list = preg_split("/[,;]+/",trim(html_entity_decode($elem)));
                foreach ($list as $phone)
                    if (($phone = normal_phone($phone)) && !in_array($phone,$params['phone']))
                        $params['phone'][] = $phone;
            }
    }

    if(preg_match('/<EmailReq/', $xml))
    {
        if(preg_match_all('/<email>([^<]+)/', $xml, $res))
            foreach ($res[1] as $elem) {
                $list = preg_split("/[\s,;]+/",trim(html_entity_decode($elem)));
                foreach ($list as $email)
                    if (($email = normal_email($email)) && !in_array($email,$params['email']))
                        $params['email'][] = $email;
            }
    }

    if(preg_match('/<SkypeReq/', $xml))
    {
        if(preg_match_all('/<skype>([^<]+)/', $xml, $res))
            foreach ($res[1] as $elem) {
                $list = preg_split("/[\s,;]+/",trim(html_entity_decode($elem)));
                foreach ($list as $skype)
                    if (trim($skype)) $params['nick'][] = trim($skype);
            }
    }

    if(preg_match('/<TelegramReq/', $xml))
    {
        if(preg_match_all('/<telegram>([^<]+)/', $xml, $res))
            foreach ($res[1] as $elem) {
                $list = preg_split("/[\s,;]+/",trim(html_entity_decode($elem)));
                foreach ($list as $telegram)
                    if (trim($telegram)) $params['nick'][] = trim($telegram);
            }
    }

    if(preg_match('/<NickReq/', $xml))
    {
        if(preg_match_all('/<nick>([^<]+)/', $xml, $res))
            foreach ($res[1] as $elem) {
                $list = preg_split("/[\s,;]+/",trim(html_entity_decode($elem)));
                foreach ($list as $nick)
                    if (trim($nick)) $params['nick'][] = trim($nick);
            }
    }

    if(preg_match('/<URLReq/', $xml))
    {
        if(preg_match_all('/<url>([^<]+)/', $xml, $res))
            foreach ($res[1] as $elem) {
                $list = preg_split("/[\s,;]+/",trim(html_entity_decode($elem)));
                foreach ($list as $url)
                    if (trim($url)) $params['url'][] = trim($url);
            }
    }

    if(preg_match('/<CarReq/', $xml))
    {
        if(preg_match('/<vin>([^<]+)/', $xml, $res) && ($vin = trim(strtr(mb_strtoupper(html_entity_decode($res[1],ENT_COMPAT,"UTF-8")),array('ОТСУТСТВУЕТ'=>'',' '=>'','​'=>'','I'=>'1','O'=>'0','Q'=>'0','А'=>'A','В'=>'B','С'=>'C','Е'=>'E','Н'=>'H','К'=>'K','М'=>'M','О'=>'0','Р'=>'P','Т'=>'T','У'=>'Y','Х'=>'X','а'=>'a','с'=>'c','е'=>'e','к'=>'k','м'=>'m','о'=>'0','р'=>'p','т'=>'t','у'=>'y','х'=>'x')))))
            $params['car']['vin'] = $vin;

        if(preg_match('/<bodynum>([^<]+)/', $xml, $res) && ($bodynum = trim(strtr(mb_strtoupper(html_entity_decode($res[1],ENT_COMPAT,"UTF-8")),array('ОТСУТСТВУЕТ'=>'',' '=>'','​'=>'','А'=>'A','В'=>'B','С'=>'C','Е'=>'E','Н'=>'H','К'=>'K','М'=>'M','О'=>'O','Р'=>'P','Т'=>'T','У'=>'Y','Х'=>'X','а'=>'a','с'=>'c','е'=>'e','к'=>'k','м'=>'m','о'=>'o','р'=>'p','т'=>'t','у'=>'y','х'=>'x')))))
            $params['car']['bodynum'] = $bodynum;

        if(preg_match('/<chassis>([^<]+)/', $xml, $res) && ($chassis = trim(strtr(mb_strtoupper(html_entity_decode($res[1],ENT_COMPAT,"UTF-8")),array('ОТСУТСТВУЕТ'=>'',' '=>'','​'=>'','А'=>'A','В'=>'B','С'=>'C','Е'=>'E','Н'=>'H','К'=>'K','М'=>'M','О'=>'O','Р'=>'P','Т'=>'T','У'=>'Y','Х'=>'X','а'=>'a','с'=>'c','е'=>'e','к'=>'k','м'=>'m','о'=>'o','р'=>'p','т'=>'t','у'=>'y','х'=>'x')))))
            $params['car']['chassis'] = $chassis;

        if(preg_match('/<regnum>([^<]+)/', $xml, $res) && trim($res[1]))
            $params['car']['regnum'] = trim(strtr(mb_strtoupper(html_entity_decode($res[1],ENT_COMPAT,"UTF-8")),array(' '=>'','​'=>'','A'=>'А','B'=>'В','C'=>'С','E'=>'Е','H'=>'Н','K'=>'К','M'=>'М','O'=>'О','P'=>'Р','T'=>'Т','Y'=>'У','X'=>'Х','a'=>'а','c'=>'с','e'=>'е','k'=>'к','m'=>'м','o'=>'о','p'=>'р','t'=>'т','y'=>'у','x'=>'х')));

        if(preg_match('/<ctc>([^<]+)/', $xml, $res) && trim($res[1]))
            $params['car']['ctc'] = trim(strtr(mb_strtoupper(html_entity_decode($res[1],ENT_COMPAT,"UTF-8")),array(' '=>'','​'=>'','№'=>'','N'=>'','A'=>'А','B'=>'В','C'=>'С','E'=>'Е','H'=>'Н','K'=>'К','M'=>'М','O'=>'О','P'=>'Р','T'=>'Т','Y'=>'У','X'=>'Х','a'=>'а','c'=>'с','e'=>'е','k'=>'к','m'=>'м','o'=>'о','p'=>'р','t'=>'т','y'=>'у','x'=>'х')));

        if(preg_match('/<pts>([^<]+)/', $xml, $res) && trim($res[1]))
            $params['car']['pts'] = trim(strtr(mb_strtoupper(html_entity_decode($res[1],ENT_COMPAT,"UTF-8")),array(' '=>'','​'=>'','№'=>'','N'=>'','A'=>'А','B'=>'В','C'=>'С','E'=>'Е','H'=>'Н','K'=>'К','M'=>'М','O'=>'О','P'=>'Р','T'=>'Т','Y'=>'У','X'=>'Х','a'=>'а','c'=>'с','e'=>'е','k'=>'к','m'=>'м','o'=>'о','p'=>'р','t'=>'т','y'=>'у','x'=>'х')));

        if(preg_match('/<reqdate>([^<]+)/', $xml, $res) && trim($res[1]))
            $params['car']['reqdate'] = trim($res[1]);
    }

    if(preg_match('/<IPReq/', $xml))
    {
        if(preg_match_all('/<ip>([^<]+)/', $xml, $res))
            foreach ($res[1] as $elem) {
                $list = preg_split("/[\s,;]+/",trim(html_entity_decode($elem)));
                foreach ($list as $ip)
//                if ($ip = normal_ip($ip))
                    if (trim($ip)) $params['ip'][] = trim($ip);
            }
    }

    if(preg_match('/<OrgReq/', $xml))
    {
        if(preg_match('/<inn>([^<]+)/', $xml, $res) && ($inn = normal_inn(trim($res[1]))))
            if (strlen($inn)==12) $params['person']['inn'] = $inn;
            else $params['org']['inn'] = $inn;

        if(preg_match('/<ogrn>([^<]+)/', $xml, $res) && trim($res[1]))
            $params['org']['ogrn'] = trim($res[1]);

        if(preg_match('/<name>([^<]+)/', $xml, $res) && ($name = trim(strtr(html_entity_decode($res[1],ENT_COMPAT,"UTF-8"),array('​'=>'')))))
            $params['org']['name'] = $name;

        if(preg_match('/<address>([^<]+)/', $xml, $res) && ($address = trim(strtr(html_entity_decode($res[1],ENT_COMPAT,"UTF-8"),array('​'=>'')))))
            $params['org']['address'] = $address;

        if(preg_match('/<region_id>([^<]+)/', $xml, $res))
            $params['org']['region_id'] = trim($res[1]);

        if(preg_match('/<bik>([^<]+)/', $xml, $res) && trim($res[1]))
            $params['org']['bik'] = trim($res[1]);
    }

    if(preg_match('/<CardReq/', $xml))
    {
        if(preg_match_all('/<card>([^<]+)/', $xml, $res))
            foreach ($res[1] as $card)
                if (/*($card = normal_card($card)) && */!in_array($card,$params['card']))
                    $params['card'][] = preg_replace("/\D/","",trim($card));
    }

    if(preg_match('/<TextReq/', $xml))
    {
        if(preg_match_all('/<text>([^<]+)/', $xml, $res))
            foreach ($res[1] as $text)
                $params['text'][] = trim($text);
    }

    if(preg_match('/<OtherReq/', $xml))
    {
        if(preg_match_all('/<fsspip>([^<]+)/', $xml, $res))
            foreach ($res[1] as $fsspip)
                $params['fssp_ip'][] = trim($fsspip);

        if(preg_match_all('/<osago>([^<]+)/', $xml, $res))
            foreach ($res[1] as $osago)
                $params['osago'][] = trim(strtr(mb_strtoupper(html_entity_decode($osago,ENT_COMPAT,"UTF-8")),array(' '=>'','​'=>'','A'=>'А','B'=>'В','C'=>'С','E'=>'Е','H'=>'Н','K'=>'К','M'=>'М','O'=>'О','P'=>'Р','T'=>'Т','Y'=>'У','X'=>'Х','a'=>'а','c'=>'с','e'=>'е','k'=>'к','m'=>'м','o'=>'о','p'=>'р','t'=>'т','y'=>'у','x'=>'х')));
    }

    return $params;
}

function initPlugins($sources)
{
    global $clientId;
    global $userId;

    $fms = 'FMSPlugin';
    $fmsdb = 'FMSDBPlugin';
    $mvdwanted = 'MVDWantedPlugin';
    $gosuslugi = 'GosuslugiPlugin';
    $fns = 'FNSPlugin';
    $pfr = 'PFRPlugin';
    $egrul = 'EGRULPlugin';
    $gisgmp = 'GISGMPPlugin';
    $notariat = 'NotariatPlugin';
    $fssp = 'FSSPPlugin';
    $fsspapi = 'FSSPAPIPlugin';
    $fsspapp = 'FSSPAppPlugin';
    $vestnik = 'VestnikPlugin';
    $gks = 'GKSPlugin';
    $kad = 'KADPlugin';
    $zakupki = 'ZakupkiPlugin';
    $bankrot = 'BankrotPlugin';
    $cbr = 'CBRPlugin';
    $terrorist = 'TerroristPlugin';
    $croinform = 'CROINFORMPlugin';
    $nbki = 'NBKIPlugin';
    $people = 'YaPeoplePlugin';
    $vk = 'VKPlugin';
    $ok = 'OKPlugin';
    $okapp = 'OKAppPlugin';
    $okappr = 'OKAppRPlugin';
    $mailru = 'MailRuPlugin';
    $fotostrana = 'FotostranaPlugin';

    $rossvyaz = 'RossvyazPlugin';
    $smsc = 'SMSCPlugin';
    $infobip = 'InfobipPlugin';
    $stream = 'StreamPlugin';
    $smspilot = 'SMSPilotPlugin';
    $hlr = 'HLRPlugin';
    $facebook = 'FacebookPlugin';
    $instagram = 'InstagramPlugin';
    $twitter = 'TwitterPlugin';
    $hh = 'HHPlugin';
    $whatsapp = 'WhatsAppPlugin';
    $whatsappweb = 'WhatsAppWebPlugin';
//    $checkwa = 'CheckWAPlugin';
    $announcement = 'AnnouncementPlugin';
    $boards = 'BoardsPlugin';
    $yamap = 'YaMapPlugin';
    $gis = 'GISPlugin';
    $listorg = 'ListOrgPlugin';
    $commerce = 'CommercePlugin';
    $viber = 'ViberPlugin';
    $viberwin = 'ViberWinPlugin';
    $telegram = 'TelegramPlugin';
    $telegramweb = 'TelegramWebPlugin';
    $icq = 'ICQPlugin';
    $truecaller = 'TrueCallerPlugin';
    $truecallerweb = 'TrueCallerWebPlugin';
    $tc = 'TCPlugin';
    $emt = 'EMTPlugin';
    $getcontact = 'GetContactPlugin';
    $getcontactapp = 'GetContactAppPlugin';
    $callapp = 'CallAppPlugin';
    $simpler = 'SimplerPlugin';
    $eyecon = 'EyeConPlugin';
    $numbuster = 'NumBusterPlugin';
    $numbusterapp = 'NumBusterAppPlugin';
    $numbusterpro = 'NumBusterProPlugin';
    $names = 'NamesPlugin';
    $phones = 'PhonesPlugin';
    $vkcheck = 'VKCheckPlugin';
    $vkauth = 'VKAuthPlugin';
    $okbot= 'OKBotPlugin';
    $sberbank = 'SberbankPlugin';
/*
    $tinkoff = 'TinkoffPlugin';
    $banks = 'BanksPlugin';
    $visa = 'VISAPlugin';
    $sbert = 'SberTPlugin';
    $alfabankt = 'AlfabankTPlugin';
    $raiffeisent = 'RaiffeisenTPlugin';
    $psbankt = 'PSBankTPlugin';
    $rosbankt = 'RosbankTPlugin';
    $raiffeisen = 'RaiffeisenPlugin';
    $tinkoffr = 'TinkoffRPlugin';
    $alfabankr = 'AlfabankRPlugin';
    $psbankr = 'PSBankRPlugin';
    $rosbankr = 'RosbankRPlugin';
    $sovcombankr = 'SovcombankRPlugin';
    $gazprombankr = 'GazprombankRPlugin';
    $qiwibankr = 'QiwibankRPlugin';
*/
    $sberw = 'SberWPlugin';
    $sbers = 'SberSPlugin';
    $sbpw = 'SBPWPlugin';
    $sbps = 'SBPSPlugin';
/*
    $sovcombank = 'SovcombankPlugin';
    $tinkoffs = 'TinkoffSPlugin';
    $alfabanks = 'AlfabankSPlugin';
    $psbanks = 'PSBankSPlugin';
    $raiffeisens = 'RaiffeisenSPlugin';
*/
    $sb = 'SBPlugin';
    $phonenumber = 'PhoneNumberPlugin';
    $avinfo = 'AvInfoPlugin';
    $beholder = 'BeholderPlugin';
    $microsoft = 'MicrosoftPlugin';
    $skype = 'SkypePlugin';
    $googleplus = 'GooglePlusPlugin';
    $google = 'GooglePlugin';
    $googler = 'GoogleRPlugin';
    $apple = 'ApplePlugin';
    $qiwi = 'QiwiPlugin';
    $yamoney = 'YaMoneyPlugin';
    $elecsnet = 'ElecsnetPlugin';
    $webmoney = 'WebMoneyPlugin';
    $pochta = 'PochtaPlugin';
    $aeroflot = 'AeroflotPlugin';
    $uralair = 'UralAirPlugin';
    $rzd = 'RZDPlugin';
    $papajohns = 'PapaJohnsPlugin';
    $biglion = 'BiglionPlugin';
    $avito = 'AvitoPlugin';
    $domclick = 'DomclickPlugin';
    $sber = 'SberPlugin';

    $gibdd = 'GIBDDPlugin';
    $eaisto = 'EAISTOPlugin';
    $rsa = 'RSAPlugin';
    $kbm = 'KBMPlugin';
    $rz = 'RZPlugin';
    $reestrzalogov = 'ReestrZalogovPlugin';
//    $autoru = 'AutoRuPlugin';
    $vin = 'VINPlugin';
    $avtokod = 'AvtoKodPlugin';
    $mosru = 'MosRuPlugin';
    $mosrufines = 'MosRuFinesPlugin';
    $nbkiauto = 'NBKIAutoPlugin';
    $avinfo = 'AvInfoPlugin';
    $ingos = 'IngosPlugin';

    $dns = 'DNSPlugin';
    $ripe = 'RIPEPlugin';
    $ipgeo = 'IPGeoBasePlugin';
    $sypexgeo = 'SypexGeoPlugin';
    $shodan = 'ShodanPlugin';
    $censys = 'CensysPlugin';

    $test = 'TestPlugin';
    $testr = 'TestRPlugin';
    $uni = 'UniPlugin';

    $plugins = array(
      'person' => array(
        'fms' => array('fms_passport' => $fms),
        'fmsdb' => array('fmsdb_passport' => $fmsdb),
        'gosuslugi' => array('gosuslugi_passport' => $gosuslugi, 'gosuslugi_inn' => $gosuslugi, 'gosuslugi_snils' => $gosuslugi),
        'fns' => array('fns_inn' => $fns, 'fns_bi' => $fns, 'fns_disqualified' => $fns, 'fns_mru' => $fns, 'fns_npd' => $fns, 'fns_invalid' => $fns, 'fns_rmsp' => $fns),
        'pfr' => array('pfr_person' => $pfr),
        'mvd' => array('mvd_wanted' => $mvdwanted),
        'gisgmp' => array('gisgmp_taxes' => $gisgmp, 'gisgmp_fssp' => $gisgmp, 'gisgmp_fines' => $gisgmp),
        'notariat' => array('notariat_person' => $notariat),
        'fssp' => array('fssp_person' => /*$fsspapp),
        'fsspsite' => array('fssp_person' => */$fssp),
        'fsspapi' => array('fssp_person' => $fsspapi),
        'fssp_suspect' => array('fssp_suspect' => $fssp),
        'bankrot' => array('bankrot_person' => $bankrot, 'bankrot_inn' => $bankrot),
        'cbr' => array('cbr_person' => $cbr),
        'terrorist' => array('terrorist_person' => $terrorist),
//        'croinform' => array('croinform_person' => $croinform),
//        'nbki' => array('nbki_credithistory' => $nbki),
//        'people' => array('people' => $people),
//        'facebook' => array('facebook_person' => $facebook),
        'vk' => array('vk_person' => $vk),
        'ok' => array('ok_person' => $ok),
//        'hh' => array('hh_person' => $hh),
        'rz' => array('rz_person' => $rz),
        'reestrzalogov' => array('reestrzalogov_person' => $reestrzalogov),
        'avtokod' => array('avtokod_driver' => $avtokod, 'avtokod_fines' => $mosrufines),
        'gibdd' => array('gibdd_driver' => $gibdd),
        'rsa' => array('rsa_kbm' => $rsa /*$kbm*/),
        'egrul' => array('egrul_person' => $egrul),
        'zakupki' => array(/*'zakupki_eruz' => $zakupki, */'zakupki_order' => $zakupki, 'zakupki_contract' => $zakupki, 'zakupki_fz223' => $zakupki, 'zakupki_capital' => $zakupki, 'zakupki_dishonest' => $zakupki, 'zakupki_guarantee' => $zakupki, 'zakupki_rkpo' => $zakupki),
        'kad' => array('kad_person' => $kad),
        '2gis' => array('2gis_inn' => $gis),
        'test' => array('test_person' => $test),
        'testr' => array('test_person' => $testr),
      ),
      'phone' => array(
        'gosuslugi' => array('gosuslugi_phone' => $gosuslugi),
        'rossvyaz' => array('rossvyaz_phone' => $rossvyaz),
        'hlr' => array('hlr_phone' => $hlr /*$smspilot*/ /*$stream*/),
//        'ss7' => array('infobip_phone' => $stream),
        'smsc' => array('smsc_phone' => $smsc),
//        'infobip' => array('infobip_phone' => $infobip),
//        'sber' => array('sberbank_phone' => $sberbank),
/*
        'sberbank' => array('sberbank_phone' => $sbers),
        'sbertest' => array('sberbank_phone' => $sberw),
        'banks' => array('tinkoff_phone' => $sbps, 'alfabank_phone' => $sbps, 'vtb_phone' => $sbps, 'openbank_phone' => $sbps, 'psbank_phone' => $sbps, 'rosbank_phone' => $sbps, 'unicredit_phone' => $sbps, 'raiffeisen_phone' => $sbps, 'sovcombank_phone' => $sbps, 'gazprombank_phone' => $sbps, 'mkb_phone' => $sbps, 'rsb_phone' => $sbps, 'avangard_phone' => $sbps, 'qiwibank_phone' => $sbps, 'rnko_phone' => $sbps),
        'tinkoff' => array('tinkoff_phone' => $sbps),
        'alfabank' => array('alfabank_phone' => $sbps),
        'vtb' => array('vtb_phone' => $sbpw),
        'openbank' => array('openbank_phone' => $sbps),
        'psbank' => array('psbank_phone' => $sbps),
        'rosbank' => array('rosbank_phone' => $sbps),
        'unicredit' => array('unicredit_phone' => $sbps),
        'raiffeisen' => array('raiffeisen_phone' => $sbps),
        'sovcombank' => array('sovcombank_phone' => $sbps),
        'gazprombank' => array('gazprombank_phone' => $sbps),
        'mkb' => array('mkb_phone' => $sbps),
        'rsb' => array('rsb_phone' => $sbps),
        'avangard' => array('avangard_phone' => $sbps),
        'qiwibank' => array('qiwibank_phone' => $sbps),
        'rnko' => array('rnko_phone' => $sbps),
*/
//        'visa' => array('visa_phone' => $visa),
        'facebook' => array('facebook_phone' => $facebook),
        'vk' => array('vk_phone' => $vk, 'vk_phonecheck' => $vkcheck),
        'ok' => array('ok_phone' => $ok, 'ok_phonecheck' => $okbot, 'ok_phoneapp' => $okappr),
        'instagram' => array('instagram_phone' => $instagram),
        'twitter' => array('twitter_phone' => $twitter),
        'fotostrana' => array('fotostrana_phone' => $fotostrana),
//        'beholder' => array('beholder_phone' => $beholder),
        'microsoft' => array('microsoft_phone' => $microsoft),
        'skype' => array('skype_phone' => $skype),
        'googleplus' => array('googleplus_phone' => $googleplus),
        'google' => array( 'google_phone' => $google, 'google_name' => $google),
//        'googlename' => array('googlename_phone' => $google),
        'viber' => array('viber_phone' => $viber),
        'viberwin' => array('viberwin_phone' => $viberwin),
        'telegram' => array('telegram_phone' => $telegram),
//        'telegramweb' => array('telegramweb_phone' => $telegram),
//        'telegramweb' => array('telegramweb_phone' => $telegramweb),
//        'icq' => array('icq_phone' => $icq),
        'whatsapp' => array('whatsappweb_phone' => $whatsapp),
//        'whatsappweb' => array('whatsappweb_phone' => $whatsappweb),
        'whatsapp_phone' => array('whatsapp_phone' => $whatsapp),
        'hh' => array('hh_phone' => $hh),
        'truecaller' => array('truecaller_phone' => $truecaller/*, 'truecallerweb_phone' => $tc *//*$truecallerweb*/),
        'tc' => array('truecaller_phone' => $truecaller/*, 'truecallerweb_phone' => $tc *//*$truecallerweb*/),
        'emt' => array('emt_phone' => $emt),
//        'getcontactweb' => array('getcontactweb_phone' => $getcontact),
        'getcontact' => array('getcontact_phone' => $getcontactapp),
        'getcontacttags' => array('getcontacttags_phone' => $getcontactapp),
        'callapp' => array('callapp_phone' => $callapp),
        'simpler' => array('simpler_phone' => $simpler),
        'eyecon' => array('eyecon_phone' => $eyecon),
        'numbuster' => array('numbuster_phone' => $numbuster),
//        'numbusterapp' => array('numbuster_phone' => $numbusterapp),
        'numbusterpro' => array('numbusterpro_phone' => $numbusterpro),
        'names' => array('names_phone' => $names),
        'phones' => array('phones_phone' => $phones),
        'qiwi' => array('qiwi_phone' => $qiwi),
        'yamoney' => array('yamoney_phone' => $yamoney/*, 'yandexmoney_phone' => $sbps*/),
//        'elecsnet' => array('elecsnet_phone' => $elecsnet),
        'webmoney' => array('webmoney_phone' => $webmoney),
        'phonenumber' => array('phonenumber_phone' => $phonenumber),
        'announcement' => array('announcement_phone' => $announcement),
        'boards' => array('boards_phone' => $boards, 'boards_phone_kz' => $boards, 'boards_phone_by' => $boards, 'boards_phone_pl' => $boards, 'boards_phone_ua' => $boards, 'boards_phone_uz' => $boards, 'boards_phone_ro' => $boards, 'boards_phone_pt' => $boards, 'boards_phone_bg' => $boards),
        'commerce' => array('commerce_phone' => $commerce),
        'yamap' => array('yamap_phone' => $yamap),
        '2gis' => array('2gis_phone' => $gis),
        'egrul' => array('listorg_phone' => $listorg),
        'pochta' => array('pochta_phone' => $pochta),
        'aeroflot' => array('aeroflot_phone' => $aeroflot),
        'uralair' => array('uralair_phone' => $uralair),
        'papajohns' => array('papajohns_phone' => $papajohns),
        'avito' => array('avito_phone' => $avito),
        'biglion' => array('biglion_phone' => $biglion),
//        'avinfo' => array('avinfo_phone' => $avinfo),
//        'domclick' => array('domclick_phone' => $domclick),
        'sber' => array('sber_phone' => $sber),
        'test' => array('test_phone' => $test),
        'testr' => array('test_phone' => $testr),
      ),
      'email' => array(
        'gosuslugi' => array('gosuslugi_email' => $gosuslugi),
        'facebook' => array('facebook_email' => $facebook),
        'vk' => array('vk_email' => $vk, 'vk_emailcheck' => $vkcheck),
        'ok' => array('ok_email' => $ok, 'ok_emailcheck' => $okbot, 'ok_emailapp' => $okappr),
        'instagram' => array('instagram_email' => $instagram),
        'twitter' => array('twitter_email' => $twitter),
        'mailru' => array('mailru_email' => $mailru),
        'fotostrana' => array('fotostrana_email' => $fotostrana),
        'microsoft' => array('microsoft_email' => $microsoft),
        'skype' => array('skype_email' => $skype),
        'googleplus' => array('googleplus_email' => $googleplus),
        'google' => array('google_email' => $google, 'google_name' => $google),
//        'googlename' => array('googlename_email' => $google),
//        'apple' => array('apple_email' => $apple),
        'hh' => array('hh_email' => $hh),
        'commerce' => array('commerce_email' => $commerce),
        'egrul' => array('listorg_email' => $listorg),
        'aeroflot' => array('aeroflot_email' => $aeroflot),
        'uralair' => array('uralair_email' => $uralair),
        'rzd' => array('rzd_email' => $rzd),
//        'papajohns' => array('papajohns_email' => $papajohns),
        'avito' => array('avito_email' => $avito),
        'sber' => array('sber_email' => $sber),
        'test' => array('test_email' => $test),
        'testr' => array('test_email' => $testr),
      ),
      'nick' => array(
        'microsoft' => array('microsoft_nick' => $microsoft),
        'skype' => array('skype' => $skype),
//        'telegram' => array('telegram' => $telegram),
//        'commerce' => array('commerce_skype' => $commerce),
        'vk' => array('vk_nick' => $vk),
        'ok' => array('ok_nick' => $ok),
//        'facebook' => array('facebook_nick' => $facebook),
//        'instagram' => array('instagram_nick' => $instagram),
        'rzd' => array('rzd_nick' => $rzd),
      ),
      'url' => array(
        'facebook' => array('facebook_url' => $facebook),
        'vk' => array('vk_url' => $vk),
        'ok' => array('ok_url' => $ok/*, 'ok_urlcheck' => $okbot*/),
        'instagram' => array('instagram_url' => $instagram),
        'hh' => array('hh_url' => $hh),
      ),
      'car' => array(
        'gibdd' => array('gibdd_history' => $gibdd, 'gibdd_register' => $gibdd, 'gibdd_aiusdtp' => $gibdd, 'gibdd_wanted' => $gibdd, 'gibdd_restricted' => $gibdd, 'gibdd_diagnostic' => $gibdd, 'gibdd_fines' => $gibdd),
        'eaisto' => array('eaisto' => $eaisto),
        'carinfo' => array('carinfo' => $ingos),
        'rsa' => array('rsa_policy' => $rsa),
        'rz' => array('rz_auto' => $rz),
        'reestrzalogov' => array('reestrzalogov_auto' => $reestrzalogov),
        'gisgmp' => array('gisgmp_fines' => $gisgmp),
//        'autoru' => array('autoru' => $autoru),
        'vin' => array('vin' => $vin),
        'avtokod' => array('avtokod_history' => $mosru, 'avtokod_pts' => $mosru, 'avtokod_fines' => $mosrufines, 'avtokod_status' => $avtokod, 'avtokod_taxi' => $mosru),
        'nbki' => array('nbki_auto' => $nbkiauto),
        'avinfo' => array('avinfo_auto' => $avinfo),
        'test' => array('test_auto' => $test),
        'testr' => array('test_auto' => $testr),
      ),
      'ip' => array(
        'dns' => array('dns' => $dns),
        'ripe' => array('ripe' => $ripe),
//        'ipgeo' => array('ipgeo' => $ipgeo),
        'sypexgeo' => array('sypexgeo' => $sypexgeo),
        'shodan' => array('shodan' => $shodan),
        'censys' => array('censys' => $censys),
      ),
      'org' => array(
        'egrul' => array('egrul_org' => $egrul, /*'egrul_daughter' => $egrul*//*, 'listorg_org' => $listorg*/),
        'fns' => array('fns_bi' => $fns, 'fns_rmsp' => $fns, 'fns_disfind' => $fns, 'fns_zd' => $fns, /*'fns_sshr' => $fns, 'fns_snr' => $fns, 'fns_revexp' => $fns, 'fns_paytax' => $fns, 'fns_debtam' => $fns, 'fns_taxoffence' => $fns *//*, 'fns_uwsfind' => $fns, 'fns_ofd' => $fns*/),
        'vestnik' => array('vestnik_org' => $vestnik/*, 'vestnik_fns' => $vestnik*/),
        'gks' => array('gks_org' => $gks),
        'zakupki' => array(/*'zakupki_eruz' => $zakupki, */'zakupki_org' => $zakupki, 'zakupki_customer223' => $zakupki, 'zakupki_order' => $zakupki, 'zakupki_contract' => $zakupki, 'zakupki_fz223' => $zakupki, 'zakupki_capital' => $zakupki, 'zakupki_dishonest' => $zakupki, 'zakupki_guarantee' => $zakupki, 'zakupki_rkpo' => $zakupki),
        'kad' => array('kad_org' => $kad),
        'bankrot' => array('bankrot_org' => $bankrot),
        'cbr' => array('cbr_org' => $cbr),
        'rz' => array('rz_org' => $rz),
        'reestrzalogov' => array('reestrzalogov_org' => $reestrzalogov),
        'rsa' => array('rsa_org' => $rsa),
        'fssp' => array('fssp_org' => $fssp, 'fssp_inn' => $fssp),
        'fsspapi' => array('fssp_org' => $fsspapi),
        'fsspsite' => array('fssp_org' => $fssp, 'fssp_inn' => $fssp),
        '2gis' => array('2gis_inn' => $gis),
        'test' => array('test_org' => $test),
        'testr' => array('test_org' => $testr),
      ),
      'card' => array(
        'sber' => array('sberbank_card' => $sb),
      ),
      'fssp_ip' => array(
        'fssp' => array('fssp_ip' => $fssp),
        'fsspapi' => array('fssp_ip' => $fsspapi),
//        'fsspsite' => array('fssp_ip' => $fssp),
        'gisgmp' => array('gisgmp_ip' => $gisgmp),
      ),
      'osago' => array(
        'rsa' => array('rsa_bsostate' => $rsa/*, 'rsa_osagovehicle' => $rsa*/),
      ),
      'text' => array(
        'facebook' => array('facebook_text' => $facebook),
        'vk' => array('vk_text' => $vk),
        'ok' => array('ok_text' => $ok),
        'hh' => array('hh_text' => $hh),
        'skype' => array('skype_text' => $skype),
      ),
    );

        $plugins['ip']['geoip']['geoip'] = $uni;
        $plugins['phone']['facebook']['facebook_phoneurl'] = $uni;
        $plugins['phone']['announcement']['announcement_phone'] = $uni;
        $plugins['phone']['hlr']['hlr_phone'] = $uni;
        $plugins['phone']['smsc']['smsc_phone'] = $uni;

        $plugins['person']['fsin']['fsin_person'] = $uni;
        $plugins['person']['minjust']['minjust_person'] = $uni;
        $plugins['person']['minjust']['minjust_inn'] = $uni;
        $plugins['org']['minjust']['minjust_org'] = $uni;
        $plugins['org']['rosobrnadzor']['rosobrnadzor_license'] = $uni;

        $plugins['org']['fns']['fns_paytax'] = $uni;
        $plugins['org']['fns']['fns_revexp'] = $uni;
//        $plugins['org']['fns']['fns_rmsp'] = $uni;
        $plugins['org']['fns']['fns_snr'] = $uni;
        $plugins['org']['fns']['fns_sshr'] = $uni;
        $plugins['org']['fns']['fns_taxoffence'] = $uni;
//        $plugins['org']['fns']['fns_debtam'] = $uni;

        $plugins['car']['elpts']['elpts'] = $uni;
        $plugins['car']['alfastrah']['alfastrah'] = $uni;

//    if ($clientId==264 || $clientId==265) {
//        unset($plugins['phone']['ok']['ok_phoneapp']);
//        unset($plugins['email']['ok']['ok_emailapp']);
//        $plugins['phone']['ok']['ok_phoneappr'] = $okappr;
//        $plugins['email']['ok']['ok_emailappr'] = $okappr;
        $plugins['ip']['dns']['dns'] = $uni;
        $plugins['ip']['ripe']['ripe'] = $uni;
        $plugins['ip']['sypexgeo']['sypexgeo'] = $uni;
        $plugins['ip']['shodan']['shodan'] = $uni;
        $plugins['ip']['censys']['censys'] = $uni;
        $plugins['ip']['libloc']['libloc'] = $uni;
        $plugins['phone']['boards'] = array('boards_phone' => $uni);
        $plugins['phone']['names']['names_phone'] = $uni;
        $plugins['phone']['phones']['phones_phone'] = $uni;
//        $plugins['phone']['rossvyaz']['rossvyaz_phone'] = $uni;
        $plugins['person']['fms']['fms_passport'] = $uni;
        $plugins['person']['fmsdb']['fmsdb_passport'] = $uni;
        $plugins['person']['fns']['fns_disqualified'] = $uni;
        $plugins['person']['fns']['fns_mru'] = $uni;
        $plugins['org']['fns']['fns_disfind'] = $uni;
        $plugins['person']['cbr']['cbr_person'] = $uni;
        $plugins['org']['cbr']['cbr_org'] = $uni;
//        $plugins['phone']['vk']['vk_phone'] = $uni;
//        $plugins['email']['vk']['vk_email'] = $uni;
        $plugins['phone']['google']['google_phone'] = $uni;
        $plugins['email']['google']['google_email'] = $uni;
        $plugins['phone']['google']['google_name'] = $uni;
        $plugins['email']['google']['google_name'] = $uni;
        $plugins['nick']['google']['google_nick'] = $uni;
        $plugins['phone']['apple']['apple_phone'] = $uni;
        $plugins['email']['apple']['apple_email'] = $uni;
        $plugins['email']['samsung']['samsung_email'] = $uni;
        $plugins['person']['samsung']['samsung_person'] = $uni;
        $plugins['phone']['samsung']['samsung_name'] = $uni;
        $plugins['email']['samsung']['samsung_name'] = $uni;
        $plugins['phone']['xiaomi']['xiaomi_phone'] = $uni;
        $plugins['email']['xiaomi']['xiaomi_email'] = $uni;
        $plugins['phone']['huawei']['huawei_phone'] = $uni;
        $plugins['email']['huawei']['huawei_email'] = $uni;
        $plugins['phone']['honor']['honor_phone'] = $uni;
        $plugins['email']['honor']['honor_email'] = $uni;
        $plugins['phone']['lenovo']['lenovo_phone'] = $uni;
        $plugins['email']['lenovo']['lenovo_email'] = $uni;
        $plugins['phone']['domru']['domru_phone'] = $uni;
        $plugins['phone']['eyecon']['eyecon_phone'] = $uni;
        $plugins['phone']['viewcaller']['viewcaller_phone'] = $uni;
//        $plugins['phone']['domclick']['domclick_phone'] = $uni;
        $plugins['phone']['ok']['ok_phoneapp'] = $uni;
        $plugins['email']['ok']['ok_emailapp'] = $uni;
        $plugins['phone']['fotostrana']['fotostrana_phone'] = $uni;
        $plugins['email']['fotostrana']['fotostrana_email'] = $uni;
        $plugins['phone']['microsoft']['microsoft_phone'] = $uni;
        $plugins['email']['microsoft']['microsoft_email'] = $uni;
        $plugins['nick']['microsoft']['microsoft_nick'] = $uni;
        $plugins['person']['kad']['kad_person'] = $uni;
        $plugins['org']['kad']['kad_org'] = $uni;
        $plugins['phone']['2gis']['2gis_phone'] = $uni;
        $plugins['person']['2gis']['2gis_inn'] = $uni;
        $plugins['org']['2gis']['2gis_inn'] = $uni;
        $plugins['phone']['avito']['avito_phone'] = $uni;
        $plugins['email']['avito']['avito_email'] = $uni;
        $plugins['phone']['callapp']['callapp_phone'] = $uni;
//        $plugins['phone']['icq']['icq_phone'] = $uni;
        $plugins['phone']['krasnoebeloe']['krasnoebeloe_phone'] = $uni;
        $plugins['phone']['winelab']['winelab_phone'] = $uni;
        $plugins['email']['winelab']['winelab_email'] = $uni;
        $plugins['phone']['petrovich']['petrovich_phone'] = $uni;
        $plugins['email']['petrovich']['petrovich_email'] = $uni;
        $plugins['phone']['numbuster']['numbuster_phone'] = $uni;
        $plugins['phone']['numbusterpro']['numbusterpro_phone'] = $uni;
        $plugins['phone']['ok']['ok_phonecheck'] = $uni;
        $plugins['email']['ok']['ok_emailcheck'] = $uni;
        $plugins['url']['ok']['ok_urlcheck'] = $uni;
        $plugins['nick']['ok']['ok_nickcheck'] = $uni;
        $plugins['phone']['papajohns']['papajohns_phone'] = $uni;
        $plugins['phone']['simpler']['simpler_phone'] = $uni;
        $plugins['phone']['telegram']['telegram_phone'] = $uni;
        $plugins['nick']['telegram']['telegram_nick'] = $uni;
        $plugins['phone']['truecaller']['truecaller_phone'] = $uni;
        $plugins['phone']['viber']['viber_phone'] = $uni;
        $plugins['phone']['whatsapp']['whatsappweb_phone'] = $uni;
        $plugins['phone']['whatsapp_phone']['whatsapp_phone'] = $uni;
        $plugins['phone']['yamap']['yamap_phone'] = $uni;
        $plugins['phone']['pochta']['pochta_phone'] = $uni;
//        $plugins['phone']['getcontact']['getcontact_phone'] = $uni;

        $plugins['phone']['sber']['sber_phone'] = $uni;
        $plugins['email']['sber']['sber_email'] = $uni;
        $plugins['phone']['rosneft']['rosneft_phone'] = $uni;
        $plugins['phone']['yoomoney']['yoomoney_phone'] = $uni;
        $plugins['email']['yoomoney']['yoomoney_email'] = $uni;
        $plugins['phone']['litres']['litres_phone'] = $uni;
        $plugins['email']['litres']['litres_email'] = $uni;
        $plugins['email']['duolingo']['duolingo_email'] = $uni;
//    }

    return $plugins;
}

function runRequests($params)
{
    if (array_search('rsa',$params['sources'])!==false) {
//        $params['timeout'] = $params['timeout']*5;
    }
    set_time_limit($params['timeout']+10);

    $plugins = initPlugins($params['sources']);
    $rm = new RequestManager($params['timeout']);
    $response = $rm->performRequests($params,$plugins);

    return $response;
}

function generateResponse($results,$status)
{
    global $serviceurl;
    global $reqId;
    global $req;
    global $params;
    global $restime;
    global $checktypes;

    $response = '<?xml version="1.0" encoding="utf-8"?>';
    $response .= "\n<Response id=\"".$reqId."\" status=\"".$status."\" datetime=\"".$restime."\" result=\"".$serviceurl."showresult.php?id=".$reqId."&amp;mode=xml\" view=\"".$serviceurl."showresult.php?id=".$reqId."\">\n".$req;

    foreach($results as $result)
    {
        if(/*($result->getPlugin() instanceof PluginInterface) && */($result->getResultData() || $result->getError()))
        {
            $checktype = $result->getCheckType();
            $response .= "
            <Source code=\"".$result->getSource()."\" checktype=\"".$result->getCheckType()."\" start=\"".$result->getStart()."\" param=\"".$result->getParam()."\" path=\"".$result->getPath()."\" level=\"".substr_count($result->getPath(),'/')."\" index=\"".$result->getLevel()."\" request_id=\"".$result->getId()."\" process_time=\"".$result->processTime()."\">
                <Name>".(isset($checktypes[$checktype])?$checktypes[$checktype]['source_name']:$result->getSourceName())."</Name>
                <Title>".(isset($checktypes[$checktype])?$checktypes[$checktype]['source_title']:$result->getSourceTitle())."</Title>
                <CheckTitle>".(isset($checktypes[$checktype])?$checktypes[$checktype]['title']:$result->getCheckTitle())."</CheckTitle>";

            $response .= "
                <Request>".htmlspecialchars(implode(' ',$result->getCheckType()=='hh_url'?array('hh_url *****'):$result->getInitData()),ENT_XML1)."</Request>";

            if($result->getError())
            {
                $response .= "
                <Error>".htmlspecialchars($result->getError(),ENT_XML1)."</Error>";
            }
            else
            {
                $rData = $result->getResultData();

                /*$appendResult = function(&$response, $record)
                {
                    if(sizeof($record))
                    {
                        $response .= "
                <Record>";

                        foreach($record as $field)
                            $response .= "
                    <Field>
                        <FieldType>".$field->getType()."</FieldType>
                        <FieldName>".$field->getName()."</FieldName>
                        <FieldTitle>".$field->getTitle()."</FieldTitle>
                        <FieldDescription>".$field->getDesc()."</FieldDescription>
                        <FieldValue>".$field->getValue()."</FieldValue>
                    </Field>";

                        $response .= "
                 </Record>";
                    }

                };*/

                if($rData instanceof ResultDataList)
                {
                    $response .= "
                <ResultsCount>".$rData->getResultsCount()."</ResultsCount>";
                    foreach($rData->getResults() as $record)
                        compileField($response, $record);
                    foreach($rData->getResults() as $record)
                        compileContact($response, $record);
                }
                else
                    compileField($response, $rData);
            }

            $response .= "
            </Source>\n";
        }
    }
    $response .= '</Response>';

    if ($status && sizeof($params['rules'])) {
        require_once('decision.php');
        if($dec_response = make_decision($response,$params['rules'])){
             $response = $dec_response;
        }
    }

    $response = preg_replace("/[^\x09\x0A\x0D\x20-\u{D7FF}\u{E000}-\u{FFFD}\u{10000}-\u{10FFFF}]/u","",$response);
    return $response;
}

function compileField(&$response,$record)
{
    if(sizeof($record))
    {
        $response .= "
                <Record>";

        foreach($record as $field) {
            $response .= "
                    <Field>
                        <FieldType>".$field->getType()."</FieldType>
                        <FieldName>".$field->getName()."</FieldName>
                        <FieldTitle>".$field->getTitle()."</FieldTitle>
                        <FieldDescription>".$field->getDesc()."</FieldDescription>
                        <FieldValue>".($field->getType()=='hidden'?' *****':htmlspecialchars(preg_replace('/(?:\\\\u[\pL\p{Zs}])+/','',strval($field->getValue())),ENT_XML1))."</FieldValue>
                    </Field>";
        }

        $response .= "
                 </Record>";
    }

}

function compileContact(&$response,$record)
{
    global $contact_types,$contact_urls;
    foreach($record as $field) {
        if (in_array($field->getType(),$contact_types) || ($field->getType()=='url' && in_array(parse_url($field->getValue(),PHP_URL_HOST),$contact_urls))) {
            $response .= "
                <Contact>
                    <ContactType>".$field->getType()."</ContactType>
                    <ContactTitle>".$field->getTitle()."</ContactTitle>
                    <ContactId>".htmlspecialchars(preg_replace('/(?:\\\\u[\pL\p{Zs}])+/','',strval($field->getValue())),ENT_XML1)."</ContactId>
                </Contact>";
        }
    }

}

/*
$xhprof_data = xhprof_disable();

include_once '/usr/share/php/xhprof_lib/utils/xhprof_lib.php';
include_once '/usr/share/php/xhprof_lib/utils/xhprof_runs.php';

$xhprof_runs = new XHProfRuns_Default();
$run_id = $xhprof_runs->save_run($xhprof_data, 'index_new');
*/

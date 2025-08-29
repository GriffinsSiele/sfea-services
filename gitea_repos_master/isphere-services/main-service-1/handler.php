<?php

//////////////////////////////////////////////////////////
include ('config.php');
include ('cookies.php');
include ('str.php');
include ('neuro.php');

include ('engine/RequestContext.php');
include ('engine/ResultDataField.php');
include ('engine/ResultDataList.php');

include ('engine/plugins/PluginInterface.php');

//////////////////////////////////////////////////////////

if ($_SERVER['REQUEST_METHOD']=='POST') {
    $request = file_get_contents('php://input');
    $params = json_decode($request,true);
    if (!$params || !is_array($params)) {
        $result = array('code'=>401,'message'=>'Некорректный запрос');
        response($result); 
        exit();
    }
} elseif ($_SERVER['REQUEST_METHOD']=='GET') {
    $params = $_GET;
} else {
    $result = array('code'=>401,'message'=>'Метод не поддерживается');
    response($result);
    exit();
}

// удаляем пустые параметры
foreach ($params as $name => $value) {
    if ($value=='') unset($params[$name]);
}
// проверяем ключ авторизации, временно статика хардкодом
// в кубере наверное будет не нужен
if (!isset($params['auth']) || $params['auth']!='n1vTY76fuCT59MH') {
    $result = array('code'=>401,'message'=>'Требуется авторизация');
    response($result);
    exit();
}
// еще 2 обязательных параметра
if (!isset($params['plugin'])) {
    $result = array('code'=>400,'message'=>'Не указан плагин');
    response($result);
    exit();
}
if (!isset($params['checktype'])) {
    $result = array('code'=>400,'message'=>'Не указан код проверки');
    response($result);
    exit();
}

// используется в плагинах для захвата сессий
$reqId = isset($params['id'])?$params['id']:rand(1,getrandmax());
// время отправки задания
$starttime = isset($params['starttime'])?$starttime:time();
// id пользователя и клиента
// иногда используются в плагинах для ограничения доступа пользователям
$userId = isset($params['userid'])?$params['userid']:'';
$clientId = isset($params['clientid'])?$params['userid']:'';
// список хешей keydb для ответа
$hash = isset($params['hash'])?$params['hash']:false; //$params['checktype'];
// время жизни ответа в хеше, секунды
$expire = isset($params['expire'])?$params['expire']:3600;
// таймаут выполнения запроса, считается от starttime
if (isset($params['timeout']) && intval($params['timeout']) && intval($params['timeout'])<=600) {
    $total_timeout = $timeout = intval($params['timeout']);
}
// также принимаем отчество с более привычным именем параметра
if (isset($params['middle_name'])) {
    $params['patronymic'] = $params['middle_name'];
    unset($params['middle_name']);
}
// на всякий случай переводим даты в привычный формат d.m.Y
if (isset($params['date'])) {
    $params['date'] = date('d.m.Y',strtotime(trim($params['date'])));
}
if (isset($params['driver_date'])) {
    $params['driver_date'] = date('d.m.Y',strtotime(trim($params['driver_date'])));
}
if (isset($params['reqdate'])) {
    $params['reqdate'] = date('d.m.Y',strtotime(trim($params['reqdate'])));
}
$key = isset($params['key'])?$params['key']:paramsToKey($params);
if ($hash) {
    try {
        $db = new Redis();
        $db->connect($keydb['server'],6379,$keydb['connect_timeout'],NULL,100,$keydb['read_timeout']);
        $db->auth($keydb['auth']);
// проверяем наличие в кеше
        if(!isset($params['nocache']) && $db->hexists($hash, $key)) {
            $cached = $db->hget($hash, $key);
            $response = json_decode($cached, true);
// На всякий случай проверим код и checktype, можно добавить проверку на срок жизни в кеше
            if ($response['code']<300 && $response['checktype']==$params['checktype']) {
// меняем тех.атрибуты ответа на актуальные
                if (isset($response['id'])) $response['cached_id'] = $response['id'];
                if (isset($response['timestamp'])) $response['cached_timestamp'] = $response['timestamp'];
                if (isset($response['starttime'])) $response['cached_starttime'] = $response['starttime'];
                if (isset($response['processtime'])) $response['cached_processtime'] = $response['processtime'];
                $response['id'] = $reqId;
                $response['starttime'] = $starttime;
                $response['processtime'] = 0;
                $db->close();
                response($response);
                exit();
            }
        }
    } catch (Exception $e) {
        $result = array('code'=>500,'message'=>'Внутренняя ошибка сервиса'); 
        response($result);
        exit();
    }
}

// большинство плагинов работают с таблицами session и proxy, а также со статическими БД (fns,rossvyaz,fedsfm,vk)
// GetContactAppPlugin также запрашивает статистику по источнику из ResponseNew
// для простых веб-сервисов и новых плагинов, работающих через через очереди, можно убрать $database из config.php
$mysqli = false;
if (isset($database['server'])) {
    $mysqli = mysqli_init();
    mysqli_options($mysqli,MYSQLI_OPT_CONNECT_TIMEOUT,$database['connect_timeout']);
    mysqli_options($mysqli,MYSQLI_OPT_READ_TIMEOUT,$database['read_timeout']);
    $mysqli = mysqli_connect($database['server'],$database['login'],$database['password'],$database['name']);
    if ($mysqli) {
//if (mysqli_real_connect($mysqli,$database['server'],$database['login'],$database['password'],$database['name'])) {
        mysqli_query($mysqli, "Set character set utf8");
        mysqli_query($mysqli, "Set names 'utf8'");
    } else {
        $result = array('code'=>500,'message'=>'Внутренняя ошибка сервиса'); 
        response($result);
        exit();
    }
}

$plugin_interface = array();
$rContext = new RequestContext($params['checktype'], $params['checktype'], $reqId, false, false, '', 0, $params['plugin'], $params);
performRequest($rContext);
// формируем массив для ответа
$response = pluginResponse($rContext);
// выдаем окончательный ответ
response($response);
// кешируем ответ
if ($hash) {
    try {
        $db->hset($hash, $key, json_encode($response, true));
// время жизни ответа в кеше (сделать параметром)
        $db->rawcommand('expiremember',$hash,$key,$expire);
        $db->close();
    } catch (Exception $e) {
    }
}

//////////////////////////////////////////////////////////

function performRequest(&$rContext)
{
        global $starttime, $timeout;

        $mh = curl_multi_init();
        $rContext->initCurlHandler();
        $timeoutReach = false;
        $working = 1;

        do { // цикл выполнения запроса
            if(time()-$starttime > $timeout) { // Общий таймаут выполнения запроса
                $timeoutReach = true;
            }

            $running = $working;

            while (!$timeoutReach && ($running > 0) && ($status = curl_multi_exec($mh, $running)) == CURLM_CALL_MULTI_PERFORM); //Запускаем соединения
//            usleep (100000); // 100мс
//            $status = curl_multi_exec($mh, $running);
//            print date('Y-m-d H:i:s')." running = $running, status = $status\n";

            while (!$timeoutReach && ($running > 0) && ($status == CURLM_OK)) { //Пока есть незавершенные соединения и нет ошибок мульти-cURL
                $sel = curl_multi_select($mh,1); //ждем активность на файловых дескрипторах. Таймаут 1 сек
                usleep (10000); // 10мс
//                usleep (500000); // 500мс
                while (($status = curl_multi_exec($mh, $running)) == CURLM_CALL_MULTI_PERFORM);
//                print date('Y-m-d H:i:s')." status = $status\n";

                while (($info = curl_multi_info_read($mh)) != false) { //Если есть завершенные соединения
//                    $status = -1;
                    $rContext->getPlugin()->computeRequest($rContext);

                    curl_multi_remove_handle($mh, $info['handle']);
                    curl_close($info['handle']);

                    if($rContext->isFinished()) {
//                        print date('Y-m-d H:i:s')." [$ind] ".$rContext->getId()." finished\n";
                        $working=0;
                    } else {
//                        print date('Y-m-d H:i:s')." [$ind] ".$rContext->getId()." repeated\n";
                        $rContext->initCurlHandler();
                    }
                }

                if(time()-$starttime > $timeout) { // Общий таймаут выполнения запроса
                    $timeoutReach = true;
                }
            }

                if($working && $rContext->isReady()) {
                    if(!is_null($rContext->getPlugin()) && $rContext->getPlugin()->prepareRequest($rContext) && !$rContext->isFinished()) { // плагин готов выполнить запрос в этом контексте
                        curl_multi_add_handle($mh, $rContext->getCurlHandler()); // добавляем дескриптор curl
//                        print date('Y-m-d H:i:s')." [$ind] ".$rContext->getId()." started \n";
                    } elseif ($rContext->isFinished()) { // контекст завершен
//                        print date('Y-m-d H:i:s')." [$ind] ".$rContext->getId()." not started\n";
                        curl_close($rContext->getCurlHandler());
                        $working=0;
                    }
                } else {
//                    print date('Y-m-d H:i:s')." [$ind] ".$rContext->getId()." not ready\n";
                }

            if(time()-$starttime > $timeout) { // Общий таймаут выполнения запроса
                $timeoutReach = true;
            }

            if (($working > 0) && !$timeoutReach) usleep(10000);
//            print "running=" . $running . " working=" . $working . " workingPool = [" . sizeof($workingPool) . "]\n";
        } while (($working > 0) && !$timeoutReach);

            if(!$rContext->isFinished()) {
                $rContext->setFinished();
                $rContext->setError($timeoutReach?'Превышено время ожидания':'Неизвестная ошибка');

                $ch = $rContext->getCurlHandler();
                if ($ch) curl_close($ch);
            }
}

function pluginResponse($result)
{
        global $starttime, $reqId, $key, $params;
        $response = array();

        if(/*($result->getPlugin() instanceof PluginInterface) && */($result->getResultData() || $result->getError()))
        {
            $response['key'] = $key;
            $response['id'] = $reqId;
            $response['starttime'] = $starttime;
            $response['processtime'] = $result->processTime();
            $response['source_name'] = $result->getSourceName();
            $response['source_title'] = $result->getSourceTitle();
            $response['checktype'] = $params['checktype'];
            $response['checktype_title'] = $result->getCheckTitle();

            if($result->getError()) {
                $response['code'] = 500;
                $response['message'] = $result->getError();
            } else {
                $records = array();
                $rData = $result->getResultData();
                if($rData instanceof ResultDataList) {
                    foreach($rData->getResults() as $r) {
                        $record = array();
                        foreach($r as $f) {
/*
                            $field = array(
                                'field' => $f->getName(),
                                'type' => $f->getType(),
                                'value' => strval($f->getValue()),
                                'title' => $f->getTitle(),
                                'description' => $f->getDesc(),
                            );
                            $record[] = $field;
*/
                            $record[$f->getName()] = $f->getValue();
                        }
                        $records[] = $record;
                    }
                }
                $response['code'] = 200; //sizeof($records)?200:204;
                $response['records'] = $records;
            }
        } else {
                $response['code'] = 400;
                $response['message'] = 'Запрос не выполнен';
        }

        return $response;
}

function response($result)
{
    $http_text = array(
        200 => 'OK',
        204 => 'No Content',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        500 => 'Internal Server Error',
    );
    if (!isset($result['code'])) {
        $result = array('code'=>500,'message'=>'Некорректный ответ источника');
    }
    $result['status'] = $result['code']<300?'ok':'Error';
    $result['timestamp'] = time();

    header("HTTP/1.1 ".$result['code']." ".$http_text[$result['code']]);
    header('Content-Type: application/json');
    echo json_encode($result, true);
}

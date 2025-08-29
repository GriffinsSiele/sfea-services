<?php

$nn_minacc = array(
    '' => 0.5,
//    'fssp' => 0.1,
//    'vk' => 0.1,
//    'getcontact' => 0.1,
//    'gosuslugi' => 0.3,
);

$nn_services = explode(',', getenv('IMAGE_CAPTCHA_URLS'));
/*
$nn_services = array(
//    'http://captcha-image-api.default',
//    'http://captcha-image-api.i-sphere.local',
    'http://172.16.0.1',
    'http://172.16.0.65',
);
*/
$nn_provider = getenv('IMAGE_CAPTCHA_PROVIDER');
//$nn_provider = 'nnetworks';

$nn_trytimeout = [2,2];
$nn_error = 0;
$nn_id = 0;
$nn_results = array();
$nn_wait = array();

$neuro_sources = array(
//    'emt' => 'emt',
    'fns_bi' => 'fns',
    'fns_svl' => 'fns',
    'mvd_wanted' => 'mvd',
    'fsspsite' => 'fsspsite',
    'getcontact_app' => 'gc',
    'vk' => 'vk',
    'vk_recover' => 'vk',
    'fms' => 'fms',
    'gosuslugi' => 'gosuslugi',
    'gibdd' => 'gibdd',
    'fotostrana' => 'fotostrana',
);

$neuro_methods = array(
//    'emt' => 'emt',
    'fnsdecode' => 'fns',
    'mvddecode' => 'mvd',
    'fsspsitedecode' => 'fssp',
    'gcdecode' => 'getcontact',
    'vkdecode' => 'vk',
    'fmsdecode' => 'fms',
    'gosuslugidecode' => 'gosuslugi',
    'gibdddecode' => 'gibdd',
    'fotostranadecode' => 'fotostrana',
);

$neuro_minacc = array(
    '' => 0.5,
//    'fsspsitedecode' => 0.1,
//    'vkdecode' => 0.1,
);

$neuro_services = explode(',', getenv('TEXT_CAPTCHA_URLS'));
$neuro_tries = 3;
$neuro_timeout = 1;

$token_services = array(
//    'http://172.16.1.14:8003',
//    'http://172.16.1.253:8003',
    'http://172.16.1.254:8003',
);
$token_tries = 2;
$token_timeout = 1;


function nn_post($image, $source, $minacc=0)
{
    global $nn_services,$nn_provider,$nn_minacc,$nn_trytimeout,$nn_error,$nn_id,$nn_results,$nn_wait,$sessionid;
    if ($nn_error>=3 && $nn_error%20==0) { // если 3 ошибки подряд, пропускаем только 1 запрос из 20
        return 'ERROR_DISABLED';
    }
    $start = microtime(true);
    $boundary = '--------------------------'.microtime(true);
    $post_data = "--".$boundary."\r\n".
        "Content-Disposition: form-data; name=\"image\"; filename=\"captcha.jpg\"\r\n".
        "Content-Type: image/jpeg\r\n\r\n".
        $image."\r\n".
        "--".$boundary."--\r\n";
    $tries = 0;
    do {
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: multipart/form-data; boundary='.$boundary,
                'content' => $post_data,
                'timeout' => $nn_trytimeout[$tries],
            )
        ));
        $result = @file_get_contents($nn_services[$tries%sizeof($nn_services)]."/api/decode/image?provider=$nn_provider&source=$source&timeout=0",false,$context);
        if ($result) {
            $nn_error=0;
        } else {
            $nn_error++;
            $error = error_get_last();
            file_put_contents('./logs/nn-'.date('Y-m-d').'.log',date('Y-m-d H:i:s')." decode id=$sessionid source=$source timeout=".$nn_trytimeout[$tries]." errors=$nn_error {$error['message']}\n",FILE_APPEND);
        }
    } while (!$result && ++$tries<sizeof($nn_trytimeout));
    $res = json_decode($result, true);
    $process_time = number_format(microtime(true)-$start,2,'.','');
    file_put_contents('./logs/nn-'.date('Y-m-d').'.log',date('Y-m-d H:i:s')." decode id=$sessionid source=$source processtime=$process_time $result\n",FILE_APPEND);
    if (!$res) {
        return 'ERROR_NO_ANSWER';
//    } elseif (!isset($res['text'])) {
    } elseif (!isset($res['task_id'])) {
        return 'ERROR_CAPTCHA_UNSOLVABLE';
    }
    if ($minacc==0) $minacc = isset($nn_minacc[$source])?$nn_minacc[$source]:$nn_minacc[''];
//    echo "Neuro $source: $result min=$minacc\n";
    if (isset($res['accuracy']) && $res['accuracy']<$minacc) {
//        return 'ERROR_CAPTCHA_INNACURATE';
    }
    $id = $res['task_id'];
    if (isset($res['text'])) {
        if (!$id) $id=--$nn_id;
        $nn_results[$id] = strtr($res['text'],array(' '=>'','-'=>'9','[UNK]'=>'9'));
    } elseif ($id) {
        $nn_wait[$id] = time()+10;
    }
    return $id;
}

function neuro_get($id)
{
    global $nn_services,$nn_trytimeout,$nn_error,$nn_results,$nn_wait,$sessionid;
    if (isset($nn_results[$id])) {
        $text = $nn_results[$id];
        unset($nn_results[$id]);
        return $text;
    } elseif (isset($nn_wait[$id]) && time()<$nn_wait[$id]) {
        return false;
    }
    $start = microtime(true);
    $params = array(
        'task_id' => $id,
    );
//    $tries = rand(0,sizeof($nn_services)-1);
    $tries = 0;
//    do {
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
//                'header' => 'Content-Type: application/x-www-form-urlencoded',
//                'content' => http_build_query($params),
                'timeout' => $nn_trytimeout[$tries],
            )
        ));
        $result = @file_get_contents($nn_services[$tries%sizeof($nn_services)].'/api/tasks/result?'.http_build_query($params),false,$context);
        if ($result) {
            $nn_error=0;
        } else {
            $nn_error++;
            $error = error_get_last();
            file_put_contents('./logs/nn-'.date('Y-m-d').'.log',date('Y-m-d H:i:s')." result taskid=$id timeout=".$nn_trytimeout[$tries]." errors=$nn_error {$error['message']}\n",FILE_APPEND);
        }
//    } while (!$result && ++$tries<sizeof($nn_trytimeout));
    $res = json_decode($result, true);
    $process_time = number_format(microtime(true)-$start,2,'.','');
    file_put_contents('./logs/nn-'.date('Y-m-d').'.log',date('Y-m-d H:i:s')." result taskid=$id processtime=$process_time $result\n",FILE_APPEND);
    if (!$res) {
        $nn_wait[$id] = time()+5;
        return false; //'ERROR_NO_ANSWER';
    } elseif (!isset($res['text'])) {
        return 'ERROR_CAPTCHA_UNSOLVABLE';
    } else {
        return $res['text'];
    }
}

function neuro_report($id, $solved)
{
    global $nn_services,$nn_trytimeout,$nn_error,$sessionid;
    if ($id<=0) {
        return 'Ignore';
    }
    $start = microtime(true);
    $params = array(
        'task_id' => $id,
        'solved_status' => $solved,
    );
    $tries = 0;
    do {
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'PUT',
//                'header' => 'Content-Type: application/x-www-form-urlencoded',
//                'content' => http_build_query($params),
                'timeout' => $nn_trytimeout[$tries],
            )
        ));
        $result = @file_get_contents($nn_services[$tries%sizeof($nn_services)].'/api/tasks/update?'.http_build_query($params),false,$context);
        if ($result) {
            $nn_error=0;
        } else {
            $nn_error++;
            $error = error_get_last();
            file_put_contents('./logs/nn-'.date('Y-m-d').'.log',date('Y-m-d H:i:s')." report taskid=$id timeout=".$nn_trytimeout[$tries]." errors=$nn_error {$error['message']}\n",FILE_APPEND);
        }
    } while (!$result && ++$tries<sizeof($nn_trytimeout));
    $res = json_decode($result, true);
    $process_time = number_format(microtime(true)-$start,2,'.','');
    file_put_contents('./logs/nn-'.date('Y-m-d').'.log',date('Y-m-d H:i:s')." report taskid=$id processtime=$process_time $result\n",FILE_APPEND);
    if (!$res) {
        return 'ERROR_NO_ANSWER';
    } elseif (!isset($res['status'])) {
        return 'ERROR_NO_STATUS';
    } else {
        return $res['status'];
    }
}

function neuro_post($image, $method)
{
    global $neuro_methods;
    if (isset($neuro_methods[$method])) {
        return nn_post($image, $neuro_methods[$method]);
    } else {
        return 'ERROR_UNKNOWN_SOURCE';
    }
}

function neuro_token($method,$sitekey,$action='')
{
    global $token_services,$token_tries,$token_timeout;
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'timeout' => $token_timeout,
         )
    ));
    $tries = 0;
    do {
       $result = @file_get_contents($token_services[$tries%sizeof($token_services)]."/$method?sitekey=$sitekey".($action?"&action=$action":""),false,$context);
    } while (!$result && ++$tries<=$token_tries);
    $res = json_decode($result, true);
    if (!$res) {
        return 'ERROR_BAD_ANSWER';
    }
    return $res['code']==200?$res['token']:'ERROR_NO_TOKEN';
}

?>
